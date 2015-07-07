<?php

namespace spec\SM\StateMachine;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use SM\Callback\CallbackFactoryInterface;
use SM\Callback\CallbackInterface;
use SM\Event\SMEvents;
use spec\SM\DummyObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class StateMachineSpec extends ObjectBehavior
{
    protected $config = array(
        'graph' => 'graph1',
        'property_path' => 'state',
        'states' => array('checkout', 'pending', 'confirmed', 'cancelled'),
        'transitions' => array(
            'create' => array(
                'from' => array('checkout'),
                'to' => 'pending'
            ),
            'confirm' => array(
                'from' => array('checkout', 'pending'),
                'to' => 'confirmed'
            ),
            'cancel' => array(
                'from' => array('confirmed'),
                'to' => 'cancelled'
            )
        ),
        'callbacks' => array(
            'before' => array(
                'from-checkout' => array(
                    'from' => array('checkout'),
                    'do' => 'dummy'
                )
            ),
            'after' => array(
                'on-confirm' => array(
                    'on' => array('confirm'),
                    'do' => 'dummy'
                ),
                'to-cancelled' => array(
                    'to' => array('cancelled'),
                    'do' => 'dummy'
                )
            )
        )
    );

    function let(DummyObject $object, EventDispatcherInterface $dispatcher, CallbackFactoryInterface $callbackFactory)
    {
        $this->beConstructedWith($object, $this->config, $dispatcher, $callbackFactory);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('SM\StateMachine\StateMachine');
    }

    function it_can($object, $dispatcher)
    {
        $object->getState()->shouldBeCalled()->willReturn('checkout');
        $object->setState(Argument::any())->shouldNotBeCalled();

        $dispatcher->dispatch(SMEvents::TEST_TRANSITION, Argument::type('SM\\Event\\TransitionEvent'))->shouldBeCalled();

        $this->can('create')->shouldReturn(true);
    }

    function it_cannot($object, $dispatcher)
    {
        $object->getState()->shouldBeCalled()->willReturn('cancel');
        $object->setState(Argument::any())->shouldNotBeCalled();

        $dispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $this->can('create')->shouldReturn(false);
    }

    function it_throws_an_exception_if_transition_doesnt_exist_on_can()
    {
        $this->shouldThrow('SM\\SMException')->during('can', array('non-existing-transition'));
    }

    function it_applies_transition(
        $object,
        $dispatcher,
        $callbackFactory,
        CallbackInterface $callback1,
        CallbackInterface $callback2,
        CallbackInterface $callback3
    ) {
        $object->getState()->shouldBeCalled()->willReturn('checkout');
        $object->setState('confirmed')->shouldBeCalled();

        $dispatcher->dispatch(SMEvents::TEST_TRANSITION, Argument::type('SM\\Event\\TransitionEvent'))->shouldBeCalled();
        $dispatcher->dispatch(SMEvents::PRE_TRANSITION, Argument::type('SM\\Event\\TransitionEvent'))->shouldBeCalled();
        $dispatcher->dispatch(SMEvents::POST_TRANSITION, Argument::type('SM\\Event\\TransitionEvent'))->shouldBeCalled();

        $callbackFactory->get($this->config['callbacks']['before']['from-checkout'])->shouldBeCalled()->willReturn($callback1);
        $callbackFactory->get($this->config['callbacks']['after']['on-confirm'])->shouldBeCalled()->willReturn($callback2);
        $callbackFactory->get($this->config['callbacks']['after']['to-cancelled'])->shouldBeCalled()->willReturn($callback3);

        $callback1->__invoke(Argument::type('SM\\Event\\TransitionEvent'))->shouldBeCalled();
        $callback2->__invoke(Argument::type('SM\\Event\\TransitionEvent'))->shouldBeCalled();
        $callback3->__invoke(Argument::type('SM\\Event\\TransitionEvent'))->shouldBeCalled();

        $this->apply('confirm');
    }

    function it_applies_transition_ignoring_callback_before_as_false_with_on_fail_valid(
        $object,
        $dispatcher,
        $callbackFactory,
        CallbackInterface $callback
    ) {
        $config = $this->config;
        $config['ignore_before_callback_result'] = false;
        $config['transitions']['confirm']['on_fail'] = 'pending';

        $this->beConstructedWith($object, $config, $dispatcher, $callbackFactory);

        $object->getState()->shouldBeCalled()->willReturn('checkout');
        $object->setState('pending')->shouldBeCalled();

        $dispatcher->dispatch(SMEvents::TEST_TRANSITION, Argument::type('SM\\Event\\TransitionEvent'))->shouldBeCalled();
        $dispatcher->dispatch(SMEvents::PRE_TRANSITION, Argument::type('SM\\Event\\TransitionEvent'))->shouldBeCalled();
        $callbackFactory->get($config['callbacks']['before']['from-checkout'])->shouldBeCalled()->willReturn($callback);

        $callback->__invoke(Argument::type('SM\\Event\\TransitionEvent'))->shouldBeCalled()->willReturn(false);

        $this->apply('confirm');
    }

    function it_throws_exception_if_ignoring_callback_before_is_true_and_on_fail_not_defined(
        $object,
        $dispatcher,
        $callbackFactory,
        CallbackInterface $callback
    ) {
        $config = $this->config;
        $config['ignore_before_callback_result'] = false;

        $this->beConstructedWith($object, $config, $dispatcher, $callbackFactory);

        $object->getState()->shouldBeCalled()->willReturn('checkout');

        $dispatcher->dispatch(SMEvents::TEST_TRANSITION, Argument::type('SM\\Event\\TransitionEvent'))->shouldBeCalled();
        $dispatcher->dispatch(SMEvents::PRE_TRANSITION, Argument::type('SM\\Event\\TransitionEvent'))->shouldBeCalled();
        $callbackFactory->get($config['callbacks']['before']['from-checkout'])->shouldBeCalled()->willReturn($callback);

        $callback->__invoke(Argument::type('SM\\Event\\TransitionEvent'))->shouldBeCalled()->willReturn(false);

        $this->shouldThrow('SM\\SMException')->during('apply', array('confirm'));
    }

    function it_throws_an_exception_if_transition_cannot_be_applied($object, $dispatcher)
    {
        $object->getState()->shouldBeCalled()->willReturn('cancel');
        $object->setState(Argument::any())->shouldNotBeCalled();

        $dispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow('SM\\SMException')->during('apply', array('confirm'));
    }

    function it_does_nothing_if_transition_cannot_be_applied_in_soft_mode($object, $dispatcher)
    {
        $object->getState()->shouldBeCalled()->willReturn('cancel');
        $object->setState(Argument::any())->shouldNotBeCalled();

        $dispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $this->apply('confirm', true);
    }

    function it_throws_an_exception_if_transition_doesnt_exist_on_apply()
    {
        $this->shouldThrow('SM\\SMException')->during('apply', array('non-existing-transition'));
    }

    function it_returns_current_state($object)
    {
        $object->getState()->shouldBeCalled()->willReturn('my-state');

        $this->getState()->shouldReturn('my-state');
    }

    function it_returns_current_graph()
    {
        $this->getGraph()->shouldReturn($this->config['graph']);
    }

    function it_returns_current_object($object)
    {
        $this->getObject()->shouldReturn($object);
    }

    function it_returns_possible_transitions($object)
    {
        $object->getState()->shouldBeCalled()->willReturn('checkout');

        $this->getPossibleTransitions()->shouldReturn(array('create', 'confirm'));
    }
}

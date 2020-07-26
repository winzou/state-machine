<?php

namespace spec\SM\Factory;

use PhpSpec\ObjectBehavior;
use SM\Callback\CallbackFactoryInterface;
use SM\Factory\Factory;
use SM\SMException;
use SM\StateMachine\StateMachine;
use spec\SM\DummyObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FactorySpec extends ObjectBehavior
{
    protected $configs = array(
        'graph1' => array('state_machine_class' => StateMachine::class, 'class' => DummyObject::class),
        'graph2' => array('class' => DummyObject::class),
    );

    function let(EventDispatcherInterface $dispatcher, CallbackFactoryInterface $callbackFactory)
    {
        $this->beConstructedWith($this->configs, $dispatcher, $callbackFactory);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Factory::class);
    }

    function it_creates_statemachine(DummyObject $object)
    {
        $graph = 'graph1';

        $this->get($object, $graph)->shouldReturnAnInstanceOf($this->configs[$graph]['state_machine_class']);
    }

    function it_creates_statemachine_with_default_class(DummyObject $object)
    {
        $this->get($object, 'graph2')->shouldReturnAnInstanceOf(StateMachine::class);
    }

    function it_throws_exception_when_configuration_doesnt_exist(DummyObject $object)
    {
        $this->shouldThrow(SMException::class)->during('get', array($object, 'non-existing-graph'));
    }
}

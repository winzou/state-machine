<?php

namespace Tests\SM\StateMachine;

use SM\Callback\CallbackFactoryInterface;
use SM\Callback\CallbackInterface;
use SM\Event\SMEvents;
use SM\Event\TransitionEvent;
use SM\SMException;
use SM\StateMachine\StateMachine;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\SM\DummyObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class StateMachineTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array
     */
    protected $config = [
        'graph' => 'graph1',
        'property_path' => 'state',
        'states' => ['checkout', 'pending', 'confirmed', 'cancelled'],
        'transitions' => [
            'create' => [
                'from' => ['checkout'],
                'to' => 'pending',
            ],
            'confirm' => [
                'from' => ['checkout', 'pending'],
                'to' => 'confirmed',
            ],
            'cancel' => [
                'from' => ['confirmed'],
                'to' => 'cancelled',
            ],
            'invalid' => [
                'from' => ['checkout', 'pending'],
                'to' => 'invalid',
            ],
        ],
        'callbacks' => [
            'guard' => [
                'guard-confirm' => [
                    'from' => ['pending'],
                    'do' => 'dummy',
                ],
            ],
            'before' => [
                'from-checkout' => [
                    'from' => ['checkout'],
                    'do' => 'dummy',
                ],
            ],
            'after' => [
                'on-confirm' => [
                    'on' => ['confirm'],
                    'do' => 'dummy',
                ],
                'to-cancelled' => [
                    'to' => ['cancelled'],
                    'do' => 'dummy',
                ],
            ],
        ],
    ];

    /**
     * @var DummyObject&\PHPUnit\Framework\MockObject\MockObject
     */
    private $object;

    /**
     * @var EventDispatcherInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $dispatcher;

    /**
     * @var CallbackFactoryInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $callbackFactory;

    /**
     * @var StateMachine
     */
    private $stateMachine;

    public function setUp(): void
    {
        $this->object = $this->createMock(DummyObject::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->callbackFactory = $this->createMock(CallbackFactoryInterface::class);
        $this->stateMachine = new StateMachine($this->object, $this->config, $this->dispatcher, $this->callbackFactory);
    }

    /** @test */
    public function throws_exception_if_object_doesnt_have_state()
    {
        $config = $this->config;
        $config['property_path'] = 'status';

        $this->expectException(SMException::class);
        $this->stateMachine = new StateMachine($this->object, $config, $this->dispatcher, $this->callbackFactory);
    }

    /** @test */
    public function it_can()
    {
        $guard = $this->createMock(CallbackInterface::class);

        $this->object->expects($this->exactly(2))->method('getState')->willReturn('checkout');
        $this->object->expects($this->never())->method('setState');

        $this->dispatcher->expects($this->once())->method('dispatch')->with($this->isInstanceOf(TransitionEvent::class), SMEvents::TEST_TRANSITION);
        $this->callbackFactory->expects($this->once())->method('get')->with($this->config['callbacks']['guard']['guard-confirm'])->willReturn($guard);
        $guard->expects($this->once())->method('__invoke')->with($this->isInstanceOf(TransitionEvent::class))->willReturn(true);

        $this->assertTrue($this->stateMachine->can('create'));
    }

    /** @test */
    public function it_cannot()
    {
        $this->object->expects($this->once())->method('getState')->willReturn('cancel');
        $this->object->expects($this->never())->method('setState');

        $this->dispatcher->expects($this->never())->method('dispatch');

        $this->assertFalse($this->stateMachine->can('create'));
    }

    /** @test */
    public function it_is_guarded_and_can()
    {
        $guard = $this->createMock(CallbackInterface::class);

        $this->object->expects($this->exactly(2))->method('getState')->willReturn('pending');
        $this->object->expects($this->never())->method('setState');

        $this->dispatcher->expects($this->once())->method('dispatch')->with($this->isInstanceOf(TransitionEvent::class), SMEvents::TEST_TRANSITION);
        $this->callbackFactory->expects($this->once())->method('get')->with($this->config['callbacks']['guard']['guard-confirm'])->willReturn($guard);
        $guard->expects($this->once())->method('__invoke')->with($this->isInstanceOf(TransitionEvent::class))->willReturn(true);

        $this->assertTrue($this->stateMachine->can('confirm'));
    }

    /** @test */
    public function it_is_guarded_and_cannot()
    {
        $guard = $this->createMock(CallbackInterface::class);

        $this->object->expects($this->exactly(2))->method('getState')->willReturn('pending');
        $this->object->expects($this->never())->method('setState');

        $this->dispatcher->expects($this->once())->method('dispatch')->with($this->isInstanceOf(TransitionEvent::class), SMEvents::TEST_TRANSITION);
        $this->callbackFactory->expects($this->once())->method('get')->with($this->config['callbacks']['guard']['guard-confirm'])->willReturn($guard);
        $guard->expects($this->once())->method('__invoke')->with($this->isInstanceOf(TransitionEvent::class))->willReturn(false);

        $this->assertFalse($this->stateMachine->can('confirm'));
    }

    /** @test */
    public function it_throws_an_exception_if_transition_doesnt_exist_on_can()
    {
        $this->expectException(SMException::class);

        $this->stateMachine->can('non-existing-transition');
    }

    /** @test */
    public function it_applies_transition()
    {
        $guard = $this->createMock(CallbackInterface::class);
        $callback1 = $this->createMock(CallbackInterface::class);
        $callback2 = $this->createMock(CallbackInterface::class);
        $callback3 = $this->createMock(CallbackInterface::class);

        $this->object->expects($this->exactly(3))->method('getState')->willReturn('checkout');
        $this->object->expects($this->once())->method('setState')->with('confirmed');

        $this->dispatcher->expects($this->exactly(3))->method('dispatch')->withConsecutive(
            [$this->isInstanceOf(TransitionEvent::class), SMEvents::TEST_TRANSITION],
            [$this->isInstanceOf(TransitionEvent::class), SMEvents::PRE_TRANSITION],
            [$this->isInstanceOf(TransitionEvent::class), SMEvents::POST_TRANSITION]
        );

        $this->callbackFactory->expects($this->exactly(4))->method('get')->withConsecutive(
            [$this->config['callbacks']['guard']['guard-confirm']],
            [$this->config['callbacks']['before']['from-checkout']],
            [$this->config['callbacks']['after']['on-confirm']],
            [$this->config['callbacks']['after']['to-cancelled']],
        )->willReturn($guard, $callback1, $callback2, $callback3);

        $guard->expects($this->once())->method('__invoke')->with($this->isInstanceOf(TransitionEvent::class))->willReturn(true);
        $callback1->expects($this->once())->method('__invoke')->with($this->isInstanceOf(TransitionEvent::class));
        $callback2->expects($this->once())->method('__invoke')->with($this->isInstanceOf(TransitionEvent::class));
        $callback3->expects($this->once())->method('__invoke')->with($this->isInstanceOf(TransitionEvent::class));

        $this->stateMachine->apply('confirm');
    }

    /** @test */
    public function it_throws_an_exception_if_transition_cannot_be_applied()
    {
        $this->object->expects($this->exactly(2))->method('getState')->willReturn('cancel');
        $this->object->expects($this->never())->method('setState');

        $this->dispatcher->expects($this->never())->method('dispatch');

        $this->expectException(SMException::class);

        $this->stateMachine->apply('confirm');
    }

    /** @test */
    public function it_throws_an_exception_when_applying_a_transition_with_an_invalid_state()
    {
        $guard = $this->createMock(CallbackInterface::class);
        $callback1 = $this->createMock(CallbackInterface::class);

        $this->object->expects($this->exactly(3))->method('getState')->willReturn('checkout');
        $this->object->expects($this->never())->method('setState');

        $this->dispatcher->expects($this->exactly(2))->method('dispatch')->withConsecutive(
            [$this->isInstanceOf(TransitionEvent::class), SMEvents::TEST_TRANSITION],
            [$this->isInstanceOf(TransitionEvent::class), SMEvents::PRE_TRANSITION],
        );

        $this->callbackFactory->expects($this->exactly(2))->method('get')->withConsecutive(
            [$this->config['callbacks']['guard']['guard-confirm']],
            [$this->config['callbacks']['before']['from-checkout']],
        )->willReturn($guard, $callback1);

        $guard->expects($this->once())->method('__invoke')->with($this->isInstanceOf(TransitionEvent::class))->willReturn(true);
        $callback1->expects($this->once())->method('__invoke')->with($this->isInstanceOf(TransitionEvent::class));

        $this->expectException(SMException::class);
        $this->stateMachine->apply('invalid');
    }

    /** @test */
    public function it_does_nothing_if_transition_cannot_be_applied_in_soft_mode()
    {
        $this->object->expects($this->once())->method('getState')->willReturn('cancel');
        $this->object->expects($this->never())->method('setState');

        $this->dispatcher->expects($this->never())->method('dispatch');

        $this->stateMachine->apply('confirm', true);
    }

    /** @test */
    public function it_throws_an_exception_if_transition_doesnt_exist_on_apply()
    {
        $this->expectException(SMException::class);

        $this->stateMachine->apply('non-existing-transition');
    }

    /** @test */
    public function it_does_nothing_if_transition_event_is_rejected()
    {
        $guard = $this->createMock(CallbackInterface::class);

        $this->object->expects($this->exactly(4))->method('getState')->willReturn('checkout');
        $this->object->expects($this->never())->method('setState');

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addListener(SMEvents::PRE_TRANSITION, function ($event) {
            $event->setRejected();
        });

        $this->callbackFactory->expects($this->once())->method('get')->with(
            $this->config['callbacks']['guard']['guard-confirm'],
        )->willReturn($guard);

        $guard->expects($this->once())->method('__invoke')->with($this->isInstanceOf(TransitionEvent::class))->willReturn(true);

        $this->stateMachine = new StateMachine($this->object, $this->config, $this->dispatcher, $this->callbackFactory);
        $this->assertFalse($this->stateMachine->apply('confirm'));
    }

    /** @test */
    public function it_does_nothing_if_there_are_no_callbacks_in_the_position()
    {
        $config = $this->config;
        unset($config['callbacks']['guard']);
        $this->stateMachine = new StateMachine($this->object, $config, $this->dispatcher, $this->callbackFactory);

        $this->object->expects($this->exactly(2))->method('getState')->willReturn('checkout');
        $this->object->expects($this->never())->method('setState');

        $this->dispatcher->expects($this->once())->method('dispatch')->with($this->isInstanceOf(TransitionEvent::class), SMEvents::TEST_TRANSITION);
        $this->callbackFactory->expects($this->never())->method('get');

        $this->assertTrue($this->stateMachine->can('create'));
    }

    /** @test */
    public function it_returns_current_state()
    {
        $this->object->expects($this->once())->method('getState')->willReturn('my-state');

        $this->assertEquals('my-state', $this->stateMachine->getState());
    }

    /** @test */
    public function it_returns_current_graph()
    {
        $this->assertEquals($this->config['graph'], $this->stateMachine->getGraph());
    }

    /** @test */
    public function it_returns_current_object()
    {
        $this->assertSame($this->object, $this->stateMachine->getObject());
    }

    /** @test */
    public function it_returns_possible_transitions()
    {
        $guard = $this->createMock(CallbackInterface::class);

        $this->object->expects($this->exactly(7))->method('getState')->willReturn('checkout');

        $this->callbackFactory->expects($this->once())->method('get')->with($this->config['callbacks']['guard']['guard-confirm'])->willReturn($guard);

        $guard->expects($this->exactly(3))->method('__invoke')->with($this->isInstanceOf(TransitionEvent::class))->willReturn(true);

        $this->assertEquals(['create', 'confirm', 'invalid'], array_values($this->stateMachine->getPossibleTransitions()));
    }
}

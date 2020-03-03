<?php

namespace Tests\SM\Callback;

use SM\Callback\CascadeTransitionCallback;
use SM\Event\TransitionEvent;
use SM\Factory\FactoryInterface;
use SM\StateMachine\StateMachineInterface;
use Tests\SM\DummyObject;
use stdClass;

class CascadeTransitionCallbackTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FactoryInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $factory;

    /**
     * @var TransitionEvent&\PHPUnit\Framework\MockObject\MockObject
     */
    private $event;

    /**
     * @var CascadeTransitionCallback
     */
    private $cascadeTransitionCallback;

    public function setUp(): void
    {
        $this->factory = $this->createMock(FactoryInterface::class);
        $this->event = $this->createMock(TransitionEvent::class);
        $this->cascadeTransitionCallback = new CascadeTransitionCallback($this->factory);
    }

    /** @test */
    public function it_applies()
    {
        $object = new DummyObject();
        $sm = $this->createMock(StateMachineInterface::class);

        $this->factory->expects($this->once())->method('get')->with($object, 'graph')->willReturn($sm);
        $sm->expects($this->once())->method('apply')->with('transition', true);

        $this->cascadeTransitionCallback->apply($object, $this->event, 'transition', 'graph');
    }

    /** @test */
    public function it_applies_with_default_graph()
    {
        $object = new DummyObject();
        $sm1 = $this->createMock(StateMachineInterface::class);
        $sm2 = $this->createMock(StateMachineInterface::class);

        $this->event->expects($this->once())->method('getStateMachine')->willReturn($sm2);
        $sm2->method('getGraph')->willReturn('graph');

        $this->factory->expects($this->once())->method('get')->with($object, 'graph')->willReturn($sm1);
        $sm1->expects($this->once())->method('apply')->with('transition', true);

        $this->cascadeTransitionCallback->apply($object, $this->event, 'transition');
    }

    /** @test */
    public function it_applies_with_default_graph_and_default_transition() {
        $object = new DummyObject();
        $sm1 = $this->createMock(StateMachineInterface::class);
        $sm2 = $this->createMock(StateMachineInterface::class);

        $this->event->expects($this->once())->method('getTransition')->willReturn('transition');

        $this->event->expects($this->once())->method('getStateMachine')->willReturn($sm2);
        $sm2->expects($this->once())->method('getGraph')->willReturn('graph');

        $this->factory->expects($this->once())->method('get')->with($object, 'graph')->willReturn($sm1);
        $sm1->expects($this->once())->method('apply')->with('transition', true);

        $this->cascadeTransitionCallback->apply($object, $this->event);
    }
}

<?php

namespace Tests\SM\Callback;

use SM\Event\TransitionEvent;
use SM\StateMachine\StateMachineInterface;

class TransitionEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var StateMachineInterface&\PHPUnit\Framework\MockObject\MockObject $event */
    protected $stateMachine;

    /** @var TransitionEvent */
    protected $event;

    public function setUp(): void
    {
        $this->stateMachine = $this->createMock(StateMachineInterface::class);
        $this->event = new TransitionEvent('tested-transition', 'dummy', ['states' => ['dummy']], $this->stateMachine);
    }

    /** @test */
    public function it_gets_the_transition()
    {
        $this->assertEquals('tested-transition', $this->event->getTransition());
    }

    /** @test */
    public function it_gets_the_configuration()
    {
        $this->assertEquals(['states' => ['dummy']], $this->event->getConfig());
    }

    /** @test */
    public function it_gets_the_statemachine()
    {
        $this->assertSame($this->stateMachine, $this->event->getStateMachine());
    }

    /** @test */
    public function it_gets_the_state()
    {
        $this->assertEquals('dummy', $this->event->getState());
    }

    /** @test */
    public function it_rejects_a_transition()
    {
        $this->event->setRejected();
        $this->assertTrue($this->event->isRejected());

        $this->event->setRejected(true);
        $this->assertTrue($this->event->isRejected());

        $this->event->setRejected(false);
        $this->assertFalse($this->event->isRejected());

    }
}
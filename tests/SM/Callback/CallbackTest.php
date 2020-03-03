<?php

namespace Tests\SM\Callback;

use SM\Callback\Callback;
use SM\Event\TransitionEvent;
use SM\StateMachine\StateMachineInterface;
use Tests\SM\DummyCallable;
use Tests\SM\DummyObject;

class CallbackTest extends \PHPUnit\Framework\TestCase
{
    protected $specs = [];

    /**
     * @var callable&\PHPUnit\Framework\MockObject\MockObject
     */
    protected $callable;

    /**
     * @var StateMachineInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    protected $sm;

    /**
     * @var Callback
     */
    private $callback;

    protected function setUp(): void
    {
        /** @var StateMachineInterface&\PHPUnit\Framework\MockObject\MockObject $sm */
        $this->sm = $this->createMock(StateMachineInterface::class);
        $this->sm->method('getState')->willReturn('checkout');

        $this->callable = $this->createMock(DummyCallable::class, ['__invoke']);
        $this->callback = new Callback($this->specs, $this->callable);
    }

    /** @test */
    public function satisfies_simple_on()
    {
        /** @var TransitionEvent&\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(TransitionEvent::class);
        $event->method('getConfig')->willReturn($this->getConfig(['dummy'], 'dummy'));
        $event->method('getTransition')->willReturn('tested-transition');
        $event->method('getState')->willReturn('dummy');

        $specs = ['on' => 'tested-transition'];
        $this->callback = new Callback($specs, $this->callable);

        $this->assertTrue($this->callback->isSatisfiedBy($event));
    }

    /** @test */
    public function doesnt_satisfy_simple_on()
    {
        /** @var TransitionEvent&\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(TransitionEvent::class);
        $event->method('getConfig')->willReturn($this->getConfig(['dummy'], 'dummy'));
        $event->method('getTransition')->willReturn('tested-transition-not-matching');

        $specs = ['on' => 'tested-transition'];
        $this->callback = new Callback($specs, $this->callable);

        $this->assertFalse($this->callback->isSatisfiedBy($event));
    }

    /** @test */
    public function satisfies_simple_from()
    {
        /** @var TransitionEvent&\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(TransitionEvent::class);
        $event->method('getConfig')->willReturn($this->getConfig(['tested-state'], 'dummy'));
        $event->method('getTransition')->willReturn('dummy');
        $event->method('getState')->willReturn('tested-state');

        $specs = ['from' => 'tested-state'];
        $this->callback = new Callback($specs, $this->callable);

        $this->assertTrue($this->callback->isSatisfiedBy($event));
    }

    /** @test */
    public function doesnt_satisfy_simple_from()
    {
        /** @var TransitionEvent&\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(TransitionEvent::class);
        $event->method('getConfig')->willReturn($this->getConfig(['tested-state-not-matching'], 'dummy'));
        $event->method('getTransition')->willReturn('dummy');
        $event->method('getState')->willReturn('tested-state-not-matching');

        $specs = ['from' => 'tested-state'];
        $this->callback = new Callback($specs, $this->callable);

        $this->assertFalse($this->callback->isSatisfiedBy($event));
    }

    /** @test */
    public function satisfies_simple_to()
    {
        /** @var TransitionEvent&\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(TransitionEvent::class);
        $event->method('getConfig')->willReturn($this->getConfig(['dummy'], 'tested-state'));
        $event->method('getTransition')->willReturn('dummy');
        $event->method('getState')->willReturn('dummy');

        $specs = ['to' => 'tested-state'];
        $this->callback = new Callback($specs, $this->callable);

        $this->assertTrue($this->callback->isSatisfiedBy($event));
    }

    /** @test */
    public function doesnt_satisfy_simple_to()
    {
        /** @var TransitionEvent&\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(TransitionEvent::class);
        $event->method('getConfig')->willReturn($this->getConfig(['tested-state-not-matching'], 'dummy'));
        $event->method('getTransition')->willReturn('dummy');
        $event->method('getState')->willReturn('dummy');

        $specs = ['from' => 'tested-state'];
        $this->callback = new Callback($specs, $this->callable);

        $this->assertFalse($this->callback->isSatisfiedBy($event));
    }

    /** @test */
    public function satisfies_complex_specs()
    {
        /** @var TransitionEvent&\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(TransitionEvent::class);
        $event->method('getConfig')->willReturn($this->getConfig(['tested-state-from'], 'tested-state-to'));
        $event->method('getTransition')->willReturn('tested-transition');
        $event->method('getState')->willReturn('tested-state-from');

        $specs = ['to' => 'tested-state-to', 'from' => 'tested-state-from', 'on' => 'tested-transition'];
        $this->callback = new Callback($specs, $this->callable);

        $this->assertTrue($this->callback->isSatisfiedBy($event));
    }

    /** @test */
    public function doesnt_satisfy_wrong_from()
    {
        /** @var TransitionEvent&\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(TransitionEvent::class);
        $event->method('getConfig')->willReturn($this->getConfig(['dummy'], 'tested-state-to'));
        $event->method('getTransition')->willReturn('tested-transition');
        $event->method('getState')->willReturn('dummy');

        $specs = ['to' => 'tested-state-to', 'from' => 'tested-wrong', 'on' => 'tested-transition'];
        $this->callback = new Callback($specs, $this->callable);

        $this->assertFalse($this->callback->isSatisfiedBy($event));
    }

    /** @test */
    public function doesnt_satisfy_wrong_to()
    {
        /** @var TransitionEvent&\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(TransitionEvent::class);
        $event->method('getConfig')->willReturn($this->getConfig(['tested-state-from'], 'dummy'));
        $event->method('getTransition')->willReturn('tested-transition');
        $event->method('getState')->willReturn('tested-state-from');

        $specs = ['to' => 'tested-wrong', 'from' => 'tested-state-from', 'on' => 'tested-transition'];
        $this->callback = new Callback($specs, $this->callable);

        $this->assertFalse($this->callback->isSatisfiedBy($event));
    }

    /** @test */
    public function doesnt_satisfy_wrong_on()
    {
        /** @var TransitionEvent&\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(TransitionEvent::class);
        $event->method('getConfig')->willReturn($this->getConfig(['tested-state-from'], 'tested-state-to'));
        $event->method('getTransition')->willReturn('dummy');
        $event->method('getState')->willReturn('tested-state-from');

        $specs = ['to' => 'tested-state-to', 'from' => 'tested-state-from', 'on' => 'tested-wrong'];
        $this->callback = new Callback($specs, $this->callable);

        $this->assertFalse($this->callback->isSatisfiedBy($event));
    }

    /** @test */
    public function doesnt_satisfy_excluded_from()
    {
        /** @var TransitionEvent&\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(TransitionEvent::class);
        $event->method('getConfig')->willReturn($this->getConfig(['tested-state-from'], 'tested-state-to'));
        $event->method('getTransition')->willReturn('dummy');
        $event->method('getState')->willReturn('tested-state-from');

        $specs = ['to' => 'tested-state-to', 'excluded_from' => 'tested-state-from'];
        $this->callback = new Callback($specs, $this->callable);

        $this->assertFalse($this->callback->isSatisfiedBy($event));
    }

    /** @test */
    public function doesnt_call()
    {
        /** @var TransitionEvent&\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(TransitionEvent::class);
        $event->method('getConfig')->willReturn($this->getConfig(['dummy'], 'dummy'));
        $event->method('getTransition')->willReturn('tested-transition-not-matching');
        $event->method('getState')->willReturn('dummy');

        $specs = ['on' => 'tested-transition'];
        $this->callback = new Callback($specs, $this->callable);

        $this->callable->expects($this->never())->method('__invoke');

        $this->assertTrue(($this->callback)($event));
    }

    /** @test */
    public function call_without_args()
    {
        /** @var TransitionEvent&\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(TransitionEvent::class);
        $event->method('getConfig')->willReturn($this->getConfig(['dummy'], 'dummy'));
        $event->method('getTransition')->willReturn('tested-transition');
        $event->method('getState')->willReturn('dummy');

        $specs = ['on' => 'tested-transition'];
        $this->callback = new Callback($specs, $this->callable);

        $this->callable->expects($this->once())->method('__invoke')->with($event)->willReturn(true);

        $this->assertTrue(($this->callback)($event));
    }

    /** @test */
    public function call_with_object_callable()
    {
        /** @var TransitionEvent&\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(TransitionEvent::class);
        $event->method('getConfig')->willReturn($this->getConfig(['dummy'], 'dummy'));
        $event->method('getTransition')->willReturn('tested-transition');
        $event->method('getState')->willReturn('dummy');
        $event->method('getStateMachine')->willReturn($this->sm);

        /** @var DummyObject&\PHPUnit\Framework\MockObject\MockObject $object */
        $object = $this->createMock(DummyObject::class);
        $object->expects($this->once())->method('getState')->with($event)->willReturn(true);
        $this->sm->method('getObject')->willReturn($object);

        $specs = ['on' => 'tested-transition'];
        $this->callback = new Callback($specs, ['object', 'getState']);

        $this->assertTrue(($this->callback)($event));
    }

    /** @test */
    public function call_with_object_instance_callable()
    {
        /** @var TransitionEvent&\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(TransitionEvent::class);
        $event->method('getConfig')->willReturn($this->getConfig(['dummy'], 'dummy'));
        $event->method('getTransition')->willReturn('tested-transition');
        $event->method('getState')->willReturn('dummy');
        $event->method('getStateMachine')->willReturn($this->sm);

        /** @var DummyObject&\PHPUnit\Framework\MockObject\MockObject $object */
        $object = $this->createMock(DummyObject::class);
        $object->expects($this->once())->method('getState')->with($event)->willReturn(true);
        $this->sm->method('getObject')->willReturn($object);

        $specs = ['on' => 'tested-transition'];
        $this->callback = new Callback($specs, [$object, 'getState']);

        $this->assertTrue(($this->callback)($event));
    }

    /** @test */
    public function call_with_object_property_callable()
    {
        /** @var TransitionEvent&\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(TransitionEvent::class);
        $event->method('getConfig')->willReturn($this->getConfig(['dummy'], 'dummy'));
        $event->method('getTransition')->willReturn('tested-transition');
        $event->method('getState')->willReturn('dummy');
        $event->method('getStateMachine')->willReturn($this->sm);

        /** @var DummyObject&\PHPUnit\Framework\MockObject\MockObject $object */
        $object = $this->createMock(DummyObject::class);
        $object->expects($this->once())->method('getState')->willReturn($object);
        $object->expects($this->once())->method('setState')->with($event)->willReturn(true);
        $this->sm->method('getObject')->willReturn($object);

        $specs = ['on' => 'tested-transition'];
        $this->callback = new Callback($specs, ['object.state', 'setState']);

        $this->assertTrue(($this->callback)($event));
    }

    /** @test */
    public function call_with_args()
    {
        /** @var TransitionEvent&\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(TransitionEvent::class);
        $event->method('getConfig')->willReturn($this->getConfig(['dummy'], 'dummy'));
        $event->method('getTransition')->willReturn('tested-transition');
        $event->method('getState')->willReturn('dummy');
        $event->method('getStateMachine')->willReturn($this->sm);

        $specs = ['on' => 'tested-transition', 'args' => ['event', 'object', '"dummy"', ['dummy']]];
        $this->callback = new Callback($specs, $this->callable);

        /** @var DummyObject&\PHPUnit\Framework\MockObject\MockObject $object */
        $object = $this->createMock(DummyObject::class);
        $this->sm->method('getObject')->willReturn($object);
        $this->callable->expects($this->once())->method('__invoke')->with($event, $object, 'dummy', ['dummy'])->willReturn(true);

        $this->assertTrue(($this->callback)($event));
    }

    protected function getConfig(array $from = [], $to): array
    {
        return ['from' => $from, 'to' => $to];
    }
}

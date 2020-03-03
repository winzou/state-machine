<?php

namespace Tests\SM\Callback;

use SM\Callback\Callback;
use SM\Callback\CallbackFactory;
use SM\Event\TransitionEvent;
use SM\SMException;
use Tests\SM\DummyCallable;

class CallbackFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        // $this->factory = new CallbackFactory();
    }

    /** @test */
    function throws_exception_when_callback_class_doesnt_exist()
    {
        $this->expectException(SMException::class);
        new CallbackFactory('InvalidCallback');
    }

    /** @test */
    public function it_gets_the_callback_object()
    {
        /** @var TransitionEvent&\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(TransitionEvent::class);
        $event->method('getConfig')->willReturn(['to' => 'dummy']);
        $event->method('getTransition')->willReturn('tested-transition');
        $event->method('getState')->willReturn('dummy');

        /** @var DummyCallable&\PHPUnit\Framework\MockObject\MockObject $callable */
        $callable = $this->createMock(DummyCallable::class);
        $callable->expects($this->once())->method('__invoke')->with($event)->willReturn(True);

        $callbackFactory = new CallbackFactory(Callback::class);
        $callback = $callbackFactory->get(['on' => 'tested-transition', 'to' => 'dummy', 'do' => $callable]);

        $this->assertInstanceOf(Callback::class, $callback);
        $this->assertTrue($callback($event));
    }

    /** @test */
    function throws_exception_when_specs_doesnt_have_do_index()
    {
        $callbackFactory = new CallbackFactory(Callback::class);

        $this->expectException(SMException::class);
        $callbackFactory->get([]);
    }
}
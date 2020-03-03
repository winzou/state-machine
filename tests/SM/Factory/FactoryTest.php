<?php

namespace Tests\SM\Factory;

use SM\Callback\CallbackFactoryInterface;
use SM\Factory\Factory;
use SM\SMException;
use SM\StateMachine\StateMachine;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tests\SM\DummyObject;

class FactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array
     */
    protected $configs = [
        'graph1' => ['state_machine_class' => StateMachine::class, 'class' => DummyObject::class],
        'graph2' => ['class' => DummyObject::class],
        'graph3' => ['state_machine_class' => 'InvalidStateMachine', 'class' => DummyObject::class],
    ];

    /**
     * @var Factory
     */
    private $factory;

    protected function setUp(): void
    {
        /** @var EventDispatcherInterface&\PHPUnit\Framework\MockObject\MockObject $dispatcher */
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        /** @var CallbackFactoryInterface&\PHPUnit\Framework\MockObject\MockObject $callbackFactory */
        $callbackFactory = $this->createMock(CallbackFactoryInterface::class);

        $this->factory = new Factory($this->configs, $dispatcher, $callbackFactory);
    }

    /** @test */
    function creates_statemachine()
    {
        /** @var DummyObject|\PHPUnit\Framework\MockObject\MockObject $object */
        $object = $this->createMock(DummyObject::class);
        $graph = 'graph1';

        $this->assertInstanceOf($this->configs[$graph]['state_machine_class'], $this->factory->get($object, $graph));
    }

    /** @test */
    public function clears_statemachines()
    {
        /** @var DummyObject|\PHPUnit\Framework\MockObject\MockObject $object */
        $object = $this->createMock(DummyObject::class);

        $stateMachineA = $this->factory->get($object, 'graph1');
        $stateMachineB = $this->factory->get($object, 'graph1');
        $this->assertSame($stateMachineA, $stateMachineB);

        $this->factory->clear();
        $stateMachineC = $this->factory->get($object, 'graph1');
        $this->assertNotSame($stateMachineA, $stateMachineC);
        $this->assertNotSame($stateMachineB, $stateMachineC);
    }

    /** @test */
    function creates_statemachine_with_default_class()
    {
        /** @var DummyObject&\PHPUnit\Framework\MockObject\MockObject $object */
        $object = $this->createMock(DummyObject::class);

        $this->assertInstanceOf(StateMachine::class, $this->factory->get($object, 'graph2'));
    }

    /** @test */
    function throws_exception_when_statemachine_class_doesnt_exist()
    {
        /** @var DummyObject&\PHPUnit\Framework\MockObject\MockObject $object */
        $object = $this->createMock(DummyObject::class);

        $this->expectException(SMException::class);
        $this->factory->get($object, 'graph3');
    }

    /** @test */
    function throws_exception_when_configuration_doesnt_exist()
    {
        /** @var DummyObject&\PHPUnit\Framework\MockObject\MockObject $object */
        $object = $this->createMock(DummyObject::class);

        $this->expectException(SMException::class);
        $this->factory->get($object);
    }

    /** @test */
    function throws_exception_when_configuration_doesnt_have_a_class()
    {
        $this->expectException(SMException::class);
        $this->factory->addConfig(['state_machine_class' => StateMachine::class], 'graph4');
    }
}

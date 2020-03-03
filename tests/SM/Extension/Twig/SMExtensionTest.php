<?php

namespace Tests\SM\Extension\Twig;

use SM\Extension\Twig\SMExtension;
use SM\Factory\FactoryInterface;
use SM\StateMachine\StateMachineInterface;
use Tests\SM\DummyObject;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFunction;

class SMExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SMExtension
     */
    private $sMExtension;

    /**
     * @var FactoryInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $factory;

    /**
     * @var StateMachineInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $stateMachine;

    protected function setUp(): void
    {
        $this->factory = $this->createMock(FactoryInterface::class);
        $this->stateMachine = $this->createMock(StateMachineInterface::class);
        $this->sMExtension = new SMExtension($this->factory);
    }

    /** @test */
    function a_twig_extension()
    {
        $this->assertInstanceOf(ExtensionInterface::class, $this->sMExtension);
    }

    /** @test */
    public function get_functions()
    {
        $functions = $this->sMExtension->getFunctions();

        $this->assertCount(3, $functions);
        $this->assertContainsOnlyInstancesOf(TwigFunction::class, $functions);

        $this->assertEquals('sm_can', $functions[0]->getName());
        $this->assertEquals([$this->sMExtension, 'can'], $functions[0]->getCallable());

        $this->assertEquals('sm_state', $functions[1]->getName());
        $this->assertEquals([$this->sMExtension, 'getState'], $functions[1]->getCallable());

        $this->assertEquals('sm_possible_transitions', $functions[2]->getName());
        $this->assertEquals([$this->sMExtension, 'getPossibleTransitions'], $functions[2]->getCallable());
    }

    /** @test */
    function provide_sm_can_function()
    {
        $object = new DummyObject();

        $this->factory->expects($this->once())->method('get')->with($object, 'simple')->willReturn($this->stateMachine);
        $this->stateMachine->expects($this->once())->method('can')->with('new')->willReturn(true);

        $this->sMExtension->can($object, 'new', 'simple');
    }

    /** @test */
    function provide_sm_get_state_function()
    {
        $object = new DummyObject();

        $this->factory->expects($this->once())->method('get')->with($object, 'simple')->willReturn($this->stateMachine);
        $this->stateMachine->expects($this->once())->method('getState')->willReturn('new');

        $this->sMExtension->getState($object, 'simple');
    }

    /** @test */
    function provide_sm_get_possible_transitions_function()
    {
        $object = new DummyObject();

        $this->factory->expects($this->once())->method('get')->with($object, 'simple')->willReturn($this->stateMachine);
        $this->stateMachine->expects($this->once())->method('getPossibleTransitions')->willReturn([]);

        $this->sMExtension->getPossibleTransitions($object, 'simple');
    }
}

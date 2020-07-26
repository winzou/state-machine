<?php

namespace spec\SM\Extension\Twig;

use PhpSpec\ObjectBehavior;
use SM\Extension\Twig\SMExtension;
use SM\Factory\FactoryInterface;
use SM\StateMachine\StateMachineInterface;
use spec\SM\DummyObject;
use Twig\Extension\ExtensionInterface;

class SMExtensionSpec extends ObjectBehavior
{
    function let(FactoryInterface $factory, StateMachineInterface $stateMachine)
    {
        $this->beConstructedWith($factory);
        $factory->get(new DummyObject(), 'simple')->willReturn($stateMachine);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(SMExtension::class);
    }

    function it_is_a_twig_extension()
    {
        $this->shouldImplement(ExtensionInterface::class);
    }
}

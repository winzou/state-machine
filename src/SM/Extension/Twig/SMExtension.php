<?php

/*
 * This file is part of the StateMachine package.
 *
 * (c) Alexandre Bacco
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SM\Extension\Twig;

use SM\Factory\FactoryInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SMExtension extends AbstractExtension
{
    /** @var FactoryInterface */
    protected $factory;

    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return array(
            new TwigFunction('sm_can', array($this, 'can')),
            new TwigFunction('sm_state', array($this, 'getState')),
            new TwigFunction('sm_possible_transitions', array($this, 'getPossibleTransitions')),
        );
    }

    /**
     * @throws \SM\SMException
     */
    public function can($object, string $transition, string $graph = 'default'): bool
    {
        return $this->factory->get($object, $graph)->can($transition);
    }

    /**
     * @throws \SM\SMException
     */
    public function getState($object, string $graph = 'default'): string
    {
        return $this->factory->get($object, $graph)->getState();
    }

    /**
     * @throws \SM\SMException
     */
    public function getPossibleTransitions($object, string $graph = 'default'): array
    {
        return $this->factory->get($object, $graph)->getPossibleTransitions();
    }
}

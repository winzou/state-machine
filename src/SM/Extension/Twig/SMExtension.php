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
    /**
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @param FactoryInterface $factory
     */
    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('sm_can', [$this, 'can']),
            new TwigFunction('sm_state', [$this, 'getState']),
            new TwigFunction('sm_possible_transitions', [$this, 'getPossibleTransitions']),
        ];
    }

    /**
     * @param object $object
     * @param string $transition
     * @param string $graph
     *
     * @return bool
     */
    public function can(object $object, $transition, string $graph = 'default'): bool
    {
        return $this->factory->get($object, $graph)->can($transition);
    }

    /**
     * @param object $object
     * @param string $graph
     *
     * @return string
     */
    public function getState(object $object, string $graph = 'default')
    {
        return $this->factory->get($object, $graph)->getState();
    }

    /**
     * @param object $object
     * @param string $graph
     *
     * @return array<string>
     */
    public function getPossibleTransitions(object $object, string $graph = 'default'): array
    {
        return $this->factory->get($object, $graph)->getPossibleTransitions();
    }
}

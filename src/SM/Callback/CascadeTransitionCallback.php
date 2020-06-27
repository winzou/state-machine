<?php

/*
 * This file is part of the StateMachine package.
 *
 * (c) Alexandre Bacco
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SM\Callback;

use SM\Event\TransitionEvent;
use SM\Factory\FactoryInterface;

/**
 * Add the ability to cascade a transition to a different graph or different object via a simple callback
 *
 * @author Alexandre Bacco <alexandre.bacco@gmail.com>
 */
class CascadeTransitionCallback
{
    /** @var FactoryInterface */
    protected $factory;

    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Apply a transition to the object that has just undergone a transition
     *
     * @param iterable|array     $objects    Object or array|iterable of objects to apply the transition on
     * @param TransitionEvent    $event      Transition event
     * @param string|null        $transition Transition that is to be applied (if null, same as the trigger)
     * @param string|null        $graph      Graph on which the new transition will apply (if null, same as the trigger)
     * @param bool               $soft       If true, check if it can apply the transition first (no Exception thrown)
     *
     * @throws \SM\SMException
     */
    public function apply($objects, TransitionEvent $event, string $transition = null, string $graph = null, bool $soft = true): void
    {
        if (!is_iterable($objects)) {
            $objects = array($objects);
        }

        if (null === $transition) {
            $transition = $event->getTransition();
        }

        if (null === $graph) {
            $graph = $event->getStateMachine()->getGraph();
        }

        foreach ($objects as $object) {
            $this->factory->get($object, $graph)->apply($transition, $soft);
        }
    }
}

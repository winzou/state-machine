<?php

/*
 * This file is part of the StateMachine package.
 *
 * (c) Alexandre Bacco
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SM\StateMachine;

use SM\SMException;

interface StateMachineInterface
{
    /**
     * Can the transition be applied on the underlying object
     *
     * @param string $transition
     *
     * @return bool
     *
     * @throws SMException If transition doesn't exist
     */
    public function can($transition): bool;

    /**
     * Applies the transition on the underlying object
     *
     * @param string $transition Transition to apply
     * @param bool   $soft       Soft means do nothing if transition can't be applied (no exception thrown)
     *
     * @return bool If the transition has been applied or not (in case of soft apply or rejected pre transition event)
     *
     * @throws SMException If transition can't be applied or doesn't exist
     */
    public function apply($transition, bool $soft = false): bool;

    /**
     * Returns the current state
     *
     * @return string
     */
    public function getState();

    /**
     * Returns the underlying object
     *
     * @return object
     */
    public function getObject(): object;

    /**
     * Returns the current graph
     *
     * @return string
     */
    public function getGraph(): string;

    /**
     * Returns the possible transitions
     *
     * @return string[]
     */
    public function getPossibleTransitions(): array;
}

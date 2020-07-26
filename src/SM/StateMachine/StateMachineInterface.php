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
    public function can(string $transition): bool;

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
    public function apply(string $transition, $soft = false): bool;

    /**
     * Returns the current state
     */
    public function getState(): string;

    /**
     * Returns the underlying object
     *
     * @return object
     */
    public function getObject();

    /**
     * Returns the current graph
     */
    public function getGraph(): string;

    /**
     * Returns the possible transitions
     */
    public function getPossibleTransitions(): array;
}

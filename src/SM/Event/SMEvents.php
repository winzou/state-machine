<?php

/*
 * This file is part of the StateMachine package.
 *
 * (c) Alexandre Bacco
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SM\Event;

abstract class SMEvents
{
    public const PRE_TRANSITION  = 'winzou.state_machine.pre_transition';
    public const POST_TRANSITION = 'winzou.state_machine.post_transition';
    public const TEST_TRANSITION = 'winzou.state_machine.test_transition';
}

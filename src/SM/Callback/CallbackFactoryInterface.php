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

use SM\SMException;

interface CallbackFactoryInterface
{
    /**
     * Return an instance of CallbackInterface loaded with the given $specs
     *
     * @param array $specs
     *
     * @return CallbackInterface
     *
     * @throws SMException
     */
    public function get(array $specs): CallbackInterface;
}

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

use SM\Callback\CallbackFactory;
use SM\Callback\CallbackFactoryInterface;
use SM\Callback\CallbackInterface;
use SM\Event\SMEvents;
use SM\Event\TransitionEvent;
use SM\SMException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class StateMachine implements StateMachineInterface
{
    /**
     * @var object
     */
    protected $object;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var CallbackFactoryInterface
     */
    protected $callbackFactory;

    /**
     * @param object                   $object          Underlying object for the state machine
     * @param array                    $config          Config array of the graph
     * @param EventDispatcherInterface $dispatcher      EventDispatcher or null not to dispatch events
     * @param CallbackFactoryInterface $callbackFactory CallbackFactory or null to use the default one
     *
     * @throws SMException If object doesn't have configured property path for state
     */
    public function __construct(
        $object,
        array $config,
        EventDispatcherInterface $dispatcher      = null,
        CallbackFactoryInterface $callbackFactory = null
    ) {
        $this->object          = $object;
        $this->dispatcher      = $dispatcher;
        $this->callbackFactory = $callbackFactory ?: new CallbackFactory('SM\Callback\Callback');

        if (!isset($config['property_path'])) {
            $config['property_path'] = 'state';
        }

        if (!isset($config['ignore_before_callback_result'])) {
            $config['ignore_before_callback_result'] = true;
        }

        $this->config = $config;

        // Test if the given object has the given state property path
        try {
            $this->getState();
        } catch (NoSuchPropertyException $e) {
            throw new SMException(sprintf(
                'Cannot access to configured property path %s on object %s with graph %s',
                $config['property_path'],
                get_class($object),
                $config['graph']
            ));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function can($transition)
    {
        if (!isset($this->config['transitions'][$transition])) {
            throw new SMException(sprintf(
                'Transition %s does not exist on object %s with graph %s',
                $transition,
                get_class($this->object),
                $this->config['graph']
            ));
        }

        if (!in_array($this->getState(), $this->config['transitions'][$transition]['from'])) {
            return false;
        }

        if (null !== $this->dispatcher) {
            $event = new TransitionEvent($transition, $this->getState(), $this->config['transitions'][$transition], $this);
            $this->dispatcher->dispatch(SMEvents::TEST_TRANSITION, $event);

            return !$event->isRejected();
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function apply($transition, $soft = false)
    {
        if (!$this->can($transition)) {
            if ($soft) {
                return false;
            }

            throw new SMException(sprintf(
                'Transition %s cannot be applied on state %s of object %s with graph %s',
                $transition,
                $this->getState(),
                get_class($this->object),
                $this->config['graph']
            ));
        }

        $event = new TransitionEvent($transition, $this->getState(), $this->config['transitions'][$transition], $this);

        if (null !== $this->dispatcher) {
            $this->dispatcher->dispatch(SMEvents::PRE_TRANSITION, $event);
        }

        if (false === ($callbackResult = $this->callCallbacks($event, 'before'))
            && false === $this->config['ignore_before_callback_result']
        ) {
            if (!isset($this->config['transitions'][$transition]['on_fail'])
                || false === $this->config['transitions'][$transition]['on_fail']
            ) {
                throw new SMException(sprintf(
                    'Missing "on_fail" option in transition %s to object %s on graph %s.',
                    $transition,
                    get_class($this->object),
                    $this->config['graph']
                ));
            }

            $this->setState($this->config['transitions'][$transition]['on_fail']);

            return false;
        }

        $this->setState($this->config['transitions'][$transition]['to']);

        $this->callCallbacks($event, 'after');

        if (null !== $this->dispatcher) {
            $this->dispatcher->dispatch(SMEvents::POST_TRANSITION, $event);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getState()
    {
        $accessor = new PropertyAccessor();
        return $accessor->getValue($this->object, $this->config['property_path']);
    }

    /**
     * {@inheritDoc}
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * {@inheritDoc}
     */
    public function getGraph()
    {
        return $this->config['graph'];
    }

    /**
     * {@inheritDoc}
     */
    public function getPossibleTransitions()
    {
        return array_filter(
            array_keys($this->config['transitions']),
            array($this, 'can')
        );
    }

    /**
     * Set a new state to the underlying object
     *
     * @param string $state
     *
     * @throws SMException
     */
    protected function setState($state)
    {
        if (!in_array($state, $this->config['states'])) {
            throw new SMException(sprintf(
                'Cannot set the state to %s to object %s with graph %s because it is not pre-defined.',
                $state,
                get_class($this->object),
                $this->config['graph']
            ));
        }

        $accessor = new PropertyAccessor();
        $accessor->setValue($this->object, $this->config['property_path'], $state);
    }

    /**
     * Builds and calls the defined callbacks
     *
     * @param TransitionEvent $event
     * @param string          $position
     *
     * @return true
     */
    protected function callCallbacks(TransitionEvent $event, $position)
    {
        $result = true;
        if (!isset($this->config['callbacks'][$position])) {
            return $result;
        }

        foreach ($this->config['callbacks'][$position] as &$callback) {
            if (!$callback instanceof CallbackInterface) {
                $callback = $this->callbackFactory->get($callback);
            }

            if (null !== $callbackResult = call_user_func($callback, $event)) {
                $result = $result && $callbackResult;
            }
        }

        return $result;
    }
}

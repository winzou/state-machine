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

        $this->config = $config;

        // Test if the given object has the given state property path
        try {
            $this->getState();
        } catch (NoSuchPropertyException $e) {
            throw new SMException(sprintf(
               'Cannot access to configured property path "%s" on object %s with graph "%s"',
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
                'Transition "%s" does not exist on object "%s" with graph "%s"',
                $transition,
                get_class($this->object),
                $this->config['graph']
            ));
        }

        $transitionConfig = $this->getTransitionFromState($transition);

        $can = true;

        $event = new TransitionEvent($transition, $this->getState(), $transitionConfig, $this);

        if (null !== $this->dispatcher) {
            $this->dispatcher->dispatch(SMEvents::TEST_TRANSITION, $event);

            $can = !$event->isRejected();
        }

        return $can && $this->callCallbacks($event, 'guard');
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
                'Transition "%s" cannot be applied on state "%s" of object "%s" with graph "%s"',
                $transition,
                $this->getState(),
                get_class($this->object),
                $this->config['graph']
            ));
        }

        $transitionConfig = $this->getTransitionFromState($transition);

        $event = new TransitionEvent($transition, $this->getState(), $transitionConfig, $this);

        if (null !== $this->dispatcher) {
            $this->dispatcher->dispatch(SMEvents::PRE_TRANSITION, $event);

            if ($event->isRejected()) {
                return false;
            }
        }

        $this->callCallbacks($event, 'before');

        $this->setState($transitionConfig['to']);

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
                'Cannot set the state to "%s" to object "%s" with graph %s because it is not pre-defined.',
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
     * @param string $position
     * @return bool
     */
    protected function callCallbacks(TransitionEvent $event, $position)
    {
        $callbacks = [];

        // Callbacks can come from two places. Either they are set on the
        // specific transition or the can be set in a more general callbacks
        // array. Somewhat arbitrarily I have chosen to always execute the
        // more specific callbacks first and then the global ones.
        if (isset($event->getConfig()[$position])) {
            $callbacks[] = ['do' => $event->getConfig()[$position] ];
        }

        // Load global callbacks
        if (isset($this->config['callbacks'][$position][$event->getTransition()]['do'])) {
            $callbacks[] =[ 'do' => $this->config['callbacks'][$position][$event->getTransition()]['do'] ];
        }

        $result = true;

        foreach ($callbacks as &$callback) {
            if (!$callback instanceof CallbackInterface) {
                $callback = $this->callbackFactory->get($callback);
            }

            $result = call_user_func($callback, $event) && $result;
        }

        return $result;
    }

    /**
     * Determines the appropriate transition configuration given the current
     * state of the object
     *
     * @param  string $transition text representation of transition
     * @return array
     * @throws SMException will be thrown if no suitable transition can be found
     */
    private function getTransitionFromState( $transition )
    {
        $transitionConfig = $this->config['transitions'][$transition];

        // We have one of two things coming in. Either it's a hash with from
        // and to values or it's an array of hashes (which we'll need to loop
        // through). This if statements will tell us if it's the latter
        if (array_keys($transitionConfig) === range(0, count($transitionConfig) - 1)) {
            foreach ($transitionConfig as $transition) {
                if (in_array($this->getState(), $transition)) {
                    return $transition;
                }
            }
        }
        elseif (isset($this->config['transitions'][$transition]['from'])) {
            return $this->config['transitions'][$transition];
        }

        // Having looped through all the transitions of this name and not found
        // one that starts on the objects current state throw an exception
        throw new SMException(sprintf(
            'Transition "%s" does not exist on object "%s" with graph "%s"',
            $transition,
            get_class($this->object),
            $this->config['graph']
        ));
    }
}

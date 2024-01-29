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

use SM\Callback\Callback;
use SM\Callback\CallbackFactory;
use SM\Callback\CallbackFactoryInterface;
use SM\Callback\CallbackInterface;
use SM\Event\SMEvents;
use SM\Event\TransitionEvent;
use SM\SMException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class StateMachine implements StateMachineInterface
{
    /** @var object */
    protected $object;

    /** @var array */
    protected $config = [];

    /** @var ?EventDispatcherInterface */
    protected $dispatcher;

    /** @var CallbackFactoryInterface */
    protected $callbackFactory;

    /** @var ?string */
    protected $enumClass;

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
        $this->callbackFactory = $callbackFactory ?: new CallbackFactory(Callback::class);

        if (!isset($config['property_path'])) {
            $config['property_path'] = 'state';
        }

        $this->config = $config;

        $this->setEnumClass($config['enumClass'] ?? null);

        // Test if the given object has the given state property path
        if (!$this->hasObjectProperty($object, $config['property_path'])) {
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
    public function can(string $transition): bool
    {
        if (!isset($this->config['transitions'][$transition])) {
            throw new SMException(sprintf(
                'Transition "%s" does not exist on object "%s" with graph "%s"',
                $transition,
                get_class($this->object),
                $this->getGraph()
            ));
        }

        if (!in_array($this->getState(), $this->config['transitions'][$transition]['from'], true)) {
            return false;
        }

        $can = true;
        $event = new TransitionEvent($transition, $this->getState(), $this->config['transitions'][$transition], $this);
        if (null !== $this->dispatcher) {
            $this->dispatcher->dispatch($event, SMEvents::TEST_TRANSITION);

            $can = !$event->isRejected();
        }

        return $can && $this->callCallbacks($event, 'guard');
    }

    /**
     * {@inheritDoc}
     */
    public function apply(string $transition, $soft = false): bool
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
                $this->getGraph()
            ));
        }

        $event = new TransitionEvent($transition, $this->getState(), $this->config['transitions'][$transition], $this);

        if (null !== $this->dispatcher) {
            $this->dispatcher->dispatch($event, SMEvents::PRE_TRANSITION);

            if ($event->isRejected()) {
                return false;
            }
        }

        $this->callCallbacks($event, 'before');

        $this->setState($this->config['transitions'][$transition]['to']);

        $this->callCallbacks($event, 'after');

        if (null !== $this->dispatcher) {
            $this->dispatcher->dispatch($event, SMEvents::POST_TRANSITION);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getState(): string
    {
        $accessor = new PropertyAccessor();
        $state = $accessor->getValue($this->object, $this->config['property_path']);

        if (($enumClass = $this->getEnumClass()) && is_a($state, $enumClass, true)) {
            return (string) $state->value;
        }

        return $state;
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
    public function getGraph(): string
    {
        return $this->config['graph'];
    }

    /**
     * {@inheritDoc}
     */
    public function getPossibleTransitions(): array
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
    protected function setState($state): void
    {
        if (!in_array($state, $this->config['states'], true)) {
            throw new SMException(sprintf(
                'Cannot set the state to "%s" to object "%s" with graph %s because it is not pre-defined.',
                $state,
                get_class($this->object),
                $this->getGraph()
            ));
        }

        if ($enumClass = $this->getEnumClass()) {
            $state = $enumClass::from($state);
        }

        $accessor = new PropertyAccessor();
        $accessor->setValue($this->object, $this->config['property_path'], $state);
    }

    /**
     * Builds and calls the defined callbacks
     */
    protected function callCallbacks(TransitionEvent $event, string $position): bool
    {
        if (!isset($this->config['callbacks'][$position])) {
            return true;
        }

        $result = true;
        foreach ($this->config['callbacks'][$position] as &$callback) {
            if (!$callback instanceof CallbackInterface) {
                $callback = $this->callbackFactory->get($callback);
            }

            $result = call_user_func($callback, $event) && $result;
        }
        return $result;
    }

    protected function hasObjectProperty($object, string $property): bool
    {
        return (new PropertyAccessor())->isReadable($object, $property);
    }

    public function getEnumClass(): ?string
    {
        return $this->enumClass;
    }

    public function setEnumClass(?string $class): self
    {
        if ($class && !is_a($class, \BackedEnum::class, true)) {
            throw new SMException('Enum class must be a BackedEnum');
        }

        $this->enumClass = $class;
        
        return $this;
    }
}

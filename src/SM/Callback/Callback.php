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
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Callback implements CallbackInterface
{
    /**
     * @var array<string,array>
     */
    protected $specs = [];

    /**
     * @var mixed
     */
    protected $callable;

    /**
     * @param array<string,string|array> $specs    Specification for the Callback to be called
     * @param mixed                      $callable Closure or Callable that will be called if specifications pass
     */
    public function __construct(array $specs, $callable)
    {
        foreach (['from', 'to', 'on', 'excluded_from', 'excluded_to', 'excluded_on'] as $clause) {
            if (!isset($specs[$clause])) {
                $specs[$clause] = [];
            } elseif (!is_array($specs[$clause])) {
                $specs[$clause] = [$specs[$clause]];
            }
        }

        $this->specs = $specs;
        $this->callable = $callable;
    }

    /**
     * @param TransitionEvent $event
     *
     * @return mixed The returned value from the callback
     */
    public function call(TransitionEvent $event)
    {
        if (!isset($this->specs['args'])) {
            $args = [$event];
        } else {
            $expr = new ExpressionLanguage();
            $args = array_map(
                function($arg) use($expr, $event) {
                    if (!is_string($arg)) {
                        return $arg;
                    }

                    return $expr->evaluate($arg, [
                        'object' => $event->getStateMachine()->getObject(),
                        'event'  => $event,
                    ]);
                }, $this->specs['args']
            );
        }

        $callable = $this->filterCallable($this->callable, $event);

        return call_user_func_array($callable, $args);
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(TransitionEvent $event)
    {
        if ($this->isSatisfiedBy($event)) {
            return $this->call($event);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isSatisfiedBy(TransitionEvent $event): bool
    {
        $config = $event->getConfig();

        return
            $this->isSatisfiedByClause('on', $event->getTransition()) &&
            $this->isSatisfiedByClause('from', $event->getState()) &&
            $this->isSatisfiedByClause('to', $config['to'])
        ;
    }

    /**
     * @param string $clause The clause to check (on, from or to)
     * @param string $value  The value to check the clause against
     *
     * @return bool
     */
    protected function isSatisfiedByClause(string $clause, string $value): bool
    {
        if (0 < count($this->specs[$clause]) && !in_array($value, $this->specs[$clause])) {
            return false;
        }

        if (0 < count($this->specs['excluded_'.$clause]) && in_array($value, $this->specs['excluded_'.$clause])) {
            return false;
        }

        return true;
    }

    /**
     * @param callable|array{object|string,string}  $callable A callable or array with index 0 starting with "object" that will evaluated as a property path with "object" being the object undergoing the transition
     * @param TransitionEvent $event
     */
    protected function filterCallable($callable, TransitionEvent $event): callable
    {
        if (!is_array($callable)) {
            return $callable;
        }

        [$class, $method] = $callable;

        if (!is_string($class) || 'object' !== substr($class, 0, 6)) {
            return $callable;
        }

        $object = $event->getStateMachine()->getObject();

        // callable could be "object.property" and not just "object", so we evaluate the "property" path
        if ('object' !== $class) {
            $accessor = PropertyAccess::createPropertyAccessor();
            $object = $accessor->getValue($object, substr($class, 7));
        }

        return [$object, $method];
    }
}

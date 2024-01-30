<?php

/*
 * This file is part of the StateMachine package.
 *
 * (c) Alexandre Bacco
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SM\Factory;

use SM\SMException;
use SM\StateMachine\StateMachineInterface;

abstract class AbstractFactory implements ClearableFactoryInterface
{
    /** @var array */
    protected $configs = [];

    /** @var StateMachineInterface[] */
    protected $stateMachines = [];

    /**
     * @param array $configs Array of configs for the available state machines
     *
     * @throws SMException
     */
    public function __construct(array $configs)
    {
        foreach ($configs as $graph => $config) {
            $this->addConfig($config, $graph);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get($object, string $graph = 'default'): StateMachineInterface
    {
        $hash = spl_object_hash($object);

        if (isset($this->stateMachines[$hash][$graph])) {
            return $this->stateMachines[$hash][$graph];
        }

        foreach ($this->configs as $config) {
            if ($config['graph'] === $graph && $object instanceof $config['class']) {
                return $this->stateMachines[$hash][$graph] = $this->createStateMachine($object, $config);
            }
        }

        throw new SMException(sprintf(
            'Cannot create a state machine because the configuration for object "%s" with graph "%s" does not exist.',
            get_class($object),
            $graph
        ));
    }

    /**
     * @return array returns available graph names
     */
    public function getGraphs()
    {
        $graphNames = [];

        foreach ($this->configs as $config) {
            $graphNames[] = $config['graph'];
        }
        return $graphNames;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        $this->stateMachines = array();
    }

    /**
     * Adds a new config
     *
     * @param array  $config
     * @param string $graph
     *
     * @throws SMException If the index "class" is not configured
     */
    public function addConfig(array $config, string $graph = 'default'): void
    {
        if (!isset($config['graph'])) {
            $config['graph'] = $graph;
        }

        if (!isset($config['class'])) {
            throw new SMException(sprintf(
                'Index "class" needed for the state machine configuration of graph "%s"',
                $config['graph']
            ));
        }

        $this->configs[] = $config;
    }

    /**
     * Create a state machine for the given object and config
     *
     * @param       $object
     * @param array $config
     *
     * @return StateMachineInterface
     *
     * @throws SMException
     */
    abstract protected function createStateMachine($object, array $config): StateMachineInterface;
}

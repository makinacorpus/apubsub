<?php

namespace MakinaCorpus\APubSub\Notification;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default implementation for registry, you may extend from this class and
 * override the getInstanceFromDefinition() method
 */
class ContainerAwareFormatterRegistry extends DefaultFormatterRegistry
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param array
     */
    protected $data = [];

    /**
     * @var array
     */
    protected $instances = [];

    /**
     * Default constructor
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct();

        $this->container = $container;
    }

    /**
     * {inheritodc}
     */
    protected function getInstanceFromDefinition($type)
    {
        if (!isset($this->data[$type])) {
            throw new \InvalidArgumentException(sprintf("Unknown type '%s'", $type));
        }

        $id = $this->data[$type];

        if ($this->container->has($id)) {
            return $this->container->get($id);
        }

        return parent::getInstanceFromDefinition($type);
    }
}

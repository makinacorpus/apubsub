<?php

namespace MakinaCorpus\APubSub\Notification;

use MakinaCorpus\APubSub\Notification\Formatter\DebugFormatter;
use MakinaCorpus\APubSub\Notification\Formatter\NullFormatter;

/**
 * Default implementation for registry, you may extend from this class and
 * override the getInstanceFromDefinition() method
 */
class DefaultFormatterRegistry implements FormatterRegistryInterface
{
    /**
     * @param array
     */
    protected $data = [];

    /**
     * @var array
     */
    protected $instances = [];

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * @var FormatterInterface
     */
    private $nullInstance;

    /**
     * @var FormatterInterface
     */
    private $debugInstance;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->nullInstance = new NullFormatter();
        $this->debugInstance = new DebugFormatter();
    }

    /**
     * {inheritdoc}
     */
    final public function setDebugMode($toggle = true)
    {
        $this->deubg = $toggle;
    }

    /**
     * {@inheritdoc}
     */
    final public function typeExists($type)
    {
        return isset($this->data[$type]);
    }

    /**
     * {@inheritdoc}
     */
    final public function registerType($type, $className)
    {
        $this->data[$type] = $className;
        unset($this->instances[$type]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function registerInstance($type, FormatterInterface $instance)
    {
        $this->data[$type] = get_class($instance);
        $this->instances[$type] = $instance;

        return $this;
    }

    /**
     * Get instance from definition
     *
     * @param string $type
     *
     * @return FormatterInterface
     */
    protected function getInstanceFromDefinition($type)
    {
        if (!isset($this->data[$type])) {
            throw new \InvalidArgumentException(sprintf("Unknown type '%s'", $type));
        }

        $className = $this->data[$type];

        if (!class_exists($className)) {
            throw new \LogicException(sprintf("Class '%s' does not exist for type '%s'", $className, $type));
        }

        return new $className($type);
    }

    /**
     * {@inheritdoc}
     */
    final public function get($type)
    {
        if (!isset($this->instances[$type])) {
            try {
                $instance = $this->getInstanceFromDefinition($type);

                if (!$instance instanceof FormatterInterface) {
                    throw new \LogicException(sprintf("Class '%s' does not implements FormatterInterface", get_class($instance)));
                }

                return $this->instances[$type] = $instance;

            } catch (\Exception $e) {
                if ($this->debug) {
                    // throw $e;
                    $this->instances[$type] = $this->debugInstance;
                } else {
                    $this->instances[$type] = $this->nullInstance;
                }
            }
        }

        return $this->instances[$type];
    }

    /**
     * {@inheritdoc}
     */
    final public function getTypeList()
    {
        return array_keys($this->data);
    }
}

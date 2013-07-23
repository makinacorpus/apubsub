<?php

namespace APubSub\Backend\Drupal7;

use APubSub\Backend\DefaultContext;
use APubSub\Backend\Drupal7\Helper\Cache;
use APubSub\Backend\Drupal7\Helper\NullCache;
use APubSub\Backend\Drupal7\Helper\TypeRegistry;
use APubSub\ContextInterface;

/**
 * Context implementation for Drupal 7 objects
 */
class D7Context extends DefaultContext
{
    /**
     * @var \DatabaseConnection
     */
    public $dbConnection;

    /**
     * @var Cache
     */
    public $cache;

    /**
     * Queue global limit (0 = no limit)
     *
     * @var int
     */
    public $queueGlobalLimit = 0;

    /**
     * Message maximum lifetime, in seconds (0 = no limit)
     *
     * @var int
     */
    public $messageMaxLifetime = 0;

    /**
     * Delay all checks
     *
     * Delay all checks (queue length, message life time, ...) at garbage
     * collection time (note that even if deactivate the garbage collection
     * will still continue to do those checks). Use this is you are running
     * in an environment where performance is critical and data volume high
     * enough to cause you slow index troubles. Note that the software env.
     * arround must run the garbage collection often enough.
     *
     * @var bool
     */
    public $delayChecks = true;

    /**
     * @var D7Backend
     */
    public $backend;

    /**
     * Type helper
     *
     * @var TypeRegistry
     */
    public $typeRegistry;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $dbConnection Database connection
     * @param D7Backend $backend                 Backend
     * @param array|Traversable $options        Options, if any
     */
    public function __construct(
        \DatabaseConnection $dbConnection,
        D7Backend $backend,
        $options = null)
    {
        parent::__construct($backend, $options);

        $this->dbConnection = $dbConnection;
        $this->typeRegistry = new TypeRegistry($this);
        $this->cache        = new Cache();
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ContextInterface::setOptions()
     */
    public function setOptions($options)
    {
        foreach ($options as $key => $value) {
            switch ($key) {

                case 'queue_global_limit':
                    $this->queueGlobalLimit = (int)$value;
                    break;

                case 'message_max_lifetime':
                    $this->messageMaxLifetime = (int)$value;
                    break;

                case 'delay_checks':
                    $this->delayChecks = (bool)$value;
                    break;

                case 'disable_cache':
                    if ($value) {
                        $this->cache = new NullCache();
                    }
                    break;
            }
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ContextInterface::getOptions()
     */
    public function getOptions()
    {
        return array(
            'queue_global_limit'   => $this->queueGlobalLimit,
            'message_max_lifetime' => $this->messageMaxLifetime,
            'delay_checks'         => $this->delayChecks,
            'disable_cache'        => $this->cache instanceof NullCache,
        );
    }
}

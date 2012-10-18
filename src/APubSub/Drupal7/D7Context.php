<?php

namespace APubSub\Drupal7;

use APubSub\Impl\ContextInterface;

/**
 * Drupal 7 context is an internal object that must not be public in any case.
 *
 * It will be spawn when the D7PubSub object will be created, and propagated
 * into every other object handled by this package.
 */
class D7Context implements ContextInterface
{
    /**
     * @var \DatabaseConnection
     */
    public $dbConnection;

    /**
     * Is this backend configured for keeping messages
     *
     * @var bool
     */
    public $keepMessages = false;

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
     * @var \APubSub\Drupal7\D7PubSub
     */
    public $backend;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $dbConnection Database connection
     * @param D7PubSub $backend                 Backend
     * @param array|Traversable $options        Options, if any
     */
    public function __construct(\DatabaseConnection $dbConnection,
        D7PubSub $backend, $options = null)
    {
        $this->dbConnection = $dbConnection;
        $this->backend = $backend;

        if (null !== $options) {
            $this->parseOptions($options);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Impl\ContextInterface::getBackend()
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Impl\ContextInterface::setOptions()
     */
    public function setOptions($options)
    {
        // FIXME: Parse options
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Impl\ContextInterface::getOptions()
     */
    public function getOptions()
    {
        throw new \Exception("Not implemented yet");
    }
}

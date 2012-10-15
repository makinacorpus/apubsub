<?php

namespace APubSub\Drupal7;

/**
 * Drupal 7 context is an internal object that must not be public in any case.
 *
 * It will be spawn when the D7PubSub object will be created, and propagated
 * into every other object handled by this package.
 */
class D7Context
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
     * Default constructor
     *
     * @param \DatabaseConnection $dbConnection Database connection
     * @param array $options                    Options, if any
     */
    public function __construct($dbConnection, array $options = null)
    {
        $this->dbConnection = $dbConnection;

        // FIXME: Parse options
    }
}

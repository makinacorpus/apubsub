<?php

namespace APubSub\Tests;

abstract class AbstractBackendBasedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \APubSub\PubSubInterface
     */
    protected $backend;

    /**
     * Create the backend for testing
     *
     * @return \APubSub\PubSubInterface Ready to use mock instance
     */
    abstract protected function setUpBackend();

    protected function setUp()
    {
        $this->backend = $this->setUpBackend();
    }
}

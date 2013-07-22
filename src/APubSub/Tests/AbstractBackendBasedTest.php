<?php

namespace APubSub\Tests;

abstract class AbstractBackendBasedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \APubSub\BackendInterface
     */
    protected $backend;

    /**
     * Create the backend for testing
     *
     * @return \APubSub\BackendInterface Ready to use mock instance
     */
    abstract protected function setUpBackend();

    protected function setUp()
    {
        $this->backend = $this->setUpBackend();
    }
}

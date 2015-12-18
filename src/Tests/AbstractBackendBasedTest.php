<?php

namespace MakinaCorpus\APubSub\Tests;

abstract class AbstractBackendBasedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \MakinaCorpus\APubSub\BackendInterface
     */
    protected $backend;

    /**
     * Create the backend for testing
     *
     * @return \MakinaCorpus\APubSub\BackendInterface Ready to use mock instance
     */
    abstract protected function setUpBackend();

    protected function setUp()
    {
        $this->backend = $this->setUpBackend();
    }
}

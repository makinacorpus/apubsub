<?php

namespace APubSub\Tests\Backend\Mongo;

use APubSub\Backend\Mongo\MongoPubSub;
use APubSub\Tests\AbstractMessageTest;

class MessageTest extends AbstractMessageTest
{
    /**
     * @var \Mongo
     */
    protected $db;

    protected function setUp()
    {
        if (!$this->db = Helper::getMongoConnection()) {
            $this->markTestSkipped("Mongo server URL not available.");
        } else {
            // In case a parent test run failed, the tables where not cleaned
            // up properly
            Helper::cleanup();

            parent::setUp();
        }
    }

    protected function tearDown()
    {
        if ($this->db) {
            Helper::cleanup();
        }

        parent::tearDown();
    }

    protected function setUpBackend()
    {
        return new MongoPubSub($this->db->{Helper::getDbName()});
    }
}

<?php

namespace APubSub\Tests\Drupal7;

use APubSub\Drupal7\D7PubSub;
use APubSub\Tests\AbstractSubscriptionTest;
use APubSub\Tests\Drupal\DrupalHelper;

class SubscriptionTest extends AbstractSubscriptionTest
{
    /**
     * @var bool
     */
    protected static $drupalBootstrapped = false;

    /**
     * @var \DatabaseConnection
     */
    protected $dbConnection;

    protected function setUp()
    {
        if (!$this->dbConnection = DrupalHelper::findDrupalDatabaseConnection(7)) {
            $this->markTestSkipped("Drupal 7 connection handler and database information are not available.");
        } else {
            parent::setUp();
        }
    }

    protected function tearDown()
    {
        // Test could have been skipped
        if (null !== $this->dbConnection) {
            foreach (array('apb_queue', 'apb_msg', 'apb_sub', 'apb_chan') as $table) {
                $this->dbConnection->truncate($table);
            }
        }

        parent::tearDown();
    }

    protected function setUpBackend()
    {
        return new D7PubSub($this->dbConnection);
    }
}

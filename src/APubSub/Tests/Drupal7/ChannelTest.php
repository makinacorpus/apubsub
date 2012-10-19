<?php

namespace APubSub\Tests\Drupal7;

use APubSub\Backend\Drupal7\D7PubSub;
use APubSub\Tests\AbstractChannelTest;
use APubSub\Tests\Drupal\DrupalHelper;

class ChannelTest extends AbstractChannelTest
{
    /**
     * @var bool
     */
    protected static $drupalBootstrapped = false;

    /**
     * @var \DatabaseConnection
     */
    protected $dbConnection;

    protected function cleanup()
    {
        // Test could have been skipped
        if (null !== $this->dbConnection) {
            foreach (array('apb_queue', 'apb_msg', 'apb_sub', 'apb_chan', 'apb_sub_map') as $table) {
                $this->dbConnection->query("TRUNCATE {" . $table . "}");
            }
        }
    }

    protected function setUp()
    {
        if (!$this->dbConnection = DrupalHelper::findDrupalDatabaseConnection(7)) {
            $this->markTestSkipped("Drupal 7 connection handler and database information are not available.");
        } else {
            // In case a parent test run failed, the tables where not cleaned
            // up properly
            $this->cleanup();

            parent::setUp();
        }
    }

    protected function tearDown()
    {
        $this->cleanup();

        parent::tearDown();
    }

    protected function setUpBackend()
    {
        return new D7PubSub($this->dbConnection);
    }
}

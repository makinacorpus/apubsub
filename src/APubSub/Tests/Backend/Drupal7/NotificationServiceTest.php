<?php

namespace APubSub\Tests\Backend\Drupal7;

use APubSub\Backend\Drupal7\D7Backend;
use APubSub\Tests\Backend\Drupal\DrupalHelper;
use APubSub\Tests\Notification\AbstractNotificationServiceTest;

class NotificationServiceTest extends AbstractNotificationServiceTest
{
    /**
     * @var \DatabaseConnection
     */
    protected $dbConnection;

    protected function cleanup()
    {
        // Test could have been skipped
        if (null !== $this->dbConnection) {
            foreach (array('apb_queue', 'apb_msg', 'apb_msg_chan', 'apb_sub_map', 'apb_sub', 'apb_chan') as $table) {
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
        return new D7Backend($this->dbConnection);
    }
}

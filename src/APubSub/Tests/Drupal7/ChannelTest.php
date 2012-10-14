<?php

namespace APubSub\Tests\Drupal7;

use APubSub\Drupal7\D7PubSub;
use APubSub\Tests\AbstractChannelTest;

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

    protected function setUp()
    {
        if (!self::$drupalBootstrapped) { // Ugly!
            define('DRUPAL_ROOT', '/var/www/d7-core/www');
            require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
            drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);
            self::$drupalBootstrapped = true;
        }

        // FIXME: Restore later
        //$this->markTestSkipped("Drupal 7 connection handler and database information are not available.");

        $this->dbConnection = \Database::getConnection();

        if (true /* not skipped */) {
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

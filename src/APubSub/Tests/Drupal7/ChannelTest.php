<?php

namespace APubSub\Tests\Drupal7;

use APubSub\Drupal7\D7PubSub;
use APubSub\Tests\AbstractChannelTest;

class ChannelTest extends AbstractChannelTest
{
    protected function setUpBackend()
    {
        // Ugly!
        if (!defined('DRUPAL_ROOT')) {
            define('DRUPAL_ROOT', '/var/www/d7-core/www');
            require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
            drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);
        }

        //$this->markTestSkipped("Drupal 7 connection handler and database information are not available.");

        return new D7PubSub(\Database::getConnection());
    }
}

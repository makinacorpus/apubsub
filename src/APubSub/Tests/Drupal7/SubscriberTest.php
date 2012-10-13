<?php

namespace APubSub\Tests\Drupal7;

use APubSub\Drupal7\D7PubSub;
use APubSub\Tests\AbstractSubscriberTest;

class SubscriberTest extends AbstractSubscriberTest
{
    protected function setUpBackend()
    {
        $this->markTestSkipped("Drupal 7 connection handler and database information are not available.");
        //return new D7PubSub();
    }
}

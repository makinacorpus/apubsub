<?php

namespace APubSub\Tests\Predis;

use APubSub\Predis\PredisPubSub;
use APubSub\Tests\AbstractChannelTest;

use Predis\Client;

class ChannelTest extends AbstractChannelTest
{  
    /**
     * @var \Predis\Client
     */
    protected $predisClient;

    /**
     * @var string
     */
    protected $keyPrefix;

    protected function setUpBackend()
    {
        return new PredisPubSub($this->predisClient, $this->keyPrefix);
    }

    protected function setUp()
    {
        if (!class_exists('Predis\Client')) {
            $this->markTestSkipped("Predis library is not available.");

            return;
        }

        $this->keyPrefix = uniqid('test');
        $this->predisClient = new Client();

        parent::setUp();
    }

    protected function tearDown()
    {
        // FIXME: Delete all keys with our prefix

        parent::tearDown();
    }
}

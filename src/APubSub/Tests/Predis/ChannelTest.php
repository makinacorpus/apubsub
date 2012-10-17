<?php

namespace APubSub\Tests\Predis;

use APubSub\Predis\PredisPubSub;
use APubSub\Tests\AbstractChannelTest;

use Predis\Client;

class ChannelTest extends AbstractChannelTest
{
    /**
     * @var string
     */
    protected $keyPrefix;

    protected function setUpBackend()
    {
        return new PredisPubSub(array(
            'keyprefix' => $this->keyPrefix,
        ));
    }

    protected function setUp()
    {
        $this->markTestSkipped("Predis library is not available.");
        return;

        if (!class_exists('Predis\Client')) {
            $this->markTestSkipped("Predis library is not available.");

            return;
        }

        $this->keyPrefix = uniqid('test');

        parent::setUp();
    }

    protected function tearDown()
    {
        // FIXME: Delete all keys with our prefix

        parent::tearDown();
    }
}

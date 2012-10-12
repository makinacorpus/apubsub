<?php

namespace APubSub\Tests\Impl; 

use APubSub\Memory\MemoryPubSub;
use APubSub\Impl\DefaultMessage;

class DefaultMessageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \APubSub\Memory\MemoryPubSub
     */
    protected $backend;

    /**
     * @var \APubSub\Memory\MemoryChannel
     */
    protected $channel;

    protected function setUp()
    {
        $this->backend = new MemoryPubSub();
        $this->channel = $this->backend->createChannel('foo');
    }

    public function testGetSetContent()
    {
        $data     = new \stdClass;
        $message  = new DefaultMessage($this->channel, $data, 12);
        $contents = $message->getContents();

        $this->assertSame($data, $contents);
    }

    public function testSetIdWhenAlreadySet()
    {
        $this->setExpectedException('LogicException');

        $message = new DefaultMessage($this->channel, null, 12);
        $message->setId(13);
    }

    public function testGetIdWhenNotSet()
    {
        $this->setExpectedException('LogicException');

        $message = new DefaultMessage($this->channel, null);
        $message->getId();
    }
}

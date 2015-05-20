<?php

namespace APubSub\Messenging;

use APubSub\ChannelInterface;
use APubSub\Field;

class Thread
{
    /**
     * @var MessengingService
     */
    private $service;

    /**
     * @var ChannelInterface
     */
    private $channel;

    /**
     * Default constructor
     *
     * @param MessengingService $service
     */
    public function __construct(MessengingService $service, ChannelInterface $channel)
    {
        $this->service = $service;
        $this->channel = $channel;
    }

    /**
     * Send a message
     *
     * @param mixed $contents
     *   Message text
     * @param string $sender
     *   Sender identifier
     * @param string $type
     *   Arbitrary business type
     * @param int $level
     *   Arbitrary business level
     *
     * @return MessageInterface
     */
    public function send($contents, $sender, $type = null, $level = 0)
    {
        $this->channel->send($contents, $type, $sender, $level);
    }

    /**
     * Get thread recipients
     *
     * @return string[]
     */
    public function getRecipients()
    {
        return iterator_to_array(
            $this
                ->service
                ->getBackend()
                ->fetchSubscribers([
                    Field::CHAN_ID => $this->channel->getId(),
                ])
        );
    }

    /**
     * Fetch thread messages
     *
     * @param mixed[] $conditions
     *
     * @return \APubSub\CursorInterface|\APubSub\MessageInstanceInterface[]
     */
    public function fetchMessages(array $conditions = [])
    {
        return $this->channel->fetch($conditions);
    }
}

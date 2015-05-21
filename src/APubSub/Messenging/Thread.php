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
     * @param ChannelInterface $channel
     */
    public function __construct(MessengingService $service, ChannelInterface $channel)
    {
        $this->service    = $service;
        $this->channel    = $channel;
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
     * Get identifier
     *
     * @return string
     */
    public function getId()
    {
        return $this->channel->getId();
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->channel->getTitle();
    }

    /**
     * Get creation time
     *
     * @return \DateTime
     *   Date when the object was created
     */
    public function getCreationDate()
    {
        return $this->channel->getCreationDate();
    }

    /**
     * Get latest update date
     *
     * @return \DateTime
     *   Date when the latest message was sent
    */
    public function getLatestUpdateDate()
    {
        return $this->channel->getLatestUpdateDate();
    }

    /**
     * Get thread recipients
     *
     * @return string[]
     */
    public function getRecipients()
    {
        $cursor = $this
            ->service
            ->getBackend()
            ->fetchSubscribers([
                Field::CHAN_ID => $this->channel->getId(),
            ])
        ;

        return function () use ($cursor) {
            /* @var $subscriber \APubSub\SubscriberInterface */
            foreach ($cursor as $subscriber) {
                // Yeah, my first PHP generator ever \o/ This worth the comment.
                yield(explode(':', $subscriber->getId())[1]);
            }
        };
    }

    /**
     * Fetch thread messages
     *
     * @param string $userId
     * @param mixed[] $conditions
     *
     * @return \APubSub\CursorInterface|\APubSub\MessageInstanceInterface[]
     */
    public function fetchMessagesFor($userId, array $conditions = [])
    {
        $conditions[Field::CHAN_ID] = $this->channel->getId();
        $conditions[Field::SUBER_NAME] = $this->service->getUserSubscriberId($userId);

        return $this->channel->fetch($conditions);
    }

    /**
     * Count unread messages for user
     *
     * @param string $userId
     * @param mixed[] $conditions
     */
    public function countUnreadFor($userId, array $conditions = [])
    {
        $conditions[Field::MSG_UNREAD] = 1;

        return $this->fetchMessagesFor($userId, $conditions)->count();
    }
}

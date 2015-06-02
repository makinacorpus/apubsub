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
     * Get messenging service this thread is attached to
     *
     * @return \APubSub\Messenging\MessengingService
     */
    public function getService()
    {
        return $this->service;
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
        return $this->channel->send($contents, $type, $sender, $level);
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
        $ret = [];

        $cursor = $this
            ->service
            ->getBackend()
            ->fetchSubscribers([
                Field::CHAN_ID => $this->channel->getId(),
            ])
        ;

        /* @var $subscriber \APubSub\SubscriberInterface */
        foreach ($cursor as $subscriber) {
            $ret[] = explode(':', $subscriber->getId())[1];
        }

        return $ret;
    }

    /**
     * Does user is in recipient list
     *
     * @param string $userId
     */
    public function isInRecipients($userId)
    {
        return $this
            ->service
            ->getBackend()
            ->getSubscriber(
                $this->service->getUserSubscriberId($userId)
            )
            ->hasSubscriptionFor(
                $this->channel->getId()
            )
        ;
    }

    /**
     * Add one or more recipients to this thread
     *
     * @param string|string[] $userIdList
     *   User identifier or list of user identifiers
     */
    public function addRecipients($userIdList)
    {
        return $this
            ->service
            ->addRecipientsTo(
                $this->getId(),
                $userIdList
            )
        ;
    }

    /**
     * Can user see this thread
     *
     * Alias to isInRecipients()
     *
     * @param string $userId
     */
    public function hasAccess($userId)
    {
        return $this->isInRecipients($userId);
    }

    /**
     * Removes user from this thread
     *
     * When no users are left on a thread, it will be deleted.
     *
     * @param string|string[] $threadId
     * @param string $userId
     */
    public function deleteFor($userId)
    {
        $this->service->deleteThreadFor($this->getId(), $userId);
    }

    /**
     * Removes this thread for everyone
     *
     * @param string|string[] $threadId
     */
    public function deleteThread()
    {
        $this->service->deleteThread($this->getId());
    }

    /**
     * Fetch all thread messages
     *
     * @param mixed[] $conditions
     *
     * @return \APubSub\CursorInterface|\APubSub\MessageInstanceInterface[]
     */
    public function fetchMessages(array $conditions = [])
    {
        $conditions[Field::CHAN_ID] = $this->channel->getId();

        return $this->channel->fetch($conditions);
    }

    /**
     * Fetch thread messages for user
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

        return $this
            ->fetchMessagesFor($userId, $conditions)
            ->count()
        ;
    }

    /**
     * Mark whole or part of the thread as read
     *
     * @param string $userId
     * @param boolean $read = true
     * @param mixed[] $conditions
     */
    public function markAsReadFor($userId, $read = true, array $conditions = [])
    {
        $this
            ->service
            ->markAsReadFor($userId, $this->getId(), $read, $conditions)
        ;
    }
}

<?php

namespace MakinaCorpus\APubSub\Messenging;

use MakinaCorpus\APubSub\BackendInterface;
use MakinaCorpus\APubSub\Backend\BackendAwareTrait;
use MakinaCorpus\APubSub\CursorInterface;
use MakinaCorpus\APubSub\Field;
use MakinaCorpus\APubSub\MessageInstanceInterface;
use MakinaCorpus\APubSub\Misc;

/**
 * Messenging service, single point of entry for the business layer
 */
class MessengingService
{
    /**
     * Subscriber prefix for messenging system
     */
    const SUBER_PREFIX = '_msg';

    use BackendAwareTrait;

    /**
     * Default constructor
     *
     * @param BackendInterface $backend
     */
    public function __construct(BackendInterface $backend)
    {
        $this->setBackend($backend);
    }

    /**
     * {@inheritdoc}
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * From user identifier get internal subscriber identifier
     *
     * @param string $userId
     *
     * @return string
     */
    public function getUserSubscriberId($userId)
    {
        return self::SUBER_PREFIX . ':' . $userId;
    }

    /**
     * Create a new message thread
     *
     * @param string $senderUserId
     *   Sender user identifier
     * @param string|string[] $recipient
     *   Recipient list of user identifiers
     * @param string $subject
     *   Optional thread name
     *
     * @return Thread
     *   New thread
     */
    public function createThread($senderUserId, $recipient, $subject = null)
    {
        $recipient = Misc::toArray($recipient);

        // Generate a unique time based identifier, note that this might
        // own some risks of conflicts, but very low. SHA-1 gives a 40
        // character-long string, we have a database limit of 64 with the
        // Drupal backend, so this is acceptable
        $id = $senderUserId . ':' . sha1($senderUserId . implode(',', $recipient) . uniqid('', true));

        $chan = $this->backend->createChannel($id, $subject);

        array_unshift($recipient, $senderUserId);

        // This is the only non-scalable part of the algorithm
        foreach ($recipient as $recipientId) {
            $this
                ->backend
                ->getSubscriber($this->getUserSubscriberId($recipientId))
                ->subscribe($chan->getId())
            ;
        }

        return new Thread($this, $chan);
    }

    /**
     * Removes user from the given one or more thread
     *
     * When no users are left on a thread, it will be deleted.
     *
     * @param string|string[] $threadId
     * @param string $userId
     */
    public function deleteThreadFor($threadId, $userId)
    {
        $this
            ->backend
            ->getSubscriber($this->getUserSubscriberId($userId))
            ->unsubscribe($threadId)
        ;
    }

    /**
     * Removes one or more thread for everyone
     *
     * @param string|string[] $threadId
     */
    public function deleteThread($threadId)
    {
        $this
            ->backend
            ->fetchChannels([
                Field::CHAN_ID => $threadId
            ])
            ->delete()
        ;
    }

    /**
     * Mark one or more thread as read for user
     *
     * @param string $userId
     *   User identifier
     * @param string|string[] $threadId
     *   Thread identifier or list of thread identifiers
     * @param boolean $read
     *   New read status
     * @param mixed[] $conditions
     *   Additional conditions
     */
    public function markAsReadFor($userId, $threadId, $read = true, array $conditions = [])
    {
        $conditions[Field::CHAN_ID] = $threadId;

        $this
            ->getUserMessages($userId, $conditions)
            ->update([
                Field::MSG_UNREAD => !(bool)$read,
                // @todo This should be implicit...
                Field::MSG_READ_TS => (new \DateTime())->format(Misc::SQL_DATETIME),
            ])
        ;
    }

    /**
     * Get user threads
     *
     * Using the getUserMessages() method, you can provide a message-based
     * UI, while using this method you can provide a thread-based UI.
     *
     * @return CursorInterface|ThreadInterface[]
     */
    public function getUserThreads($userId, array $conditions = [])
    {
        $conditions[Field::SUBER_NAME] = $this->getUserSubscriberId($userId);

        return new ThreadCursor(
            $this,
            $this->backend->fetchChannels($conditions)
        );
    }

    /**
     * Add one or more recipients to this thread
     *
     * @param string $threadId
     *   Thread identifier
     * @param string|string[] $userId
     *   User identifier or list of user identifiers
     */
    public function addRecipientsTo($threadId, $userIdList)
    {
        $subList = [];

        foreach (Misc::toIterable($userIdList) as $userId) {

            $subList[] = $this
                ->backend
                ->getSubscriber($this->getUserSubscriberId($userId))
                ->subscribe($threadId)
                ->getId()
            ;
        }

        $this->backend->copyQueue($threadId, $subList);
    }

    /**
     * Get user messages
     *
     * This should serve you to have quick and easy count methods over the
     * unread messages, for example.
     *
     * Using the getUserThreads() method, you can provide a thread-based
     * UI, while using this method you can provide a message-based UI.
     *
     * @return CursorInterface|MessageInstanceInterface[]
     */
    public function getUserMessages($userId, array $conditions = [])
    {
        $conditions[Field::SUBER_NAME] = $this->getUserSubscriberId($userId);

        return $this->backend->fetch($conditions);
    }

    /**
     * Get thread by identifier
     *
     * @param string $id
     *
     * @return Thread
     */
    public function getThread($id)
    {
        return new Thread($this, $this->backend->getChannel($id));
    }
}

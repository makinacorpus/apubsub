<?php

namespace APubSub;

use APubSub\Impl\ObjectInterface;

/**
 * Interface for all channels.
 */
interface ChannelInterface extends ObjectInterface
{
    /**
     * Get channel identifier
     *
     * @return string Channel identifier
     */
    public function getId();

    /**
     * Get the backend this channel is originating from
     *
     * @return \APubSub\PubSubInterface The owner backend
     */
    public function getBackend();

    /**
     * Get message by identifier
     *
     * @param scalar $id                 Message identifier whose type depends
     *                                   on the channel implementation
     *
     * @return \APubSub\MessageInterface The message
     *
     * @throws \APubSub\Error\MessageDoesNotExistException
     *                                  If message does not exist
     */
    public function getMessage($id);

    /**
     * Get list of message by identifier
     *
     * @param array $idList List of message id
     *
     * @throws \APubSub\Error\MessageDoesNotExistException
     *                      If one or more message(s) does not exist
     */
    public function getMessages($idList);

    /**
     * Get creation time as a UNIX timestamp
     *
     * @return int UNIX timestamp where the channel was created
     */
    public function getCreationTime();

    /**
     * Send a new message
     *
     * @param mixed $contents            Any kind of contents (will be
     *                                   serialized if not a primitive type)
     * @param int $sendTime              If set the creation/send timestamp will
     *                                   be forced to the given value
     *
     * @return \APubSub\MessageInterface The new message
     */
    public function send($contents, $sendTime = null);

    /**
     * Send a set of messages
     *
     * Use this method when you have a lot of messages to send at the same time
     * and no use of fetching back the message instance
     *
     * @param array|Traversable $contentList Messages contents
     */
    //public function massSend($contentList);

    /**
     * Create a new subscription to this channel.
     *
     * @return \APubSub\SubscriptionInterface The new subscription object, which
     *                                        is not active per default and whose
     *                                        identifier has been generated
     */
    public function subscribe();
}

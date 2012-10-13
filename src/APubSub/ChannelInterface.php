<?php

namespace APubSub;

/**
 * Interface for all channels. 
 */
interface ChannelInterface
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
     * Get creation time as a UNIX timestamp
     *
     * @return int UNIX timestamp where the channel was created
     */
    public function getCreationTime();

    /**
     * Create a new message instance
     *
     * @param mixed $contents            Any kind of contents (will be
     *                                   serialized if not a primitive type)
     * @param int $sendTime              If set the creation/send timestamp will
     *                                   be forced to the given value
     *
     * @return \APubSub\MessageInterface The new message
     */
    public function createMessage($contents, $sendTime = null);

    /**
     * Send a message into the channel
     *
     * @param MessageInterface $message The message to send, it must absolutely
     *                                  be created by the same channel
     */
    public function send(MessageInterface $message);

    /**
     * Create a new subscription to this channel.
     *
     * @return \APubSub\SubscriptionInterface The new subscription object, which
     *                                        is not active per default and whose
     *                                        identifier has been generated
     */
    public function subscribe();
}

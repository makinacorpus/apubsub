<?php

namespace APubSub;

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
     * Get message by identifier
     *
     * When you fetch a message using the channel as entry point, this message
     * won't have any subscription tied to it, you cannot use most of the
     * message interface methods
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
     * Create a new subscription to this channel.
     *
     * @return \APubSub\SubscriptionInterface The new subscription object, which
     *                                        is not active per default and whose
     *                                        identifier has been generated
     */
    public function subscribe();

    /**
     * Get statistics helper
     *
     * This method is optional for backends
     *
     * @return \APubSub\Helper\ChannelStatInterface Stat helper
     *
     * @throws \APubSub\Error\UncapableException    If backend doesn't implement
     *                                              this helper
     */
    public function getStatHelper();
}

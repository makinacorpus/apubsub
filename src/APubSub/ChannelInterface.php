<?php

namespace APubSub;

/**
 * Interface for all channels.
 */
interface ChannelInterface extends
    ObjectInterface,
    MessageContainerInterface
{
    /**
     * Get channel identifier
     *
     * @return string Channel identifier
     */
    public function getId();

    /**
     * Get creation time as a UNIX timestamp
     *
     * @return int UNIX timestamp where the channel was created
     */
    public function getCreationTime();

    /**
     * Send a new message
     *
     * @param mixed $contents   Any kind of contents (will be serialized)
     * @param string $type      Message type
     * @param int $level        Arbitrary business level
     * @param int $sendTime     If set the creation/send timestamp will be
     *                          forced to the given value
     *
     * @return MessageInterface The new message
     */
    public function send($contents, $type = null, $level = 0, $sendTime = null);

    /**
     * Create a new subscription to this channel.
     *
     * @return SubscriptionInterface The new subscription object, which is not
     *                               active per default and whose identifier
     *                               has been generated
     */
    public function subscribe();
}

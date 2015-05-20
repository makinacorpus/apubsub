<?php

namespace APubSub;

/**
 * Channel reprensentation
 */
interface ChannelInterface extends MessageContainerInterface
{
    /**
     * Get channel identifier
     *
     * @return string
     *   Channel identifier
     */
    public function getId();

    /**
     * Get channel title
     *
     * @return string
     *   Human readable title if set otherwise null
     */
    public function getTitle();

    /**
     * Update channel title
     *
     * @param string $title
     *   New title or null to unset it
     */
    public function setTitle($title);

    /**
     * Get creation time
     *
     * @return \DateTime
     *   Date when the object was created
     */
    public function getCreationDate();

    /**
     * Send a single message to this channel
     *
     * Alias of BackendInterface::send()
     *
     * @param mixed $contents
     *   Any kind of contents (will be serialized)
     * @param string $type
     *   Message type
     * @param string $origin
     *   Arbitrary origin text representation
     * @param int $level
     *   Arbitrary business level
     * @param scalar[] $excluded
     *   Excluded subscription identifiers from recipient list
     * @param \DateTime $sentAt
     *   If set the creation/send date will be forced to the given value
     *
     * @return MessageInterface
     *   The new message
     */
    public function send(
        $contents,
        $type               = null,
        $origin             = null,
        $level              = 0,
        array $excluded     = null,
        \DateTime $sentAt   = null
    );

    /**
     * Create a new subscription to this channel.
     *
     * Alias of BackendInterface::subscribe()
     *
     * @return SubscriptionInterface
     *   The new subscription object, which is not active per default and
     *   whose identifier has been generated
     */
    public function subscribe();
}

<?php

namespace APubSub;

/**
 * Field constants enumeration
 */
final class Field
{
    /**
     * Whatever object you are querying identifier
     */
    const SELF_ID         = 1;

    /**
     * Channel id
     */
    const CHAN_ID         = 10;

    /**
     * Channel creation timestamp
     */
    const CHAN_CREATED_TS = 11;

    /**
     * Message identifier
     */
    const MSG_ID          = 20;

    /**
     * Message sent
     */
    const MSG_SENT        = 21;

    /**
     * Message read/unread status
     *
     * When sorting, read < unread
     */
    const MSG_UNREAD      = 22;

    /**
     * Message type
     */
    const MSG_TYPE        = 23;

    /**
     * Read UNIX timestamp
     */
    const MSG_READ_TS     = 24;

    /**
     * Message arbitrary level value
     */
    const MSG_LEVEL       = 25;

    /**
     * Message identifier
     */
    const MSG_QUEUE_ID    = 26;

    /**
     * Subscriber name
     */
    const SUBER_NAME      = 30;

    /**
     * Subscriber last access time
     */
    const SUBER_ACCESS    = 31;

    /**
     * Subscription identifier
     */
    const SUB_ID          = 40;

    /**
     * Subscription status
     *
     * For sort enabled > disabled
     */
    const SUB_STATUS      = 41;

    /**
     * Subscription creation timestamp
     */
    const SUB_CREATED_TS  = 42;
}

<?php

namespace MakinaCorpus\APubSub;

/**
 * Field constants enumeration
 */
final class Field
{
    /**
     * Channel id
     */
    const CHAN_ID         = 10;

    /**
     * Channel title
     */
    const CHAN_TITLE      = 12;

    /**
     * Channel creation date
     */
    const CHAN_CREATED_TS = 11;

    /**
     * Channel update date
     */
    const CHAN_UPDATED_TS = 12;

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
     * Read date
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
     * Message origin
     */
    const MSG_ORIGIN      = 28;

    /**
     * Subscriber name
     */
    const SUBER_NAME      = 30;

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
     * Subscription creation date
     */
    const SUB_CREATED_TS  = 42;

    /**
     * Subscription activation date
     */
    const SUB_ACTIVATED   = 43;

    /**
     * Subscription deactivation date
     */
    const SUB_DEACTIVATED = 44;

    /**
     * Subscription last access date
     */
    const SUB_ACCESS      = 45;
}

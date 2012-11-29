<?php

namespace APubSub;

final class Filter
{
    /**
     * Sort order descending
     */
    const SORT_DESC = - 1;

    /**
     * Sort order ascending
     */
    const SORT_ASC = 1;

    /**
     * No limit
     */
    const NO_LIMIT = 0;

    /**
     * Field "unread"
     */
    const FIELD_UNREAD = 1;

    /**
     * Field "sent"
     */
    const FIELD_SENT = 2;

    /**
     * Field "channel name"
     */
    const FIELD_CHANNEL = 3;

    /**
     * Field "subscription id"
     */
    const FIELD_SUBSCRIPTION = 4;

    /**
     * Do not instanciate this class
     */
    private function __construct()
    {
    }
}

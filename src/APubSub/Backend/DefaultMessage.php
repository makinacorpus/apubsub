<?php

namespace APubSub\Backend;

use APubSub\ContextInterface;
use APubSub\MessageInterface;

/**
 * Default message implementation suitable for most backends
 */
class DefaultMessage extends AbstractObject implements MessageInterface
{
    /**
     * Message identifier
     *
     * @var scalar
     */
    private $id;

    /**
     * Message type
     *
     * @return string
     */
    private $type;

    /**
     * Message raw data
     *
     * @var mixed
     */
    private $contents;

    /**
     * @var int
     */
    private $level;

    /**
     * Default constructor
     *
     * @param ContextInterface $context Context
     * @param string $chanId            Channel identifier
     * @param string $subscriptionId    Subscription identifier
     * @param mixed $contents           Message contents
     * @param scalar $id                Message identifier
     * @param int $sendTime             Send time UNIX timestamp
     * @param string $type              Message type
     * @param bool $isUnread            Is this message unread
     * @param int $readTimestamp        Read timestamp
     * @param int $level                Level
     */
    public function __construct(
        ContextInterface $context,
        $contents,
        $id,
        $type          = null,
        $level         = 0)
    {
        parent::__construct($context);

        $this->id             = $id;
        $this->contents       = $contents;
        $this->type           = $type;
        $this->level          = $level;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getContents()
    {
        return $this->contents;
    }

    public function getLevel()
    {
        return $this->level;
    }
}

<?php

namespace APubSub\Backend\Memory;

use APubSub\Backend\AbstractObject;
use APubSub\CursorInterface;
use APubSub\SubscriptionInterface;

/**
 * Array based implementation for unit testing: do not use in production
 */
class MemorySubscription extends AbstractObject implements SubscriptionInterface
{
    /**
     * Sort helper for messages
     *
     * @param MemoryMessage $a   Too lazy to comment
     * @param MemoryMessage $b   Too lazy to comment
     * @param string $sortField  Too lazy to comment
     * @param int $sortDirection Too lazy to comment
     *
     * @return int               Too lazy to comment
     */
    public static function sortMessages(MemoryMessage $a, MemoryMessage $b, $sortField, $sortDirection)
    {
        $value = 0;

        switch ($sortField) {

            case CursorInterface::FIELD_CHAN_ID:
                $value = strcmp($a->getChannelId(), $b->getChannelId());
                break;

            case CursorInterface::FIELD_MSG_SENT:
                $value = $a->getSendTimestamp() - $b->getSendTimestamp();
                break;

            case CursorInterface::FIELD_SUB_ID:
                $value = $a->getSubscriptionId() - $b->getSubscriptionId();
                break;

            case CursorInterface::FIELD_MSG_UNREAD:
                $value = ((int)$a->isUnread()) - ((int)$b->isUnread());
                break;
        }

        if (0 === $value) {
            $value = $a->getId() - $b->getId();
        }

        if (CursorInterface::SORT_DESC === $sortDirection) {
            $value = 0 - $value;
        }

        return $value;
    }

    /**
     * Sort helper for messages
     *
     * @param MemoryMessage $a  Too lazy to comment
     * @param array $conditions Too lazy to comment
     *
     * @return bool             Too lazy to comment
     */
    public static function filterMessages(MemoryMessage $a, array $conditions)
    {
        foreach ($conditions as $key => $value) {

            $value = null;

            switch ($key) {

                case CursorInterface::FIELD_CHAN_ID:
                    $value = $a->getChannelId();
                    break;

                case CursorInterface::FIELD_MSG_SENT:
                    $value = $a->getSendTimestamp();
                    break;

                case CursorInterface::FIELD_SUB_ID:
                    $value = $a->getSubscriptionId();
                    break;

                case CursorInterface::FIELD_MSG_ID:
                    $value = $a->isUnread();
                    break;
            }

            if (null === $key && $value !== null || $key != $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Message identifier
     *
     * @var scalar
     */
    private $id;

    /**
     * Channel identifier this message belongs to
     *
     * @var string
     */
    private $chanId;

    /**
     * Creation UNIX timestamp
     *
     * @var int
     */
    private $created;

    /**
     * Is this subscription active
     *
     * @var bool
     */
    private $active = false;

    /**
     * Time when this subscription has been activated for the last time as a
     * UNIX timestamp
     *
     * @var int
     */
    private $activatedTime;

    /**
     * Time when this subscription has been deactivated for the last time as a
     * UNIX timestamp
     *
     * @var int
     */
    private $deactivatedTime;

    /**
     * Current message queue
     *
     * @var array
     */
    private $messageQueue = array();

    /**
     * Already fetched messages
     *
     * @var array
     */
    private $readMessages = array();

    /**
     * Default constructor
     *
     * @param MemoryContext $context Context
     * @param string $chanId         Channel identifier
     * @param scalar $id             Message identifier
     */
    public function __construct(MemoryContext $context, $chanId, $id = null)
    {
        $this->id = $id;
        $this->chanId = $chanId;
        $this->created = $this->deactivatedTime = time();
        $this->context = $context;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::getId()
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelAwareInterface::getChannelId()
     */
    public function getChannelId()
    {
        return $this->chanId;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelAwareInterface::getChannel()
     */
    public function getChannel()
    {
        return $this->context->backend->getChannel($this->chanId);
    }

    /**
     * (non-PHPdoc)
     * @see APubSub.ChannelInterface::getCreationTime()
     */
    public function getCreationTime()
    {
        return $this->created;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::isActive()
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::getStartTime()
     */
    public function getStartTime()
    {
        if (!$this->active) {
            throw new \LogicException("This subscription is not active");
        }

        return $this->activatedTime;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::getStopTime()
     */
    public function getStopTime()
    {
        if ($this->active) {
            throw new \LogicException("This subscription is active");
        }

        return $this->deactivatedTime;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::delete()
     */
    public function delete()
    {
        $this->context->backend->deleteSubscription($this->getId());
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::fetch()
     */
    public function fetch(
        $limit            = CursorInterface::LIMIT_NONE,
        $offset           = 0,
        array $conditions = null,
        $sortField        = CursorInterface::FIELD_MSG_SENT,
        $sortDirection    = CursorInterface::SORT_DESC)
    {
        if (!isset($this->context->subscriptionMessages[$this->id])) {
            return array();
        }

        $ret = $this->context->subscriptionMessages[$this->id];

        uasort($ret, function ($a, $b) use ($sortField, $sortDirection) {
            return MemorySubscription::sortMessages(
                $a, $b, $sortField, $sortDirection);
        });

        if ($conditions) {
            $ret = array_filter($ret, function ($a) use ($conditions) {
                return MemorySubscription::filterMessages($a, $conditions);
            });
        }

        if ($limit) {
            $ret = array_slice($ret, $offset, $limit);
        }

        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::deactivate()
     */
    public function deactivate()
    {
        $this->active = false;
        $this->deactivatedTime = time();
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::activate()
     */
    public function activate()
    {
        $this->active = true;
        $this->activatedTime = time();
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::flush()
     */
    public function flush()
    {
        $this->context->getMessageListFor(array($this->id));
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::setUnread()
     */
    public function setUnread($messageId, $toggle = false)
    {
        if (isset($this->context->subscriptionMessages[$this->id])) {
            $this->context->subscriptionMessages[$this->id]->setUnread($toggle);
        }
    }
}

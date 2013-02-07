<?php

namespace APubSub\Backend\Memory;

use APubSub\Backend\AbstractObject;
use APubSub\Backend\ArrayCursor;
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
     * @see \APubSub\SubscriptionInterface::getChannelId()
     */
    public function getChannelId()
    {
        return $this->chanId;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::getChannel()
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
    public function fetch(array $conditions = null)
    {
        if (!isset($this->context->subscriptionMessages[$this->id])) {
            return array();
        }

        $ret = $this->context->subscriptionMessages[$this->id];

        if ($conditions) {
            $ret = array_filter($ret, function ($a) use ($conditions) {
                return MemorySubscription::filterMessages($a, $conditions);
            });
        }

        $sorter = new MemoryMessageSorter();

        return new ArrayCursor($this->context, $ret, $sorter->getAvailableSorts(), $sorter);
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

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::deleteMessage()
     */
    public function deleteMessage($id)
    {
        $this->deleteMessages(array($id));
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::deleteMessages()
     */
    public function deleteMessages(array $idList)
    {
        if (isset($this->context->subscriptionMessages[$this->id])) {
            foreach ($idList as $id) {
                unset($this->context->subscriptionMessages[$this->id][$id]);
            }
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::getMessage()
     */
    public function getMessage($id)
    {
        foreach ($this->fetch() as $message) {
            if ($message->getId() === $id) {
                return $message;
            }
        }

        throw new MessageDoesNotExistException();
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::getMessages()
     */
    public function getMessages(array $idList)
    {
        $ret = array();

        foreach ($this->fetch() as $message) {
            if (in_array($id, $message->getId())) {
                return $ret[] = $message;
            }
        }

        if (count($ret) !== count($idList)) {
            throw new MessageDoesNotExistException();
        }

        // FIXME Re-order messages following the $idList order

        return $ret;
    }
}

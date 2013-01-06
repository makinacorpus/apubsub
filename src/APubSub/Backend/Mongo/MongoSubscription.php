<?php

namespace APubSub\Backend\Mongo;

use APubSub\Backend\AbstractObject;
use APubSub\Backend\DefaultMessage;
use APubSub\Backend\Mongo\Cursor\MongoMessageCursor;
use APubSub\CursorInterface;
use APubSub\SubscriptionInterface;

class MongoSubscription extends AbstractObject implements SubscriptionInterface
{
    /**
     * Subscription identifier
     *
     * @var string
     */
    private $id;

    /**
     * Channel database identifier
     *
     * @var string
     */
    private $chanDbId;

    /**
     * Channel identifier
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
     * Default constructor
     *
     * @param MongoContext $context Context
     * @param string $id            Subscription identifier
     * @param int $chanDbId         Channel database identifier
     * @param string $chanId        Channel identifier
     * @param int $created          Creation UNIX timestamp
     * @param int $activatedTime    Latest activation UNIX timestamp
     * @param int $deactivatedTime  Latest deactivation UNIX timestamp
     * @param bool $isActive        Is this subscription active
     */
    public function __construct(MongoContext $context, $id, $chanDbId,
        $chanId, $created, $activatedTime, $deactivatedTime, $isActive)
    {
        $this->id              = $id;
        $this->chanDbId        = $chanDbId;
        $this->chanId          = $chanId;
        $this->created         = $created;
        $this->activatedTime   = $activatedTime;
        $this->deactivatedTime = $deactivatedTime;
        $this->active          = $isActive;
        $this->context         = $context;
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
        return $this->context->backend->getChannelByDatabaseId($this->chanDbId);
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
        throw new \Exception("Not implemented yet");

        /*
         * Targeted query:
         *
         * SELECT q.*, m.* FROM apb_queue q mp
         *     JOIN apb_msg m ON m.id = q.msg_id
         *     WHERE
         *       q.sub_id = :subid
         *       [CONDITION [...]]
         *     ORDER BY [FIELD] [DIRECTION];
         */

        /*
        $query = $this
            ->context
            ->dbConnection
            ->select('apb_queue', 'q');
        $query
            ->join('apb_msg', 'm', 'm.id = q.msg_id');
        $query
            ->fields('m')
            ->fields('q')
            ->condition('q.sub_id', $this->id);

        // FIXME: Apply conditions.
        return new MongoMessageCursor($this->context, $query);
         */
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::deactivate()
     */
    public function deactivate()
    {
        $deactivated = time();

        $this
            ->context
            ->subCollection
            ->update(
                array(
                    '_id' => new \MongoId($this->id),
                ),
                array(
                    'deactivated' => $deactivated,
                    'active'      => false,
                ),
                array(
                    'multiple' => false,
                )
            );

        $this->active          = false;
        $this->deactivatedTime = $deactivated;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::activate()
     */
    public function activate()
    {
        $activated = time();

        $this
            ->context
            ->subCollection
            ->update(
                array(
                    '_id' => new \MongoId($this->id),
                ),
                array(
                    'activated' => $activated,
                    'active'    => true,
                ),
                array(
                    'multiple' => false,
                )
            );

        $this->active        = true;
        $this->activatedTime = $activated;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::flush()
     */
    public function flush()
    {
        $this
            ->context
            ->queueCollection
            ->remove(array(
                'sub_id' => new \MongoId($this->id),
            ));
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::setUnread()
     */
    public function setUnread($messageId, $toggle = false)
    {
        $this
            ->context
            ->queueCollection
            ->update(
                array(
                    'msg_id' => new \MongoId($messageId),
                    'sub_id' => new \MongoId($this->id),
                ),
                array(
                    'unread' => $toggle,
                ),
                array(
                    'multiple' => true,
                )
            );
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
        foreach ($idList as $key => $id) {
            $idList[$key] = new \MongoId($id);
        }

        $this
            ->context
            ->queueCollection
            ->remove(array(
                'sub_id' => new \MongoId($this->id),
                'msg_id' => array(
                    '$in' => $idList,
                ),
            ));
    }
}

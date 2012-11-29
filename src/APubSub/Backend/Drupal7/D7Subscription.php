<?php

namespace APubSub\Backend\Drupal7;

use APubSub\Backend\AbstractObject;
use APubSub\Backend\DefaultMessage;
use APubSub\Filter;
use APubSub\SubscriptionInterface;

/**
 * Drupal 7 simple subscription implementation
 */
class D7Subscription extends AbstractObject implements SubscriptionInterface
{
    /**
     * Message identifier
     *
     * @var scalar
     */
    private $id;

    /**
     * Channel database identifier
     *
     * @var string
     */
    private $chanDbId;

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
     * @param D7Context $context   Context
     * @param int $chanDbId        Channel database identifier
     * @param int $id              Subscription identifier
     * @param int $created         Creation UNIX timestamp
     * @param int $activatedTime   Latest activation UNIX timestamp
     * @param int $deactivatedTime Latest deactivation UNIX timestamp
     * @param bool $isActive       Is this subscription active
     */
    public function __construct(D7Context $context, $chanDbId, $id,
        $created, $activatedTime, $deactivatedTime, $isActive)
    {
        $this->id = $id;
        $this->chanDbId = $chanDbId;
        $this->created = $created;
        $this->activatedTime = $activatedTime;
        $this->deactivatedTime = $deactivatedTime;
        $this->active = $isActive;
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
        // FIXME: return $this->chanId;
        return $this->getChannel()->getId();
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
     *
    public function fetch()
    {
        $ret = array();
        $cx  = $this->context->dbConnection;

        $idList = $cx
            // Don't care about sort hopefully the items will be naturally
            // ordered by insertion time even thought this is not guaranteed
            // by any SQL standard
            ->query("SELECT msg_id FROM {apb_queue} WHERE sub_id = :id", array(
                ':id' => $this->id,
            ))
            ->fetchCol();

        if (empty($idList)) {
            return $ret;
        }

        $ret = $this->getChannel()->getMessages($idList);

        return $ret;
    }
     */

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::fetch()
     */
    public function fetch(
        $limit            = Filter::NO_LIMIT,
        $offset           = 0,
        array $conditions = null,
        $sortField        = Filter::FIELD_SENT,
        $sortDirection    = Filter::SORT_DESC)
    {
        $ret    = array();
        $idList = array();

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

        // F**ing Drupal cannot use OFFSET without LIMIT
        if (Filter::NO_LIMIT !== $limit) {
            $query->range($offset, $limit);
        }

        if (Filter::SORT_DESC === $sortDirection) {
            $sqlDirection = 'DESC';
        } else {
            $sqlDirection = 'ASC';
        }

        switch ($sortField) {

            case Filter::FIELD_CHANNEL:
                $query->orderBy('m.chan_id', $sqlDirection);
                break;

            case Filter::FIELD_SENT:
                // Special case, always order by identifier when dealing sent
                // timestamp, allowing us to ensure some kind of order when
                // dealing with messages sent the same second
                $query->orderBy('m.created', $sqlDirection);
                $query->orderBy('m.id', $sqlDirection);
                break;

            case Filter::FIELD_SUBSCRIPTION:
                $query->orderBy('q.sub_id', $sqlDirection);
                break;

            case Filter::FIELD_UNREAD:
                $query->orderBy('q.unread', $sqlDirection);
                break;
        }

        $results = $query->execute();

        foreach ($results as $record) {
            $ret[] = new DefaultMessage($this->context,
                (string)$record->chan_id, (int)$record->sub_id,
                unserialize($record->contents), (int)$record->id,
                (int)$record->created, (bool)$record->unread);

            $idList[] = (int)$record->id;
        }

        return $ret;
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
            ->dbConnection
            ->query("UPDATE {apb_sub} SET status = 0, deactivated = :deactivated WHERE id = :id", array(
                ':deactivated' => $deactivated,
                ':id' => $this->id,
            ));

        $this->active = false;
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
            ->dbConnection
            ->query("UPDATE {apb_sub} SET status = 1, activated = :activated WHERE id = :id", array(
                ':activated' => $activated,
                ':id' => $this->id,
            ));

        $this->active = true;
        $this->activatedTime = $activated;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::flush()
     */
    public function flush()
    {
        // Even de-activated, ensure a flush
        $this
            ->context
            ->dbConnection
            ->delete('apb_queue')
            ->condition('sub_id', $this->id)
            ->execute();
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::setUnread()
     */
    public function setUnread($messageId, $toggle = false)
    {
        $this
            ->context
            ->dbConnection
            ->query("
                UPDATE {apb_queue}
                SET unread = :unread
                WHERE msg_id = :msgid
                  AND sub_id = :subid
            ", array(
                ':unread' => (int)$toggle,
                ':msgid'  => (int)$messageId,
                ':subid'  => $this->id,
            ));
    }
}

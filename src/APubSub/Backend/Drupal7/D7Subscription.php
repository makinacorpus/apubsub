<?php

namespace APubSub\Backend\Drupal7;

use APubSub\Backend\AbstractObject;
use APubSub\Backend\Drupal7\Cursor\D7MessageCursor;
use APubSub\CursorInterface;
use APubSub\Error\MessageDoesNotExistException;
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
     * @var array
     */
    private $extraData = array();

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
     * @param array $extraData     Extra data
     */
    public function __construct(
        D7Context $context,
        $chanDbId,
        $id,
        $created,
        $activatedTime,
        $deactivatedTime,
        $isActive,
        array $extraData = array())
    {
        $this->id = $id;
        $this->chanDbId = $chanDbId;
        $this->created = $created;
        $this->activatedTime = $activatedTime;
        $this->deactivatedTime = $deactivatedTime;
        $this->active = $isActive;
        $this->context = $context;
        $this->extraData = $extraData;
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
        return $this->getChannel()->getId();
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::getChannel()
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

        $cursor = new D7MessageCursor($this->context, $query);

        if (null !== $conditions) {
            $cursor->applyConditions($conditions);
        }

        return $cursor;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::update()
     *
     * // FIXME: Fix this (performance problem spotted)
     */
    public function update(array $values, array $conditions = null)
    {
        if (empty($values)) {
            return;
        }

        $cx     = $this->context->dbConnection;
        $fields = array();

        foreach ($values as $field => $value) {
            switch ($field) {

                case CursorInterface::FIELD_MSG_UNREAD:
                    if (!$fields['unread'] = $value ? 1 : 0) {
                        // Also update the read timestamp if necessary
                        $fields['read_timestamp'] = time();
                    }
                    break;

                case CursorInterface::FIELD_MSG_READ_TS:
                    $fields['read_timestamp'] = (int)$value;
                    break;

                default:
                    trigger_error(sprintf("% does not support updating %d",
                        get_class($this), $field));
                    break;
            }
        }

        $cursor = $this->fetch($conditions);
        $query  = $cursor->getQuery();

        // I am sorry but I have to be punished for I am writing this
        $selectFields = &$query->getFields();
        foreach ($selectFields as $key => $value) {
            unset($selectFields[$key]);
        }
        // Again.
        $tables = &$query->getTables();
        foreach ($tables as $key => $table) {
          unset($tables[$key]['all_fields']);
        }
        $query->fields('m', array('id'));

        // Create a temp table containing identifiers to update: this is
        // mandatory because you cannot use the apb_queue in the UPDATE
        // query subselect
        $tempTableName = $cx
            ->queryTemporary((string)$query, $query->getArguments());

        $select = $cx
            ->select($tempTableName, 'tu')
            ->fields('tu');

        $update = $cx
            ->update('apb_queue');

        $update
            ->fields($fields)
            ->condition('msg_id', $select, 'IN')
            ->execute();
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
                SET unread = :unread, read_timestamp = :time
                WHERE msg_id = :msgid
                  AND sub_id = :subid
            ", array(
                ':unread' => (int)$toggle,
                ':msgid'  => (int)$messageId,
                ':subid'  => $this->id,
                ':time'   => $toggle ? null : time(),
            ));
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
        $this
            ->context
            ->dbConnection
            ->delete('apb_queue')
            ->condition('sub_id', $this->id)
            ->condition('msg_id', $idList, 'IN')
            ->execute();
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::deleteAllMessages()
     */
    public function deleteAllMessages()
    {
        $cx = $this->context->dbConnection;
        $tx = $cx->startTransaction();

        try {
            $cx
              ->delete('apb_queue')
              ->condition('sub_id', $this->id)
              ->execute();

            unset($tx); // Excplicit commit

        } catch (\Exception $e) {
            $tx->rollback();

            throw $e;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::flush()
     */
    public function flush()
    {
        $this->deleteAllMessages();
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::getMessages()
     */
    public function getMessage($id)
    {
        $cursor = $this->fetch(array(
            CursorInterface::FIELD_MSG_ID => $id,
        ));

        if (!count($cursor)) {
            throw new MessageDoesNotExistException();
        }

        foreach ($cursor as $message) {
            return $message;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::getMessages()
     */
    public function getMessages(array $idList)
    {
        $cursor = $this->fetch(array(
            CursorInterface::FIELD_MSG_ID => $idList,
        ));

        if (count($cursor) !== count($idList)) {
            throw new MessageDoesNotExistException();
        }

        return iterator_to_array($cursor);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::getExtraData()
     */
    public function getExtraData()
    {
        return $this->extraData;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::setExtraData()
     */
    public function setExtraData(array $data)
    {
        $this->extraData = $data;

        $this
            ->context
            ->dbConnection
            ->update('apb_sub')
            ->condition('id', $this->id)
            ->fields(array(
                'extra' => serialize($this->extraData),
            ))
            ->execute();
    }
}

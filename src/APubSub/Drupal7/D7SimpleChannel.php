<?php

namespace APubSub\Drupal7;

use APubSub\ChannelInterface;
use APubSub\Error\MessageDoesNotExistException;
use APubSub\Impl\DefaultMessage;
use APubSub\MessageInterface;

/**
 * Array based implementation for unit testing: do not use in production
 */
class D7SimpleChannel extends AbstractD7Object implements ChannelInterface
{
    /**
     * Channel identifier
     *
     * @var string
     */
    protected $id;

    /**
     * Channel database identifier
     *
     * @var int
     */
    protected $dbId;

    /**
     * Current backend
     *
     * @var \APubSub\Drupal7\D7PubSub
     */
    protected $backend;

    /**
     * Creation UNIX timestamp
     *
     * @var int
     */
    protected $created;

    /**
     * Internal constructor
     *
     * @param string $id        Channel identifier
     * @param int $dbId         Channel database identifier
     * @param D7PubSub $backend Backend
     * @param int $created      Creation UNIX timestamp
     */
    public function __construct(D7PubSub $backend, $id, $dbId, $created)
    {
        $this->id = $id;
        $this->dbId = $dbId;
        $this->backend = $backend;
        $this->created = $created;

        $this->setContext($this->backend->getContext());
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::getId()
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * For internal use only: get database identifier
     *
     * @return int Channel database identifier
     */
    public function getDatabaseId()
    {
        return $this->dbId;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::getBackend()
     */
    public function getBackend()
    {
        return $this->backend;
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
     * @see \APubSub\ChannelInterface::getMessage()
     */
    public function getMessage($id)
    {
        // FIXME: Could/should use a static cache here? I guess so.
        $record = $this
            ->context
            ->dbConnection
            ->query("SELECT * FROM {apb_msg} WHERE id = :id AND chan_id = :chanId", array(
                ':id' => $id,
                ':chanId' => $this->dbId,
            ))
            ->fetchObject();

        if (!$record) {
            throw new MessageDoesNotExistException();
        }

        return new DefaultMessage($this, unserialize($record->contents), $id, (int)$record->created);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::getMessages()
     */
    public function getMessages($idList)
    {
        // FIXME: See getMessage() for static caching considerations.
        $result = $this
            ->context
            ->dbConnection
            ->select('apb_msg', 'm')
            ->fields('m')
            ->condition('m.id', $idList, 'IN')
            ->execute();

        if (count($idList) !== count($result)) {
            throw new MessageDoesNotExistException();
        }

        $ret = array();

        while ($record = $result->fetchObject()) {
            $ret[] = new DefaultMessage($this,
                unserialize($record->contents),
                (int)$record->id, (int)$record->created);
        }

        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::createMessage()
     */
    public function send($contents, $sendTime = null)
    {
        $cx = $this->context->dbConnection;
        $tx = $cx->startTransaction();
        $id = null;

        if (null === $sendTime) {
            $sendTime = time();
        }

        try {
            $cx
                ->insert('apb_msg')
                ->fields(array(
                    'chan_id' => $this->dbId,
                    'created' => $sendTime,
                    'contents' => serialize($contents),
                ))
                ->execute();

            $id = (int)$cx->lastInsertId();

            // Send message to all subscribers
            $cx
                ->query("
                    INSERT INTO {apb_queue}
                        SELECT :msgId AS msg_id, s.id AS sub_id, 0 AS consumed
                            FROM {apb_sub} s
                            WHERE s.chan_id = :chanId
                    ", array(
                        'msgId' => $id,
                        'chanId' => $this->dbId,
                    ));

            if (!$this->context->delayChecks) {
                $this->backend->cleanUpMessageQueue();
                $this->backend->cleanUpMessageLifeTime();
            }

        } catch (\Exception $e) {
            $tx->rollback();

            throw $e;
        }

        return new DefaultMessage($this, $contents, $id, $sendTime);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::subscribe()
     */
    public function subscribe()
    {
        $deactivated = time();
        $created     = $deactivated;
        $cx          = $this->context->dbConnection;
        $tx          = $cx->startTransaction();

        try {
            $cx
                ->insert('apb_sub')
                ->fields(array(
                    'chan_id' => $this->dbId,
                    'status' => 0,
                    'created' => $created,
                    'deactivated' => $deactivated,
                ))
                ->execute();

            $id = (int)$cx->lastInsertId();

            return new D7SimpleSubscription($this,
                $id, $created, 0, $deactivated, false);

        } catch (\Exception $e) {
            $tx->rollback();

            throw $e;
        }
    }
}

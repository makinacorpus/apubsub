<?php

namespace APubSub\Backend\Drupal7;

use APubSub\Backend\DefaultMessage;
use APubSub\ChannelInterface;
use APubSub\Error\MessageDoesNotExistException;
use APubSub\Error\UncapableException;

/**
 * Drupal 7 simple channel implementation
 *
 * This implementation does not statically cache messages at all: messages are
 * not supposed to be read multiple times, they should never be, and multiple
 * queries on the same message must remain uncommon
 */
class D7SimpleChannel extends AbstractD7Object implements ChannelInterface
{
    /**
     * Channel identifier
     *
     * @var string
     */
    private $id;

    /**
     * Channel database identifier
     *
     * @var int
     */
    private $dbId;

    /**
     * Creation UNIX timestamp
     *
     * @var int
     */
    private $created;

    /**
     * Internal constructor
     *
     * @param string $id         Channel identifier
     * @param int $dbId          Channel database identifier
     * @param D7Context $context Backend
     * @param int $created       Creation UNIX timestamp
     */
    public function __construct(D7Context $context, $id, $dbId, $created)
    {
        $this->id = $id;
        $this->dbId = $dbId;
        $this->created = $created;
        $this->context = $context;
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

        return new DefaultMessage($this->context, $this->id,
            unserialize($record->contents), $id, (int)$record->created);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::getMessages()
     */
    public function getMessages($idList)
    {
        $records = $this
            ->context
            ->dbConnection
            ->select('apb_msg', 'm')
            ->fields('m')
            ->condition('m.id', $idList, 'IN')
            ->execute()
            // Fetch all is mandatory in order for the result to be countable
            ->fetchAll();

        if (count($idList) !== count($records)) {
            throw new MessageDoesNotExistException();
        }

        $ret = array();

        foreach ($records as $record) {
            $ret[] = new DefaultMessage($this->context,
                $this->id, unserialize($record->contents),
                (int)$record->id, (int)$record->created);
        }

        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::send()
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
                        SELECT :msgId AS msg_id, s.id AS sub_id
                            FROM {apb_sub} s
                            WHERE s.chan_id = :chanId
                            AND s.status = 1
                    ", array(
                        'msgId' => $id,
                        'chanId' => $this->dbId,
                    ));

            unset($tx); // Excplicit commit

            if (!$this->context->delayChecks) {
                $this->context->backend->cleanUpMessageQueue();
                $this->context->backend->cleanUpMessageLifeTime();
            }
        } catch (\Exception $e) {
            $tx->rollback();

            throw $e;
        }

        return new DefaultMessage($this->context,
            $this->id, $contents, $id, $sendTime);
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

            $subscription = new D7SimpleSubscription($this->context,
                $this->dbId, $id, $created, 0, $deactivated, false);

            $this->context->cache->addSubscription($subscription);

            return $subscription;

        } catch (\Exception $e) {
            $tx->rollback();

            throw $e;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::getStatHelper()
     */
    public function getStatHelper()
    {
        throw new UncapableException();
    }
}

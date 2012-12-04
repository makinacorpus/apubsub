<?php

namespace APubSub\Backend\Mongo;

use APubSub\Backend\AbstractObject;
use APubSub\Backend\DefaultMessage;
use APubSub\ChannelInterface;
use APubSub\Error\MessageDoesNotExistException;
use APubSub\Error\UncapableException;

class MongoChannel extends AbstractObject implements ChannelInterface
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
     * @param string $id            Channel identifier
     * @param string $dbId          Channel database identifier
     * @param MongoContext $context Backend
     * @param int $created          Creation UNIX timestamp
     */
    public function __construct(MongoContext $context, $id, $dbId, $created)
    {
        $this->id      = $id;
        $this->dbId    = $dbId;
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
        throw new \Exception("Not implemented yet");

        /*
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

        return new DefaultMessage($this->context, $this->id, null,
            unserialize($record->contents), $id, (int)$record->created);
         */
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::getMessages()
     */
    public function getMessages($idList)
    {
        throw new \Exception("Not implemented yet");

        /*
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
                $this->id, null, unserialize($record->contents),
                (int)$record->id, (int)$record->created);
        }

        return $ret;
         */
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::send()
     */
    public function send($contents, $sendTime = null)
    {
        throw new \Exception("Not implemented yet");

        /*
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
                        SELECT
                            :msgId AS msg_id,
                            s.id AS sub_id,
                            1 AS unread,
                            :created AS created
                        FROM {apb_sub} s
                        WHERE s.chan_id = :chanId
                        AND s.status = 1
                    ", array(
                        'msgId' => $id,
                        'chanId' => $this->dbId,
                        ':created' => $sendTime,
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
            $this->id, null, $contents, $id, $sendTime);
         */
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::subscribe()
     */
    public function subscribe()
    {
        throw new \Exception("Not implemented yet");

        /*
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

            $subscription = new MongoSubscription($this->context,
                $this->dbId, $id, $created, 0, $deactivated, false);

            $this->context->cache->addSubscription($subscription);

            return $subscription;

        } catch (\Exception $e) {
            $tx->rollback();

            throw $e;
        }
         */
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::getStatHelper()
     */
    public function getStatHelper()
    {
        throw new UncapableException();
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
        throw new \Exception("Not implemented yet");

        /*
        $cx = $this->context->dbConnection;
        $tx = $cx->startTransaction();

        try {
            $cx
                ->delete('apb_queue')
                ->condition('msg_id', $idList, 'IN')
                ->execute();

            $cx
                ->delete('apb_msg')
                ->condition('id', $idList, 'IN')
                ->execute();

            unset($tx); // Excplicit commit

        } catch (\Exception $e) {
            $tx->rollback();

            throw $e;
        }
         */
    }
}
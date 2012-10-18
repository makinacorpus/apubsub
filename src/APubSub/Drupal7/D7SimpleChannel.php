<?php

namespace APubSub\Drupal7;

use APubSub\ChannelInterface;
use APubSub\Error\MessageDoesNotExistException;
use APubSub\Impl\DefaultMessage;
use APubSub\MessageInterface;

/**
 * Drupal 7 simple channel implementation
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

        $this->setContext($context);
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
        return $this->context->backend;
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

        return new DefaultMessage($this->context,
            unserialize($record->contents), $id, (int)$record->created);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::getMessages()
     */
    public function getMessages($idList)
    {
        // FIXME: See getMessage() for static caching considerations.
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
     * @see \APubSub\ChannelInterface::massSend()
     */
    public function massSend($contentList)
    {
        throw new \Exception("Not implemented yet");
        $ret      = array();
        $cx       = $this->context->dbConnection;
        $tx       = $cx->startTransaction();
        $id       = null;
        $sendTime = time();
        $idList   = array();

        try {
            // This is really bad for performances but because the SQL standard
            // does not allow to do a BULK INSERT and fetch the generated ids
            // at the same time: we're fucked. At least, we can optimise the
            // whole execution by doing the BULK INSERT into the queue only
            // once thanks to those ids being aggregated.
            foreach ($contentList as $contents) {
                $cx
                    ->insert('apb_msg')
                    ->fields(array(
                        'chan_id' => $this->dbId,
                        'created' => $sendTime,
                        'contents' => serialize($contents),
                    ))
                    ->execute();

                $idList[] = $id = (int)$cx->lastInsertId();

                $ret[] = new DefaultMessage($this->context,
                    $contents, $id, $sendTime);
            }

            // Send message to all subscribers
            $cx
                ->query("
                    INSERT INTO {apb_queue}
                        SELECT :msgId AS msg_id, s.id AS sub_id
                            FROM {apb_sub} s
                            WHERE s.chan_id = :chanId
                    ", array(
                        'msgId' => $id,
                        'chanId' => $this->dbId,
                    ));

            if (!$this->context->delayChecks) {
                $this->context->backend->cleanUpMessageQueue();
                $this->context->backend->cleanUpMessageLifeTime();
            }
        } catch (\Exception $e) {
            $tx->rollback();

            throw $e;
        }

        return $ret;
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

            return new D7SimpleSubscription($this->context,
                $this->id, $id, $created, 0, $deactivated, false);

        } catch (\Exception $e) {
            $tx->rollback();

            throw $e;
        }
    }
}

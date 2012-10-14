<?php

namespace APubSub\Drupal7;

use APubSub\ChannelInterface;
use APubSub\Error\MessageDoesNotExistException;
use APubSub\Impl\DefaultMessage;
use APubSub\MessageInterface;

/**
 * Array based implementation for unit testing: do not use in production
 */
class D7SimpleChannel implements ChannelInterface
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
     * @var \DatabaseConnection
     */
    protected $dbConnection;

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
        $this->dbConnection = $this->backend->getConnection();
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
    {throw new \Exception("Not implemented yet");
        if (!isset($this->messages[$id])) {
            throw new MessageDoesNotExistException();
        }

        return $this->messages[$id];
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::createMessage()
     */
    public function createMessage($contents, $sendTime = null)
    {
        return new DefaultMessage($this, $contents, null, $sendTime);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::sendMessage()
     */
    public function send(MessageInterface $message)
    {
        if (!$message instanceof DefaultMessage || $message->getChannel() !== $this) {
            throw new \LogicException(
                "You are trying to inject a message which does not originate from this channel");
        }

        // FIXME: Also ensure the message has not been already sent

        $created = time();
        $tx      = $this->dbConnection->startTransaction();

        try {
            $this
                ->dbConnection
                ->insert('apb_msg')
                ->fields(array(
                    'chan_id' => $this->dbId,
                    'created' => $created,
                    'contents' => serialize($message->getContents()),
                ))
                ->execute();

            $id = $this->dbConnection->lastInsertId();

            $message->setId($id);
            $message->setSendTimestamp($created);

            /*
             * FIXME: Propagate messages to subscribers
             * 
            foreach ($this->subscriptions as $subscription) {
                if ($subscription->isActive()) {
                    $subscription->addMessage($message);
                }
            }
             */
        } catch (\Exception $e) {
            $tx->rollback();

            throw $e;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::subscribe()
     */
    public function subscribe()
    {
        $deactivated = time();
        $created     = $deactivated;
        $tx          = $this->dbConnection->startTransaction();

        try {
            $this
                ->dbConnection
                ->insert('apb_sub')
                ->fields(array(
                    'chan_id' => $this->dbId,
                    'status' => 0,
                    'created' => $created,
                    'deactivated' => $deactivated,
                ))
                ->execute();

            $id = $this->dbConnection;

            return new D7SimpleSubscription($this,
                $id, $created, 0, $deactivated, false);

        } catch (\Exception $e) {
            $tx->rollback();

            throw $e;
        }
    }
}

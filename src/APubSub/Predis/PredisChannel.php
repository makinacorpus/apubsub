<?php

namespace APubSub\Predis;

use APubSub\ChannelInterface;
use APubSub\Error\MessageDoesNotExistException;
use APubSub\Impl\DefaultMessage;
use APubSub\MessageInterface;

/**
 * Array based implementation for unit testing: do not use in production
 */
class PredisChannel implements ChannelInterface
{
    /**
     * Channel identifier
     *
     * @var string
     */
    protected $id;

    /**
     * Current backend
     *
     * @var \APubSub\Predis\PredisPubSub
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
     * @param PredisPubSub $backend Backend
     * @param string $id            Channel identifier
     * @param int $created          Creation UNIX timestamp
     */
    public function __construct(PredisPubSub $backend, $id, $created)
    {
        $this->id = $id;
        $this->backend = $backend;
        $this->created = $created;
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
        throw new \Exception("Not implemented yet");
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
        throw new \Exception("Not implemented yet");
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
        $client  = $this->backend->getPredisClient();
        $id      = $this->backend->getNextId('sub');
        $subKey  = $this->backend->getKeyName(PredisPubSub::KEY_PREFIX_SUB . $id);
        $active  = 0;
        $now     = time();

        $client->hmset($subKey, array(
            "created"     => $now,
            "active"      => 0,
            "activated"   => 0,
            "deactivated" => $now
        ));

        return new PredisSubscription($this, $id, $now, 0, $now, false);
    }
}

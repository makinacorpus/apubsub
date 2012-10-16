<?php

namespace APubSub\Predis;

use APubSub\ChannelInterface;
use APubSub\Error\MessageDoesNotExistException;
use APubSub\Impl\DefaultMessage;
use APubSub\MessageInterface;

/**
 * Array based implementation for unit testing: do not use in production
 */
class PredisChannel extends AbstractPredisObject implements ChannelInterface
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

        $this->setContext($backend->getContext());
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
     * @see \APubSub\ChannelInterface::getMessage()
     */
    public function getMessages($idList)
    {
        throw new \Exception("Not implemented yet");
        if (!isset($this->messages[$id])) {
          throw new MessageDoesNotExistException();
        }

        return $this->messages[$id];
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::send()
     */
    public function send($contents, $sendTime = null)
    {
        throw new \Exception("Not implemented yet");

        $client  = $this->context->client;
        $id      = $this->context->getNextId('sub');
        $msgKey  = $this->context->getKeyName(PredisContext::KEY_PREFIX_MSG . $id);
        $created = time();

        $client->pipeline(function ($pipe) use ($msgKey, $created, $id, $contents) {

            // Save the message 
            $pipe->hset($msgKey, array(
                "id"         => $id,
                "created"    => $created,
                "contents"   => is_scalar($contents) ? $contents : serialize($contents),
                "serialized" => is_scalar($contents),
            ));

            // Iterate over all subscribers and set the message there
            
        });
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::subscribe()
     */
    public function subscribe()
    {
        $client  = $this->context->client;
        $id      = $this->context->getNextId('sub');
        $subKey  = $this->context->getKeyName(PredisContext::KEY_PREFIX_SUB . $id);
        $active  = 0;
        $now     = time();

        $client->hmset($subKey, array(
            "id"          => $id,
            "created"     => $now,
            "active"      => 0,
            "activated"   => 0,
            "deactivated" => $now,
        ));

        return new PredisSubscription($this, $id, $now, 0, $now, false);
    }
}

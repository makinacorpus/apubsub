<?php

namespace APubSub\Backend;

use APubSub\ChannelInterface;
use APubSub\ContextInterface;
use APubSub\Field;

/**
 * Default implementation of the channel interface that would fit most backends
 */
class DefaultChannel extends AbstractMessageContainer implements ChannelInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var int
     */
    private $creationTime;

    /**
     * @var string
     */
    private $title;

    /**
     * Default constructor
     *
     * @param string $id        Channel identifier
     * @param ContextInterface  Context
     * @param int $creationTime Creation time UNIX timestamp
     * @param string $title     Human readable title
     */
    public function __construct($id, ContextInterface $context, $creationTime = null, $title = null)
    {
        parent::__construct($context, array(
            Field::CHAN_ID => $id,
        ));

        $this->id = $id;
        $this->title = $title;

        if (null === $creationTime) {
            $this->creationTime = time();
        } else {
            $this->creationTime = $creationTime;
        }
    }

    final public function getId()
    {
        return $this->id;
    }

    final public function getTitle()
    {
        return $this->title;
    }

    final public function setTitle($title)
    {
        if ($this->title !== $title) {

            $this
                ->context
                ->getBackend()
                ->fetchChannels(array(
                    Field::CHAN_ID => $this->getId(),
                ))
                ->update(array(
                    Field::CHAN_TITLE => $title,
                ));

            $this->title = $title;
        }
    }

    final public function getCreationTime()
    {
        return $this->creationTime;
    }

    final public function send($contents, $type = null, $level = 0, $sendTime = null)
    {
        return $this
            ->context
            ->getBackend()
            ->send(
                $this->id,
                $contents,
                $type,
                $level,
                $sendTime);
    }

    final public function subscribe()
    {
        return $this
            ->context
            ->getBackend()
            ->subscribe($this->id);
    }
}

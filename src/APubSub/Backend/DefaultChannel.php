<?php

namespace APubSub\Backend;

use APubSub\BackendInterface;
use APubSub\ChannelInterface;
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
     * @param string $id
     *   Channel identifier
     * @param BackendInterface $backend
     *   Backend
     * @param int $creationTime
     *   Creation time UNIX timestamp
     * @param string $title
     *   Human readable title
     */
    public function __construct($id, BackendInterface $backend, $creationTime = null, $title = null)
    {
        parent::__construct($backend, [Field::CHAN_ID => $id]);

        $this->id = $id;
        $this->title = $title;

        if (null === $creationTime) {
            $this->creationTime = time();
        } else {
            $this->creationTime = $creationTime;
        }
    }

    /**
     * {@inheritdoc}
     */
    final public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    final public function getTitle()
    {
        return $this->title;
    }

    /**
     * {@inheritdoc}
     */
    final public function setTitle($title)
    {
        if ($this->title !== $title) {

            $this
                ->getBackend()
                ->fetchChannels([Field::CHAN_ID => $this->getId()])
                ->update([Field::CHAN_TITLE => $title])
            ;

            $this->title = $title;
        }
    }

    /**
     * {@inheritdoc}
     */
    final public function getCreationTime()
    {
        return $this->creationTime;
    }

    /**
     * {@inheritdoc}
     */
    final public function send(
        $contents,
        $type           = null,
        $level          = 0,
        array $excluded = null,
        $sendTime       = null)
    {
        return $this
            ->getBackend()
            ->send(
                $this->id,
                $contents,
                $type,
                $level,
                $excluded,
                $sendTime
            )
        ;
    }

    final public function subscribe()
    {
        return $this
            ->getBackend()
            ->subscribe($this->id)
        ;
    }
}

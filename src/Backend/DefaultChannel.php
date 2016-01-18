<?php

namespace MakinaCorpus\APubSub\Backend;

use MakinaCorpus\APubSub\BackendInterface;
use MakinaCorpus\APubSub\ChannelInterface;
use MakinaCorpus\APubSub\Field;

/**
 * Default implementation of the channel interface that would fit most backends
 */
class DefaultChannel extends AbstractMessageContainer implements ChannelInterface
{
    /**
     * Internal database identifier, this is not part of the interface but I
     * guess that most backends will need this
     *
     * @var int
     */
    private $databaseId;

    /**
     * @var string
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var \DateTime
     */
    private $updatedAt;

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
     * @param \DateTime $createdAt
     *   Creation date
     * @param \DateTime $updatedAt
     *   Update date
     * @param string $title
     *   Human readable title
     * @param int $databaseId
     *   Arbitrary database identifier if the backend needs it for performance
     *   or consistency reasons
     */
    public function __construct($id, BackendInterface $backend, \DateTime $createdAt = null, \DateTime $updatedAt = null, $title = null, $databaseId = null)
    {
        parent::__construct($backend, [Field::CHAN_ID => $id]);

        $this->id = $id;
        $this->title = $title;

        if (null === $createdAt) {
            $this->createdAt = new \DateTime();
        } else {
            $this->createdAt = $createdAt;
        }

        if (null === $updatedAt) {
            $this->updatedAt = clone $this->createdAt;
        } else {
            $this->updatedAt = $updatedAt;
        }

        $this->databaseId = $databaseId;
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
                ->backend
                ->fetchChannels([Field::CHAN_ID => $this->getId()])
                ->update([Field::CHAN_TITLE => $title])
            ;

            $this->title = $title;
        }
    }

    /**
     * {@inheritdoc}
     */
    final public function getCreationDate()
    {
        return $this->createdAt;
    }

    /**
     * {@inheritdoc}
     */
    final public function getLatestUpdateDate()
    {
        return $this->updatedAt;
    }

    /**
     * {@inheritdoc}
     */
    final public function send(
        $contents,
        $type             = null,
        $origin           = null,
        $level            = 0,
        array $excluded   = null,
        \DateTime $sentAt = null)
    {
        return $this
            ->backend
            ->send(
                $this->id,
                $contents,
                $type,
                $origin,
                $level,
                $excluded,
                $sentAt
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    final public function subscribe()
    {
        return $this->backend->subscribe($this->id);
    }

    /**
     * Get internal database identifier
     *
     * @return int Database identifier
     */
    final public function getDatabaseId()
    {
        return $this->databaseId;
    }
}

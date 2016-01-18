<?php

namespace MakinaCorpus\APubSub\Backend;

use MakinaCorpus\APubSub\BackendInterface;
use MakinaCorpus\APubSub\Field;
use MakinaCorpus\APubSub\SubscriptionInterface;
use MakinaCorpus\APubSub\Misc;

class DefaultSubscription extends AbstractMessageContainer implements
    SubscriptionInterface
{
    /**
     * @var scalar
     */
    private $id;

    /**
     * @var string
     */
    private $chanId;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var bool
     */
    private $active = false;

    /**
     * @var \DateTime
     */
    private $activatedAt;

    /**
     * @var \DateTime
     */
    private $deactivatedAt;

    /**
     * @var \DateTime
     */
    private $accessedAt;

    /**
     * @var string
     */
    private $subscriberId;

    /**
     * Default constructor
     *
     * @param int $chanId
     *   Channel identifier
     * @param int $id
     *   Subscription identifier
     * @param \DateTime $createdAt
     *   Creation date
     * @param \DateTime $activatedAt
     *   Latest activation date
     * @param \DateTime $deactivatedAt
     *   Latest deactivation date
     * @param \DateTime $accessedAt
     *   Latest access time
     * @param bool $isActive
     *   Is this subscription active
     * @param string $subscriberId
     *   Subscriber identifier
     * @param BackendInterface $backend
     *   Backend
     */
    public function __construct(
        $chanId,
        $id,
        \DateTime $createdAt,
        \DateTime $activatedAt,
        \DateTime $deactivatedAt,
        \DateTime $accessedAt = null,
        $isActive = false,
        $subscriberId = null,
        BackendInterface $backend
    ) {
        parent::__construct($backend, [Field::SUB_ID => $id]);

        $this->id = $id;
        $this->chanId = $chanId;
        $this->createdAt = $createdAt;
        $this->activatedAt = $activatedAt;
        $this->deactivatedAt = $deactivatedAt;
        $this->accessedAt = $accessedAt;
        $this->active = $isActive;
        $this->subscriberId = $subscriberId;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getChannelId()
    {
        return $this->chanId;
    }

    /**
     * {@inheritdoc}
     */
    public function getChannel()
    {
        return $this->backend->getChannel($this->chanId);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreationDate()
    {
        return $this->createdAt;
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * {@inheritdoc}
     */
    public function getStartDate()
    {
        if (!$this->active) {
            throw new \LogicException("This subscription is not active");
        }

        return $this->activatedAt;
    }

    /**
     * {@inheritdoc}
     */
    public function getStopDate()
    {
        if ($this->active) {
            throw new \LogicException("This subscription is active");
        }

        return $this->deactivatedAt;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessDate()
    {
        return $this->accessedAt;
    }

    /**
     * {@inheritdoc}
     */
    public function hasSubscriber()
    {
        return null !== $this->subscriberId;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscriberId()
    {
        return $this->subscriberId;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscriber()
    {
        if (null === $this->subscriberId) {
            throw new \LogicException("This subscription does not belong to a subscriber");
        }

        return $this->backend->getSubscriber($this->subscriberId);
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate()
    {
        $deactivated = new \DateTime();

        $this
            ->backend
            ->fetchSubscriptions([
                Field::SUB_ID => $this->id,
            ])
            ->update([
                Field::SUB_STATUS => 0,
                Field::SUB_DEACTIVATED => $deactivated->format(Misc::SQL_DATETIME),
            ])
        ;

        $this->active = false;
        $this->deactivatedAt = $deactivated;
    }

    /**
     * {@inheritdoc}
     */
    public function activate()
    {
        $activated = new \DateTime();

        $this
            ->backend
            ->fetchSubscriptions([
                Field::SUB_ID => $this->id,
            ])
            ->update([
                Field::SUB_STATUS => 1,
                Field::SUB_DEACTIVATED => $activated->format(Misc::SQL_DATETIME),
            ])
        ;

        $this->active = true;
        $this->activatedAt = $activated;
    }
}

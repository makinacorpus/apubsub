<?php

namespace APubSub\Drupal7;

use APubSub\Impl\DefaultMessage;
use APubSub\SubscriptionInterface;

/**
 * Array based implementation for unit testing: do not use in production
 */
class D7SimpleSubscription extends AbstractD7Object implements
    SubscriptionInterface
{
    /**
     * Message identifier
     *
     * @var scalar
     */
    protected $id;

    /**
     * Channel this message belongs to
     *
     * @var \APubSub\Drupal7\D7SimpleChannel
     */
    protected $channel;

    /**
     * Creation UNIX timestamp
     *
     * @var int
     */
    protected $created;

    /**
     * Is this subscription active
     *
     * @var bool
     */
    protected $active = false;

    /**
     * Time when this subscription has been activated for the last time as a
     * UNIX timestamp
     *
     * @var int
     */
    protected $activatedTime;

    /**
     * Time when this subscription has been deactivated for the last time as a
     * UNIX timestamp
     *
     * @var int
     */
    protected $deactivatedTime;

    /**
     * Default constructor
     *
     * @param D7SimpleChannel $channel Channel this message belongs to
     * @param int $id                  Subscription identifier
     * @param int $created             Creation UNIX timestamp
     * @param int $activatedTime       Latest activation UNIX timestamp
     * @param int $deactivatedTime     Latest deactivation UNIX timestamp
     * @param bool $isActive           Is this subscription active
     */
    public function __construct(D7SimpleChannel $channel, $id,
        $created, $activatedTime, $deactivatedTime, $isActive)
    {
        $this->id = $id;
        $this->channel = $channel;
        $this->created = $created;
        $this->activatedTime = $activatedTime;
        $this->deactivatedTime = $deactivatedTime;
        $this->active = $isActive;

        $this->setContext($this->channel->getContext());
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::getId()
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::getChannel()
     */
    public function getChannel()
    {
        return $this->channel;
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
     * @see \APubSub\SubscriptionInterface::isActive()
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::getStartTime()
     */
    public function getStartTime()
    {
        if (!$this->active) {
            throw new \LogicException("This subscription is not active");
        }

        return $this->activatedTime;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::getStopTime()
     */
    public function getStopTime()
    {
        if ($this->active) {
            throw new \LogicException("This subscription is active");
        }

        return $this->deactivatedTime;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::delete()
     */
    public function delete()
    {
        $this
            ->getChannel()
            ->getBackend()
            ->deleteSubscription($this->getId());
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::fetch()
     */
    public function fetch()
    {
        $ret = array();
        $cx  = $this->context->dbConnection;

        $idList = $cx
            // Don't care about sort hopefully the items will be naturally
            // ordered by insertion time even thought this is not guaranteed
            // by any SQL standard
            ->query("SELECT msg_id FROM {apb_queue} WHERE sub_id = :id AND consumed = 0", array(
                ':id' => $this->id,
            ))
            ->fetchCol();

        if (empty($idList)) {
            return $ret;
        }

        $ret = $this->channel->getMessages($idList);

        // Delete/update using sub_id instead would allow newly queued message
        // during our own processing to be deleted: can't do this. Hence the
        // WHERE IN condition on $idList 
        if ($this->context->keepMessages) {
            $cx
                ->update('apb_queue')
                ->fields(array(
                    'consumed' => 1,
                ))
                ->condition('sub_id', $this->id)
                ->condition('msg_id', $idList, 'IN')
                ->execute();
        } else {
          $cx
              ->delete('apb_queue')
              ->condition('sub_id', $this->id)
              ->condition('msg_id', $idList, 'IN')
              ->execute();
        }

        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::deactivate()
     */
    public function deactivate()
    {
        $deactivated = time();

        $this
            ->context
            ->dbConnection
            ->query("UPDATE {apb_sub} SET status = 0, deactivated = :deactivated WHERE id = :id", array(
                ':deactivated' => $deactivated,
                ':id' => $this->id,
            ));

        $this->active = false;
        $this->deactivatedTime = $deactivated;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::activate()
     */
    public function activate()
    {
        $activated = time();

        $this
            ->context
            ->dbConnection
            ->query("UPDATE {apb_sub} SET status = 1, activated = :activated WHERE id = :id", array(
                ':activated' => $activated,
                ':id' => $this->id,
            ));

        $this->active = true;
        $this->activatedTime = $activated;
    }
}

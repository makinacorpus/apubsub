<?php

namespace APubSub\Backend\Drupal7;

use APubSub\Backend\DefaultSubscriber;
use APubSub\CursorInterface;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\SubscriberInterface;

/**
 * Drupal 7 simple subscriber implementation
 *
 * @todo Remove this class if possible
 */
class D7Subscriber extends DefaultSubscriber implements
    SubscriberInterface
{
    /**
     * Default constructor
     *
     * @param D7Context $context  Backend that owns this instance
     * @param scalar $id          User set identifier
     * @param int $lastAccessTime Last access time
     */
    public function __construct(D7Context $context, $id, $lastAccessTime = 0)
    {
        parent::__construct($id, $context, $lastAccessTime);

        // Get subscription identifiers list, with channel mapping
        $this->idList = $this
            ->context
            ->dbConnection
            // This query will also remove non existing stalling subscriptions
            // from the subscriber map thanks to the JOIN statements, thus
            // avoiding potential exceptions being thrown at single subscription
            // get time
            ->query("
                SELECT c.name, mp.sub_id
                    FROM {apb_sub_map} mp
                    JOIN {apb_sub} s ON s.id = mp.sub_id
                    JOIN {apb_chan} c ON c.id = s.chan_id
                    WHERE mp.name = :name", array(
                ':name' => $this->getId(),
            ))
            ->fetchAllKeyed();
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::subscribe()
     */
    public function subscribe($chanId)
    {
        try {
            if (isset($this->idList[$chanId])) {
                // See the getSubscriptionFor() implementation
                return $this
                    ->context
                    ->getBackend()
                    ->getSubscription($this->idList[$chanId]);
            }
        } catch (SubscriptionDoesNotExistException $e) {
            // Someone else deleted this subscription and we have cached it in
            // a wrong state, leave this method run until this end which will
            // recreate the subscription
        }

        $activated = time();
        $created   = $activated;
        $cx        = $this->context->dbConnection;
        $tx        = null;

        try {
            $tx = $cx->startTransaction();

            // Load the channel only once the subscription load has been attempted,
            // this ensures a few static caches may have been built ahead of us
            $chan = $this
                ->context
                ->getBackend()
                ->getChannel($chanId);

            $cx
                ->insert('apb_sub')
                ->fields(array(
                    'chan_id'   => $chan->getDatabaseId(),
                    'status'    => 1,
                    'created'   => $created,
                    'activated' => $activated,
                ))
                ->execute();

            $id = (int)$cx->lastInsertId();

            $cx
                ->insert('apb_sub_map')
                ->fields(array(
                    'name' => $this->getId(),
                    'sub_id' => $id,
                ))
                ->execute();

            unset($tx); // Explicit commit

            $subscription = new D7Subscription(
                $chanId, $id, $created,
                $activated, 0, true, $this->context);

            // Also ensure a few static caches are setup in the global context:
            // this will be the only cache object instance direct access from
            // this class
            $this->context->cache->addSubscription($subscription);
            $this->idList[$chanId] = $id;

            return $subscription;

        } catch (\Exception $e) {
            if (isset($tx)) {
                try {
                    $tx->rollback();
                } catch (\Exception $e2) {}
            }

            throw $e;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::touch()
     */
    public function touch()
    {
        $this->lastAccessTime = time();

        $this
            ->context
            ->dbConnection
            ->update('apb_sub_map')
            ->condition('name', $this->getId())
            ->fields(array(
                'accessed' => $this->lastAccessTime,
            ))
            ->execute();
    }
}

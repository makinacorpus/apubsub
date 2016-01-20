<?php

namespace MakinaCorpus\APubSub\Backend;

use MakinaCorpus\APubSub\BackendInterface;
use MakinaCorpus\APubSub\Error\ChannelDoesNotExistException;
use MakinaCorpus\APubSub\Error\SubscriptionDoesNotExistException;
use MakinaCorpus\APubSub\Field;
use MakinaCorpus\APubSub\Misc;

/**
 * Common base implementation for most backends
 */
abstract class AbstractBackend implements BackendInterface
{
    /**
     * {@inheritdoc}
     */
    public function getChannel($id)
    {
        $cursor = $this->fetchChannels(array(
            Field::CHAN_ID => $id,
        ));

        $ret = iterator_to_array($cursor);

        if (1 !== count($ret)) {
            throw new ChannelDoesNotExistException();
        }

        return reset($ret);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteChannel($id, $ignoreErrors = false)
    {
        $cursor = $this->fetchChannels(array(
            Field::CHAN_ID => $id,
        ));

        if (!$ignoreErrors && !count($cursor)) {
            throw new ChannelDoesNotExistException();
        }

        $cursor->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteChannels($idList, $ignoreErrors = false)
    {
        $cursor = $this->fetchChannels(array(
            Field::CHAN_ID => $idList,
        ));

        if (!$ignoreErrors && !count($cursor) !== count($idList)) {
            throw new ChannelDoesNotExistException();
        }

        $cursor->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function getChannels($idList)
    {
        $cursor = $this->fetchChannels(array(
            Field::CHAN_ID => $idList,
        ));

        $ret = iterator_to_array($cursor);

        if (count($idList) !== count($ret)) {
            throw new ChannelDoesNotExistException();
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscription($id)
    {
        $cursor = $this->fetchSubscriptions(array(
            Field::SUB_ID => $id,
        ));

        $ret = iterator_to_array($cursor);

        if (1 !== count($ret)) {
            throw new SubscriptionDoesNotExistException();
        }

        return reset($ret);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscriptions($idList)
    {
        if (empty($idList)) {
            return array();
        }

        $cursor = $this->fetchSubscriptions(array(
            Field::SUB_ID => $idList,
        ));

        $ret = iterator_to_array($cursor);

        if (count($idList) !== count($ret)) {
            throw new SubscriptionDoesNotExistException();
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function setUnread($queueId, $toggle = false)
    {
        $this
            ->fetch(array(
                Field::MSG_QUEUE_ID => $queueId
            ))
            ->update(array(
                Field::MSG_UNREAD  => $toggle,
                Field::MSG_READ_TS => (new \DateTime())->format(Misc::SQL_DATETIME),
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $this
            ->fetch()
            ->delete();
    }
}

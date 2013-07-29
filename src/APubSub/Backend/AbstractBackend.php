<?php

namespace APubSub\Backend;

use APubSub\BackendInterface;
use APubSub\Error\ChannelDoesNotExistException;
use APubSub\Field;
use APubSub\Error\SubscriptionDoesNotExistException;

/**
 * Common base implementation for most backends
 */
abstract class AbstractBackend extends AbstractObject implements
    BackendInterface
{
    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::setOptions()
     */
    public function setOptions(array $options)
    {
        $this
            ->context
            ->setOptions($options);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::getChannel()
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
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::getChannels()
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

        return reset($ret);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::getSubscription()
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
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::getSubscription()
     */
    public function getSubscriptions($idList)
    {
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
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::setUnread()
     */
    public function setUnread($queueId, $toggle = false)
    {
        $this
            ->context
            ->getBackend()
            ->fetch(array(
                Field::MSG_QUEUE_ID => $queueId
            ))
            ->update(array(
                Field::MSG_UNREAD  => $toggle,
                Field::MSG_READ_TS => time(),
            ));
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::flush()
     */
    public function flush()
    {
        $this
            ->fetch()
            ->delete();
    }
}

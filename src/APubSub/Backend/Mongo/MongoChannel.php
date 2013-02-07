<?php

namespace APubSub\Backend\Mongo;

use APubSub\Backend\AbstractObject;
use APubSub\Backend\DefaultMessage;
use APubSub\ChannelInterface;
use APubSub\Error\MessageDoesNotExistException;
use APubSub\Error\UncapableException;

class MongoChannel extends AbstractObject implements ChannelInterface
{
    /**
     * Channel identifier
     *
     * @var string
     */
    private $id;

    /**
     * Channel database identifier
     *
     * @var int
     */
    private $dbId;

    /**
     * Creation UNIX timestamp
     *
     * @var int
     */
    private $created;

    /**
     * Internal constructor
     *
     * @param string $id            Channel identifier
     * @param string $dbId          Channel database identifier
     * @param MongoContext $context Backend
     * @param int $created          Creation UNIX timestamp
     */
    public function __construct(MongoContext $context, $id, $dbId, $created)
    {
        $this->id      = $id;
        $this->dbId    = $dbId;
        $this->created = $created;
        $this->context = $context;
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
     * For internal use only: get database identifier
     *
     * @return int Channel database identifier
     */
    public function getDatabaseId()
    {
        return $this->dbId;
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

        /*
        $record = $this
            ->context
            ->dbConnection
            ->query("SELECT * FROM {apb_msg} WHERE id = :id AND chan_id = :chanId", array(
                ':id' => $id,
                ':chanId' => $this->dbId,
            ))
            ->fetchObject();

        if (!$record) {
            throw new MessageDoesNotExistException();
        }

        return new DefaultMessage($this->context, $this->id, null,
            unserialize($record->contents), $id, (int)$record->created);
         */
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::getMessages()
     */
    public function getMessages($idList)
    {
        throw new \Exception("Not implemented yet");

        /*
        $records = $this
            ->context
            ->dbConnection
            ->select('apb_msg', 'm')
            ->fields('m')
            ->condition('m.id', $idList, 'IN')
            ->execute()
            // Fetch all is mandatory in order for the result to be countable
            ->fetchAll();

        if (count($idList) !== count($records)) {
            throw new MessageDoesNotExistException();
        }

        $ret = array();

        foreach ($records as $record) {
            $ret[] = new DefaultMessage($this->context,
                $this->id, null, unserialize($record->contents),
                (int)$record->id, (int)$record->created);
        }

        return $ret;
         */
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::send()
     */
    public function send($contents, $sendTime = null)
    {
        $chanId  = new \MongoId($this->dbId);
        $created = $sendTime ? $sendTime : time();
        $msgId   = new \MongoId();

        //
        // @todo Most unperformant method EVER
        //
        // MongoDB cant do aggregated insert, and bulk insert does not seem
        // to be advised to do as soon as you have more than one server, so
        // we are fucked here.
        //
        // Real @todo:
        //  - check subscription count and put a subscription threshold for
        //    mass sending
        //  - if we are above this threshold, write somewhere that we need
        //    to send this message (in a queue) and process queues during
        //  - the backend garbageCollection() call
        //  - make this threshold configurable so we can easily test it
        //
        $cursor = $this
            ->context
            ->subCollection
            ->find(array(
                'chan_id' => $chanId,
            ));

        // I sincerely hope that Mongo won't attempt to fetch all at once and
        // will load them sequentially instead, else we'll be damned!
        foreach ($cursor as $record) {
            $this
                ->context
                ->queueCollection
                ->insert(
                    array(
                        'msg_id'    => $msgId,
                        'sub_id'    => new \MongoId((int)$record['_id']),
                        'chan_id'   => $chanId,
                        'chan_name' => $this->id,
                        'created'   => $created,
                        'contents'  => new \MongoBinData(serialize($contents)),
                    ), array(
                        'safe' => false,
                    )
                );
        }

        return new DefaultMessage($this->context,
            $this->id, (string)$msgId, $contents, 'whatwhat', $created);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::subscribe()
     */
    public function subscribe()
    {
        $created = time();
        $subId   = new \MongoId();

        $this
            ->context
            ->subCollection
            ->insert(
                array(
                    '_id'         => $subId,
                    'chan_id'     => new \MongoId($this->dbId),
                    'status'      => false,
                    'chan_name'   => $this->id,
                    'subscriber'  => null,
                    'deactivated' => $created,
                    'activated'   => 0,
                    'created'     => $created,
                ),
                array(
                    'safe' => true,
                )
            );

        $subscription = new MongoSubscription($this->context,
            (string)$subId, $this->dbId, $this->id, $created, 0, $created, false);

        $this->context->cache->addSubscription($subscription);

        return $subscription;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::deleteMessage()
     */
    public function deleteMessage($id)
    {
        $this->deleteMessages(array($id));
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::deleteMessages()
     */
    public function deleteMessages(array $idList)
    {
        $chanId = new \MongoId($this->dbId);

        foreach ($idList as $key => $id) {
            $idList[$key] = new \MongoId($id);
        }

        $this
            ->context
            ->queueCollection
            ->remove(array(
                'chan_id' => $chanId,
                'msg_id' => array(
                    '$in' => $idList,
                ),
            ));
    }
}

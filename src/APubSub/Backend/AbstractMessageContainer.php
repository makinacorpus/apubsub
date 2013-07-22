<?php

namespace APubSub\Backend;

use APubSub\ContextInterface;
use APubSub\MessageContainerInterface;
use APubSub\CursorInterface;
use APubSub\Error\MessageDoesNotExistException;

/**
 * Default implementation of the message container interface that would fit most
 * objects for most backends
 */
abstract class AbstractMessageContainer extends AbstractObject implements
    MessageContainerInterface
{
    /**
     * Default conditions to force for each operation on this object
     *
     * @var array
     *   Key value pairs where keys are field names and values are either
     *   a single mixed value or an array of mixed values that represent
     *   the conditions
     */
    protected $invariant = array();

    /**
     * Default constructor
     *
     * @param ContextInterface $context Context
     * @param array $invariant          Default filters for cursors
     */
    public function __construct(ContextInterface $context, array $invariant = null)
    {
        parent::__construct($context);

        if (!empty($invariant)) {
            $this->invariant = $invariant;
        }
    }

    /**
     * Build conditions array
     *
     * @param array $conditions Previous conditions to override
     *
     * @return array            Conditions with invariant
     */
    final private function ensureConditions(array $conditions = null)
    {
        if (empty($conditions)) {
            return $this->invariant;
        }
        if (empty($this->invariant)) {
            return $conditions;
        }

        // FIXME: Should this throw an exception for conflicting keys?
        foreach ($this->invariant as $key => $value) {
            // foreach() loop is mandatory because array_merge() does not
            // preserve numeric keys
            $conditions[$key] = $value;
        }

        return $conditions;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::deleteMessage()
     */
    public function deleteMessage($id)
    {
        return $this->deleteMessages($this->ensureConditions(array(
            CursorInterface::FIELD_MSG_ID => $id,
        )));
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::deleteMessages()
     */
    public function deleteMessages(array $conditions = null)
    {
        return $this
            ->context
            ->getBackend()
            ->deleteMessages($this->ensureConditions($conditions));
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::deleteAllMessages()
     */
    public function deleteAllMessages()
    {
        return $this->deleteMessages($this->invariant);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::getMessage()
     */
    public function getMessage($id)
    {
        $msgList = $this->getMessages(array($id));

        return reset($msgList);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::getMessages()
     */
    public function getMessages(array $idList)
    {
        $expectedCount = count($idList);

        if (0 === $expectedCount) {
            return array();
        }

        $cursor = $this
            ->context
            ->getBackend()
            ->fetch($this->ensureConditions(array(
                CursorInterface::FIELD_MSG_ID => $idList,
            )))
            ->setLimit(count($idList));

        if ($expectedCount !== count($cursor)) {
            throw new MessageDoesNotExistException();
        }

        $messageList = iterator_to_array($cursor);

        // Sort back the message list depending on given identifiers list for
        // consistency even thought this not required per signature
        if (1 === $expectedCount) {
            return $messageList;
        } else {
            return array_multisort($idList, $messageList); 
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::fetch()
     */
    public function fetch(array $conditions = null)
    {
        return $this
            ->context
            ->getBackend()
            ->fetch($this->ensureConditions($conditions));
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::update()
     */
    public function update(array $values, array $conditions = null)
    {
        return $this
            ->context
            ->getBackend()
            ->update(
                $values,
                $this->ensureConditions($conditions));
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::flush()
     */
    public function flush()
    {
        return $this->deleteAllMessages();
    }
}

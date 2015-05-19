<?php

namespace APubSub\Backend;

use APubSub\BackendInterface;
use APubSub\MessageInterface;

/**
 * Default message implementation suitable for most backends
 */
class DefaultMessage implements MessageInterface
{
    /**
     * Message identifier
     *
     * @var scalar
     */
    private $id;

    /**
     * Message type
     *
     * @return string
     */
    private $type;

    /**
     * Message raw data
     *
     * @var mixed
     */
    private $contents;

    /**
     * @var int
     */
    private $level;

    /**
     * @var BackendInterface
     */
    private $backend;

    /**
     * Default constructor
     *
     * @param BackendInterface $backend
     *   Backend
     * @param mixed $contents
     *   Message contents
     * @param scalar $id
     *   Message identifier
     * @param string $type
     *   Message type
     * @param int $level
     *   Level
     */
    public function __construct(
        BackendInterface $backend,
        $contents,
        $id,
        $type          = null,
        $level         = 0)
    {
        $this->backend  = $backend;
        $this->id       = $id;
        $this->contents = $contents;
        $this->type     = $type;
        $this->level    = $level;
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
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * {@inheritdoc}
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * {@inheritdoc}
     */
    public function getBackend()
    {
        return $this->backend;
    }
}

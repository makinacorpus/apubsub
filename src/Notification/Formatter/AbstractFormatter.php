<?php

namespace MakinaCorpus\APubSub\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\FormatterInterface;
use MakinaCorpus\APubSub\Notification\Notification;

/**
 * Abstract base implementation for formatter interface suitable for most needs
 */
abstract class AbstractFormatter implements FormatterInterface
{
    /**
     * @var string
     */
    private $type;

    /**
     * Default constructor
     *
     * @param string $type
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    final public function getType()
    {
        return $this->type;
    }

    public function getImageURI(Notification $notification)
    {
    }
}

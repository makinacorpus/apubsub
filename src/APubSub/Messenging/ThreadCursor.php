<?php

namespace APubSub\Messenging;

use APubSub\Backend\CursorDecorator;
use APubSub\ChannelInterface;
use APubSub\CursorInterface;

/**
 * This implementation will wrap a channel cursor and replace the channel
 * instances with thread instances when running
 */
class ThreadCursor extends CursorDecorator
{
    /**
     * @var MessengingService
     */
    private $service;

    /**
     * Default constructor
     *
     * @param MessengingService $service
     * @param CursorInterface $cursor
     */
    public function __construct(MessengingService $service, CursorInterface $cursor)
    {
        parent::__construct($cursor);

        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        foreach (parent::getIterator() as $key => $value) {

            if ($value instanceof ChannelInterface) {
                $value = new Thread($this->service, $value);
            }

            yield $key => $value;
        }
    }
}
<?php

namespace MakinaCorpus\APubSub\Notification;

use MakinaCorpus\APubSub\Backend\CursorDecorator;
use MakinaCorpus\APubSub\MessageInterface;
use MakinaCorpus\APubSub\CursorInterface;

/**
 * This implementation will wrap a channel cursor and replace the channel
 * instances with thread instances when running
 */
class NotificationCursor extends CursorDecorator
{
    /**
     * @var NotificationService
     */
    private $service;

    /**
     * Default constructor
     *
     * @param MessengingService $service
     * @param CursorInterface $cursor
     */
    public function __construct(NotificationService $service, CursorInterface $cursor)
    {
        parent::__construct($cursor);

        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        foreach (parent::getIterator() as $key => $message) {

            if ($message instanceof MessageInterface) {
                $message = $this->service->getNotification($message);
            }

            yield $key => $message;
        }
    }
}

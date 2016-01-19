<?php

namespace MakinaCorpus\APubSub\Tests\Notification;

use MakinaCorpus\APubSub\Notification\NotificationService;
use MakinaCorpus\APubSub\Tests\AbstractBackendBasedTest;

abstract class AbstractNotificationBasedTest extends AbstractBackendBasedTest
{
    /**
     * @var NotificationService
     */
    private $service;

    protected function setUp()
    {
        parent::setUp();

        $this->service = new NotificationService($this->backend,false);

        // Register some notification types
        $formatterRegistry = $this->service->getFormatterRegistry();
        $formatterRegistry->registerType('friend:something', '\MakinaCorpus\APubSub\Notification\Formatter\RawFormatter');
        $formatterRegistry->registerType('friend:friended', '\MakinaCorpus\APubSub\Notification\Formatter\RawFormatter');
        $formatterRegistry->registerType('content');
        $formatterRegistry->registerType('disabled');
    }

    public function getService()
    {
        return $this->service;
    }
}

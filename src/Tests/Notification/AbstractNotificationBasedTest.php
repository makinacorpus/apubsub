<?php

namespace MakinaCorpus\APubSub\Tests\Notification;

use MakinaCorpus\APubSub\Notification\NotificationService;
use MakinaCorpus\APubSub\Tests\AbstractBackendBasedTest;

abstract class AbstractNotificationBasedTest extends AbstractBackendBasedTest
{
    /**
     * @var \MakinaCorpus\APubSub\Notification\NotificationService
     */
    private $service;

    protected function setUp()
    {
        parent::setUp();

        $this->service = new NotificationService(
            $this->backend,
            false,
            false,
            array(
                'disabled', // See lower
            ));

        // Register some notification types
        $formatterRegistry = $this->service->getFormatterRegistry();
        $formatterRegistry->registerType('friend', array(
            'class'       => '\MakinaCorpus\APubSub\Notification\Formatter\RawTextFormatter',
        ));
        // Leaving this one with no class will get us null instanceing
        $formatterRegistry->registerType('content');
        // This one is disabled
        $formatterRegistry->registerType('disabled');
    }

    public function getService()
    {
        return $this->service;
    }
}

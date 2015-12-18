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

        // Register some chan types
        $chanRegistry = $this->service->getChanTypeRegistry();
        $chanRegistry->registerType('foo', array(
            'description' => "Foo description",
            'group'       => "Testing 1",
        ));
        $chanRegistry->registerType('bar', array(
            'description' => "Bar description",
            'group'       => "Testing 2",
        ));
        $chanRegistry->registerType('baz', array(
            'description' => "Baz description",
            'group'       => "Testing 2",
        ));

        // Register some notification types
        $formatterRegistry = $this->service->getFormatterRegistry();
        $formatterRegistry->registerType('friend', array(
            'class'       => '\MakinaCorpus\APubSub\Notification\Formatter\RawTextFormatter',
            'description' => "Friend",
            'group'       => "Testing 1",
        ));
        // Leaving this one with no class will get us null instanceing
        $formatterRegistry->registerType('content', array(
            'description' => "Content",
            'group'       => "Testing 2",
        ));
        // This one is disabled
        $formatterRegistry->registerType('disabled', array(
            'description' => "Disabled",
            'group'       => "Testing 1",
        ));
    }

    public function getService()
    {
        return $this->service;
    }
}

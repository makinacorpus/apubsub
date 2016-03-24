<?php

namespace MakinaCorpus\APubSub\Notification\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Compiler pass to register tagged services for the formatter registry
 *
 * Thank you so much Fabien Potencier for the work that greatly inspired me from
 * the Symfony Event dispatcher component, all credits goes to him.
 *
 * If you describe a new event listener, you must add the
 * 'apb.notification.formatter' named tag on it. This tag might carry a few
 * other properties:
 *
 *   - 'event' : string or string list, this is must be a single or list of
 *     "RESOURCE_TYPE:ACTION" strings. For example, if the formatter formats
 *     a message when a 'user_account' object has 'validated' its email, such
 *     string would be "user_account:validated".
 *
 *   - 'auto' : boolean, if set to true, an event listener will automatically
 *     listen for the 'event' event, and notify on the
 *     'RESOURCE_TYPE:RESOURCE_ID' channel, using this formatter.
 *
 *   - 'channel' : string or string list, this must be a single or list of
 *     arbitrary channels names where to send the notification, if different
 *     from the default 'RESOURCE_TYPE:RESOURCE_ID' channel.
 *     @todo NOT IMPLEMENTED YET
 */
class RegisterFormattersPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    protected $registryService;

    /**
     * @var string
     */
    protected $formatterTag;

    /**
     * @var string
     */
    protected $listenerService;

    /**
     * @var string
     */
    protected $dispatcherService;

    /**
     * Constructor.
     *
     * @param string $registryService
     *   Service name for the notification formatter registry
     * @param string $formatterTag
     *   Service tag for the notification formatters
     * @param string $listenerService
     *   Service name for the notification listener
     * @param string $dispatcherService
     *   Service name for the event dispatcher
     */
    public function __construct(
        $registryService = 'apb.notification.formatter_registry',
        $formatterTag = 'apb.notification.formatter',
        $listenerService = 'apb.notification.auto_event_listener',
        $dispatcherService = 'event_dispatcher'
    ) {
        $this->registryService = $registryService;
        $this->formatterTag = $formatterTag;
        $this->listenerService = $listenerService;
        $this->dispatcherService = $dispatcherService;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->registryService) && !$container->hasAlias($this->registryService)) {
            return;
        }
        $definition = $container->findDefinition($this->registryService);

        $autoChannelOverrides = [];

        if (($container->hasDefinition($this->listenerService) || $container->hasAlias($this->listenerService)) &&
            ($container->hasDefinition($this->registryService) || $container->hasAlias($this->registryService))
        ) {
            $dispatcherDefinition = $container->findDefinition($this->dispatcherService);
            $listenerDefinition = $container->findDefinition($this->listenerService);
            if (!$listenerDefinition) {
                throw new \InvalidArgumentException(sprintf('The "%s" listener service must be defined if you want to define automatic notifications using the "auto" tag argument'));
            }
        } else {
            $dispatcherDefinition = null;
            $listenerDefinition = null;
        }

        foreach ($container->findTaggedServiceIds($this->formatterTag) as $id => $attributes) {

            $def = $container->getDefinition($id);

            if (!$def->isPublic()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must be public as notification formatters are lazy-loaded.', $id));
            }
            if ($def->isAbstract()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must not be abstract as notification formatters are lazy-loaded.', $id));
            }

            if (empty($attributes[0]['event']) || !strpos($attributes[0]['event'], ':')) {
                throw new \InvalidArgumentException(sprintf("The service \"%s\" tags must carry the 'event' attribute in the form of 'RESOURCETYPE:ACTION'.", $id));
            }
            $event = $attributes[0]['event'];

            // We must assume that the class value has been correctly filled, even if the service is created by a factory
            //   - note from myself: this is documented that it should alway be
            //     in the official dependency injection documentation
            $class = $container->getParameterBag()->resolveValue($def->getClass());

            $refClass = new \ReflectionClass($class);
            $interface = '\MakinaCorpus\APubSub\Notification\FormatterInterface';
            if (!$refClass->implementsInterface($interface)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, $interface));
            }

            // Register event automatique firing if necessary
            if ($dispatcherDefinition && !empty($attributes[0]['auto'])) {
                $methodName = str_replace(':', '__', $event);
                // Please note we add a very huge priority, this listener must
                // run last so that other events might abitrary add channels
                // to the event where to send the notification, doing so allows
                // use to use the very same events for changing channels
                $dispatcherDefinition->addMethodCall('addListenerService', [$event, [$this->listenerService, $methodName], -10000]);

                if (!empty($attributes[0]['channels'])) {
                    // Register default channels for this notification formatter
                    $channelOverride = $attributes[0]['channels'];
                    if (!is_array($channelOverride)) {
                        $channelOverride = explode(',', $channelOverride);
                    }
                    // Consistency check, could be better...
                    foreach ($channelOverride as $chanId) {
                        if (!is_string($chanId)) {
                            throw new \InvalidArgumentException(sprintf('Channels for service "%s" should be strings', $id));
                        }
                    }
                    $autoChannelOverrides[$event] = $channelOverride;
                }
            }

            if ($listenerDefinition && $autoChannelOverrides) {
                $listenerDefinition->addMethodCall('setChanBlockingOverrides', [$autoChannelOverrides]);
            }

            $definition->addMethodCall('registerType', [$event, $id]);
        }
    }
}

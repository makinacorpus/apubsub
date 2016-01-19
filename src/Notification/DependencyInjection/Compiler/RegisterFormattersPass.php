<?php

namespace MakinaCorpus\APubSub\Notification\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Compiler pass to register tagged services for the formatter registry
 *
 * Thank you so much Fabien Potencier for the work that greatly inspired me from
 * the Symfony Event dispatcher component, all credits goes to him.
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
     * Constructor.
     *
     * @param string $registryService
     *   Service name for the notification formatter registry
     * @param string $formatterTag
     *   Service tag for the notification formatters
     */
    public function __construct($registryService = 'apb.notification.formatter_registry', $formatterTag = 'apb.notification.formatter')
    {
        $this->registryService = $registryService;
        $this->formatterTag = $formatterTag;
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

            // We must assume that the class value has been correctly filled, even if the service is created by a factory
            //   - note from myself: this is documented that it should alway be
            //     in the official dependency injection documentation
            $class = $container->getParameterBag()->resolveValue($def->getClass());

            $refClass = new \ReflectionClass($class);
            $interface = '\MakinaCorpus\APubSub\Notification\FormatterInterface';
            if (!$refClass->implementsInterface($interface)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, $interface));
            }

            $definition->addMethodCall('registerType', [$attributes[0]['event'], $id]);
        }
    }
}

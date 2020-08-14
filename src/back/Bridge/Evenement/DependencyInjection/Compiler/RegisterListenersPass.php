<?php

/*
 * This file is part of the Sterlett project <https://github.com/sterlett/sterlett>.
 *
 * (c) 2020 Pavel Petrov <itnelo@gmail.com>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3.0
 */

declare(strict_types=1);

namespace Sterlett\Bridge\Evenement\DependencyInjection\Compiler;

use Evenement\EventEmitterInterface;
use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass as RegisterSymfonyListenersPass;

/**
 * Registers tagged services as listeners for events from Evenement's emitters in the centralized way, alongside other
 * listeners which observes events from PSR-14 dispatchers.
 *
 * Note: don't use this one in the classic, "blocking" Symfony environment, it isn't a complete bridge. There are some
 * features that are not relevant to the async environment, but should be implemented if you want to use it optimally
 * in the "native" Symfony application (5.1 and higher):
 *
 * - "container.hot_path" tag support (propagates "always need" status and inlining to a listener if related event is
 * marked for frequent usage)
 * - "container.no_preload" tag support (adds the same tag to the listener if event is marked not to be preloaded)
 * - also: support code for event subscribers, event aliases and priority
 *
 * @see RegisterSymfonyListenersPass
 */
class RegisterListenersPass implements CompilerPassInterface
{
    /**
     * Tag for marking service as an Evenement's event dispatcher
     *
     * @var string
     */
    private string $dispatcherTag;

    /**
     * Tag for marking service as an Evenement's event listener
     *
     * @var string
     */
    private string $listenerTag;

    /**
     * RegisterListenersPass constructor.
     *
     * @param string $dispatcherTag Tag for marking service as an Evenement's event dispatcher
     * @param string $listenerTag   Tag for marking service as an Evenement's event listener
     */
    public function __construct(
        string $dispatcherTag = 'evenement.event_dispatcher',
        string $listenerTag = 'evenement.event_listener'
    ) {
        $this->dispatcherTag = $dispatcherTag;
        $this->listenerTag   = $listenerTag;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $listenerIds = $container->findTaggedServiceIds($this->listenerTag, true);

        foreach ($listenerIds as $listenerId => $listenerTags) {
            foreach ($listenerTags as $tagAttributes) {
                $this->registerListenerCall($container, $listenerId, $tagAttributes);
            }
        }
    }

    /**
     * Adds a method call to the dispatcher's service definition; that call represents a listener's callback execution
     * after event is triggered by the dispatcher ("emitter", in Evenement's terms)
     *
     * @param ContainerBuilder $container     DI container
     * @param string           $listenerId    Listener service identifier
     * @param mixed[]          $tagAttributes Listener tag attributes (e.g. 'event', 'method')
     *
     * @return void
     */
    private function registerListenerCall(ContainerBuilder $container, string $listenerId, array $tagAttributes): void
    {
        if (!array_key_exists('dispatcher', $tagAttributes)) {
            $tagAttributeNotFoundMessage = sprintf(
                "Tag attribute 'dispatcher' is required to subscribe on Evenement's event (%s).",
                $listenerId
            );

            throw new LogicException($tagAttributeNotFoundMessage);
        }

        if (!array_key_exists('event', $tagAttributes) || !array_key_exists('method', $tagAttributes)) {
            $tagAttributeNotFoundMessage = sprintf(
                "Both 'event' and 'method' tag attributes must be specified explicitly "
                . "to subscribe on Evenement's event (%s).",
                $listenerId
            );

            throw new LogicException($tagAttributeNotFoundMessage);
        }

        $dispatcherId         = (string) $tagAttributes['dispatcher'];
        $dispatcherDefinition = $container->getDefinition($dispatcherId);

        $dispatcherDefinitionClass = $dispatcherDefinition->getClass();
        $dispatcherInterfaces      = class_implements($dispatcherDefinitionClass);

        if (!in_array(EventEmitterInterface::class, $dispatcherInterfaces)) {
            $invalidDispatcherInterfaceMessage = sprintf(
                "Event dispatcher must implement '%s' to be able to dispatch Evenement's event (%s).",
                EventEmitterInterface::class,
                $listenerId
            );

            throw new LogicException($invalidDispatcherInterfaceMessage);
        }

        $eventName = (string) $tagAttributes['event'];

        $dispatcherDefinition->addMethodCall(
            'on',
            [
                $eventName,
                [
                    // ServiceClosureArgument here prevents a private listener service from being removed (inlined) at
                    // removing stage due to luck of "connections" with other services in the dependency graph;
                    // Logically, this wrapper acts like a synthetic user of this listener (simulated constructor
                    // injection). And, technically, it will not be inlined, it becomes a public service, i.e. available
                    // by container's get() method). Link below navigates to related part of the compiling process:
                    // https://github.com/symfony/dependency-injection/blob/v5.1.0/Compiler/InlineServiceDefinitionsPass.php#L108
                    // todo: implement ServiceClosureArgument support
                    new Reference($listenerId),
                    $tagAttributes['method'],
                ],
            ]
        );

        if (!$dispatcherDefinition->hasTag($this->dispatcherTag)) {
            $dispatcherDefinition->addTag($this->dispatcherTag);
        }
    }
}

<?php

namespace App\DependencyInjection\Compiler;

use League\CommonMark\ConfigurableEnvironmentInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class AddMarkdownExtensionsPass implements CompilerPassInterface {
    public function process(ContainerBuilder $container) {
        $definition = $container->getDefinition(ConfigurableEnvironmentInterface::class);

        foreach ($container->findTaggedServiceIds('commonmark.inline_parser') as $serviceId => $tags) {
            $definition->addMethodCall('addInlineParser', [
                new Reference($serviceId),
                $tags[array_key_last($tags)]['priority'] ?? 0,
            ]);
        }
    }
}

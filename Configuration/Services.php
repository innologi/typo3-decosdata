<?php

declare(strict_types=1);

namespace Innologi\Decosdata;

use Innologi\Decosdata\Service\Option\Query\OptionInterface as QueryOptionInterface;
use Innologi\Decosdata\Service\Option\Render\OptionInterface as RenderOptionInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerForAutoconfiguration(RenderOptionInterface::class)->addTag('decosdata.service.option');
    $containerBuilder->registerForAutoconfiguration(QueryOptionInterface::class)->addTag('decosdata.service.option');

    $containerBuilder->addCompilerPass(new class implements CompilerPassInterface {
        public function process(ContainerBuilder $container)
        {
            foreach ($container->findTaggedServiceIds('decosdata.service.option') as $id => $tags) {
                // option classes are created on demand but do have DI expectations
                $container->findDefinition($id)->setPublic(true)->setShared(false);
            }
        }
    });
};

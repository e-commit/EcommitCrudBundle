<?php

declare(strict_types=1);

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\DependencyInjection\Compiler;

use Ecommit\CrudBundle\Crud\CrudFilters;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FiltersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $taggedServices = $container->findTaggedServiceIds('ecommit_crud.filter');
        $lazyServicesRefs = [];

        foreach ($taggedServices as $id => $tagAttributes) {
            $definition = $container->getDefinition($id);
            $class = $container->getParameterBag()->resolveValue($definition->getClass());
            $name = $class::getName();

            $lazyServicesRefs[$name] = new Reference($id);
        }

        $container->register('ecommit_crud.filters', CrudFilters::class)
            ->setPublic(false)
            ->setArguments([ServiceLocatorTagPass::register($container, $lazyServicesRefs)]);
    }
}

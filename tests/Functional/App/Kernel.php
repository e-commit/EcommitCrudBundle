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

namespace Ecommit\CrudBundle\Tests\Functional\App;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Ecommit\CrudBundle\EcommitCrudBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\WebpackEncoreBundle\WebpackEncoreBundle;

class Kernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $loader->load($this->getProjectDir().'/config/framework.yaml');
        $loader->load($this->getProjectDir().'/config/doctrine.yaml');
        $loader->load($this->getProjectDir().'/config/security.yaml');
        $loader->load($this->getProjectDir().'/config/webpack_encore.yaml');
        $loader->load($this->getProjectDir().'/config/ecommit_crud.yaml');
        $loader->load($this->getProjectDir().'/config/services.yaml');
    }

    public function process(ContainerBuilder $container): void
    {
        // Bug with tests and symfony/stopwatch
        $container->removeDefinition('debug.event_dispatcher');
        $container->removeDefinition('debug.controller_resolver');
        $container->removeDefinition('debug.argument_resolver');
    }

    public function registerBundles(): array
    {
        return [
            new TwigBundle(),
            new DoctrineBundle(),
            new DoctrineFixturesBundle(),
            new FrameworkBundle(),
            new SecurityBundle(),
            new WebpackEncoreBundle(),
            new EcommitCrudBundle(),
        ];
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import($this->getProjectDir().'/config/routes.yaml');
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }
}

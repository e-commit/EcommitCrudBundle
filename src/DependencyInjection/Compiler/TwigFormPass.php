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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TwigFormPass implements CompilerPassInterface
{
    /**
     * @psalm-suppress PossiblyInvalidArgument
     */
    public function process(ContainerBuilder $container): void
    {
        $container->setParameter('twig.form.resources', array_merge(
            $container->getParameter('twig.form.resources'),
            [
                '@EcommitCrud/Form/entity_ajax.html.twig',
            ]
        ));
    }
}

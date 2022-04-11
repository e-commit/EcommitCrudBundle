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

namespace Ecommit\CrudBundle\Crud;

use Ecommit\CrudBundle\Form\Filter\FilterInterface;
use Psr\Container\ContainerInterface;

final class CrudFilters
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    public function has(string $filter): bool
    {
        return $this->container->has($filter);
    }

    public function get(string $filter): FilterInterface
    {
        return $this->container->get($filter);
    }
}

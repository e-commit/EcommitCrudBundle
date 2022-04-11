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

use Psr\Container\ContainerInterface;

final class CrudFactory
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    public function create(string $sessionName): Crud
    {
        return new Crud($sessionName, $this->container);
    }
}

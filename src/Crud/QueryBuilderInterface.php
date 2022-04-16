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

interface QueryBuilderInterface
{
    public function addOrderBy(string $sort, string $sortDirection): self;

    public function orderBy(string $sort, string $sortDirection): self;

    public function addParameter(QueryBuilderParameterInterface $parameter): self;
}

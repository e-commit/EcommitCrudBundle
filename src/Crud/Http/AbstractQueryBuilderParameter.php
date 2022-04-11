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

namespace Ecommit\CrudBundle\Crud\Http;

use Ecommit\CrudBundle\Crud\QueryBuilderParameterInterface;

/**
 * @internal
 */
abstract class AbstractQueryBuilderParameter implements QueryBuilderParameterInterface
{
    public function __construct(public mixed $value)
    {
    }
}

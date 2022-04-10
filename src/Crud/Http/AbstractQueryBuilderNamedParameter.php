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

abstract class AbstractQueryBuilderNamedParameter extends AbstractQueryBuilderParameter
{
    public function __construct(public string $name, mixed $value)
    {
        parent::__construct($value);
    }
}

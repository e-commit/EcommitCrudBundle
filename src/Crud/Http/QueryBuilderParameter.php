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

class QueryBuilderParameter implements QueryBuilderParameterInterface
{
    public $name;

    public $value;

    public $method;

    public function __construct($name, $value, $method)
    {
        $this->name = $name;
        $this->value = $value;
        $this->method = $method;
    }
}

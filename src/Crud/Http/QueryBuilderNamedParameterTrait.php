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

trait QueryBuilderNamedParameterTrait
{
    public $name;

    public function __construct(string $name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }
}

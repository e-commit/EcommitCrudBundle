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

namespace Ecommit\CrudBundle\Tests\Functional\App\Form\Searcher;

use Ecommit\CrudBundle\Form\Searcher\AbstractSearcher;

class EntityToManyToOneSearcher extends AbstractSearcher
{
    public $propertyName;
}

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

namespace Ecommit\CrudBundle\Form\Filter;

class NotNullFilter extends NullFilter
{
    public function updateQueryBuilder($queryBuilder, string $property, $value, array $options): void
    {
        if (!$value) {
            return;
        }

        $queryBuilder->andWhere(sprintf('%s IS NOT NULL', $options['alias_search']));
    }
}

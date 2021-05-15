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

use Ecommit\CrudBundle\Crud\SearchFormBuilder;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class NumberFilter extends IntegerFilter
{
    public function buildForm(SearchFormBuilder $builder, string $property, array $options): void
    {
        $typeOptions = $this->getTypeOptions($options);
        $builder->addField($property, NumberType::class, $typeOptions);
    }

    protected function testNumberValue($value): bool
    {
        return is_numeric($value);
    }
}

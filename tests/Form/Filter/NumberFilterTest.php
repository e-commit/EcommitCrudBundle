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

namespace Ecommit\CrudBundle\Tests\Form\Filter;

use Ecommit\CrudBundle\Form\Filter\NumberFilter;

class NumberFilterTest extends IntegerFilterTest
{
    public const TEST_FILTER = NumberFilter::class;

    public function getTestViewAndQueryBuilderProvider(): array
    {
        return [
            //Null value
            [null, NumberFilter::GREATER_THAN, '', null, []],
            [null, NumberFilter::GREATER_EQUAL, '', null, []],
            [null, NumberFilter::SMALLER_THAN, '', null, []],
            [null, NumberFilter::SMALLER_EQUAL, '', null, []],
            [null, NumberFilter::EQUAL, '', null, []],

            //String value
            ['5', NumberFilter::GREATER_THAN, '5', 'e.name > :value_integer_propertyName', ['value_integer_propertyName' => '5']],
            ['5', NumberFilter::GREATER_EQUAL, '5', 'e.name >= :value_integer_propertyName', ['value_integer_propertyName' => '5']],
            ['5', NumberFilter::SMALLER_THAN, '5', 'e.name < :value_integer_propertyName', ['value_integer_propertyName' => '5']],
            ['5', NumberFilter::SMALLER_EQUAL, '5', 'e.name <= :value_integer_propertyName', ['value_integer_propertyName' => '5']],
            ['5.25', NumberFilter::EQUAL, '5,25', 'e.name = :value_integer_propertyName', ['value_integer_propertyName' => '5.25']],

            //Int value
            [5, NumberFilter::GREATER_THAN, '5', 'e.name > :value_integer_propertyName', ['value_integer_propertyName' => '5']],
            [5, NumberFilter::GREATER_EQUAL, '5', 'e.name >= :value_integer_propertyName', ['value_integer_propertyName' => '5']],
            [5, NumberFilter::SMALLER_THAN, '5', 'e.name < :value_integer_propertyName', ['value_integer_propertyName' => '5']],
            [5, NumberFilter::SMALLER_EQUAL, '5', 'e.name <= :value_integer_propertyName', ['value_integer_propertyName' => '5']],
            [5.25, NumberFilter::EQUAL, '5,25', 'e.name = :value_integer_propertyName', ['value_integer_propertyName' => '5.25']],
        ];
    }

    public function getTestSubmitProvider(): array
    {
        return [
            [null, null, ''],
            ['', null, ''],
            ['5', 5.0, '5'],
            ['5.25', 5.25, '5,25'],
            ['5,25', 5.25, '5,25'],
        ];
    }
}

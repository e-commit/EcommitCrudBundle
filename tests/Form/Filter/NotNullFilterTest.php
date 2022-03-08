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

use Ecommit\CrudBundle\Form\Filter\NotNullFilter;

class NotNullFilterTest extends NullFilterTest
{
    public const TEST_FILTER = NotNullFilter::class;

    public function getTestViewAndQueryBuilderProvider(): array
    {
        return [
            // Null value
            [null, false, null, []],

            // Not checked
            [false, false, null, []],

            // Checked
            [true, true, 'e.name IS NOT NULL', []],
        ];
    }
}

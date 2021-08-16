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

namespace Ecommit\CrudBundle\Tests\DependencyInjection;

use Ecommit\CrudBundle\Crud\CrudFilters;
use Ecommit\CrudBundle\Tests\Functional\App\Form\Filter\MyFilter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EcommitCrudExtensionTest extends KernelTestCase
{
    protected function setUp(): void
    {
        static::bootKernel();
    }

    public function testAutoconfigureTag(): void
    {
        $crudFilters = self::$container->get(CrudFilters::class);

        $this->assertTrue($crudFilters->has(MyFilter::class));
    }
}

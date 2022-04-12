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

namespace Ecommit\CrudBundle\Tests\Crud;

use Ecommit\CrudBundle\Crud\CrudFilters;
use Ecommit\CrudBundle\Form\Filter\FilterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class CrudFiltersTest extends TestCase
{
    protected CrudFilters $crudFilters;

    protected function setUp(): void
    {
        $this->crudFilters = $this->createCrudFilters();
    }

    /**
     * @dataProvider getTestHasProvider
     */
    public function testHas(string $filter, $expected): void
    {
        $this->assertSame($expected, $this->crudFilters->has($filter));
    }

    public function getTestHasProvider(): array
    {
        return [
            ['filter_1', true],
            ['bad_filer', false],
        ];
    }

    public function testGet(): void
    {
        $filter = $this->crudFilters->get('filter_1');
        $this->assertInstanceOf(FilterInterface::class, $filter);
    }

    protected function createCrudFilters(): CrudFilters
    {
        $filters = [
            'filter_1' => $this->createFilter('filter_1'),
            'filter_2' => $this->createFilter('filter_2'),
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')
            ->willReturnCallback(fn ($name): bool => \array_key_exists($name, $filters));
        $container->method('get')
            ->willReturnCallback(fn ($name): FilterInterface => $filters[$name]);

        return new CrudFilters($container);
    }

    protected function createFilter(string $name): FilterInterface
    {
        return $this->createMock(FilterInterface::class);
    }
}

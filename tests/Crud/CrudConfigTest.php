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

use Doctrine\ORM\QueryBuilder;
use Ecommit\CrudBundle\Crud\CrudConfig;
use Ecommit\CrudBundle\Form\Searcher\SearcherInterface;
use PHPUnit\Framework\TestCase;

class CrudConfigTest extends TestCase
{
    public function testConstructorWithoutArgument(): void
    {
        $config = new CrudConfig();
        $this->assertSame([], $config->getOptions());
    }

    public function testConstructorWithArgument(): void
    {
        $config = new CrudConfig('session');
        $this->assertSame(['session_name' => 'session'], $config->getOptions());
    }

    public function testSetSessionName(): void
    {
        $config = (new CrudConfig())->setSessionName('session');
        $this->assertSame(['session_name' => 'session'], $config->getOptions());
    }

    public function testAddColumn(): void
    {
        $config = (new CrudConfig())
            ->addColumn('val1') // 1 argument
            ->addColumn('id2', 'alias2', 'label2') // 3 arguments
            ->addColumn('id3', 'alias3', 'label3', ['optionA' => 'valueA']); // 4 arguments
        $expected = [
            'columns' => [
                'val1',
                ['id' => 'id2', 'alias' => 'alias2', 'label' => 'label2'],
                ['id' => 'id3', 'alias' => 'alias3', 'label' => 'label3', 'optionA' => 'valueA'],
            ],
        ];
        $this->assertSame($expected, $config->getOptions());
    }

    public function testAddColumnWithoutArgument(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bad addColumn call');
        (new CrudConfig())->addColumn();
    }

    public function testAddColumnWithTooManyArguments(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bad addColumn call');
        (new CrudConfig())->addColumn(1, 2, 3, 4, 5);
    }

    public function testAddVirtualColumn(): void
    {
        $config = (new CrudConfig())
            ->addVirtualColumn('val1') // 1 argument
            ->addVirtualColumn('id2', 'alias2'); // 2 arguments
        $expected = [
            'virtual_columns' => [
                'val1',
                ['id' => 'id2', 'alias' => 'alias2'],
            ],
        ];
        $this->assertSame($expected, $config->getOptions());
    }

    public function testAddVirtualColumnWithoutArgument(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bad addVirtualColumn call');
        (new CrudConfig())->addVirtualColumn();
    }

    public function testAddVirtualColumnWithTooManyArguments(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bad addVirtualColumn call');
        (new CrudConfig())->addVirtualColumn(1, 2, 3);
    }

    public function testSetMaxPerPage(): void
    {
        $config = (new CrudConfig())->setMaxPerPage([10, 100], 100);
        $this->assertSame(['max_per_page_choices' => [10, 100], 'default_max_per_page' => 100], $config->getOptions());
    }

    public function testSetDefaultSort(): void
    {
        $config = (new CrudConfig())->setDefaultSort('sort', 'direction');
        $this->assertSame(['default_sort' => 'sort', 'default_sort_direction' => 'direction'], $config->getOptions());
    }

    public function testSetDefaultPersonalizedSort(): void
    {
        $config = (new CrudConfig())->setDefaultPersonalizedSort(['crit1', 'crit2']);
        $this->assertSame(['default_sort' => 'defaultPersonalizedSort', 'default_personalized_sort' => ['crit1', 'crit2']], $config->getOptions());
    }

    public function testSetQueryBuilder(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $config = (new CrudConfig())->setQueryBuilder($queryBuilder);
        $this->assertSame(['query_builder' => $queryBuilder], $config->getOptions());
    }

    public function testSetRoute(): void
    {
        $config = (new CrudConfig())->setRoute('route', ['params']);
        $this->assertSame(['route_name' => 'route', 'route_parameters' => ['params']], $config->getOptions());
    }

    public function testCreateSearchForm(): void
    {
        $searchFormData = $this->createMock(SearcherInterface::class);
        $config = (new CrudConfig())->createSearchForm($searchFormData, 'type', ['option1']);
        $this->assertSame(['search_form_data' => $searchFormData, 'search_form_type' => 'type', 'search_form_options' => ['option1']], $config->getOptions());
    }

    public function testSetDisplayResultsOnlyIfSearch(): void
    {
        $config = (new CrudConfig())->setDisplayResultsOnlyIfSearch(true);
        $this->assertSame(['display_results_only_if_search' => true], $config->getOptions());
    }

    public function testSetBuildPaginator(): void
    {
        $config = (new CrudConfig())->setBuildPaginator(false);
        $this->assertSame(['build_paginator' => false], $config->getOptions());
    }

    public function testSetPersistentSettings(): void
    {
        $config = (new CrudConfig())->setPersistentSettings(true);
        $this->assertSame(['persistent_settings' => true], $config->getOptions());
    }

    public function testSetDivIdSearch(): void
    {
        $config = (new CrudConfig())->setDivIdSearch('id');
        $this->assertSame(['div_id_search' => 'id'], $config->getOptions());
    }

    public function testSetDivIdList(): void
    {
        $config = (new CrudConfig())->setDivIdList('id');
        $this->assertSame(['div_id_list' => 'id'], $config->getOptions());
    }

    public function testSetTwigFunctionsConfiguration(): void
    {
        $config = (new CrudConfig())->setTwigFunctionsConfiguration(['val1']);
        $this->assertSame(['twig_functions_configuration' => ['val1']], $config->getOptions());
    }

    /**
     * @dataProvider getTestResetOptionsProvider
     */
    public function testResetOptions(mixed $value, array $expected): void
    {
        $config = (new CrudConfig())
            ->setSessionName('session')
            ->setDefaultSort('sort', 'direction')
            ->resetOptions($value);
        $this->assertSame($expected, $config->getOptions());
    }

    public function getTestResetOptionsProvider(): array
    {
        return [
            [null, []],
            ['default_sort', ['session_name' => 'session', 'default_sort_direction' => 'direction']],
            [[], ['session_name' => 'session', 'default_sort' => 'sort', 'default_sort_direction' => 'direction']],
            [['default_sort', 'default_sort_direction'], ['session_name' => 'session']],
        ];
    }

    /**
     * @dataProvider getTestOffsetExistsProvider
     */
    public function testOffsetExists(string $offset, bool $expected): void
    {
        $config = (new CrudConfig())
            ->setSessionName('session');

        $this->assertSame($expected, $config->offsetExists($offset));
    }

    public function getTestOffsetExistsProvider(): array
    {
        return [
            ['session_name', true],
            ['bad_offset', false],
        ];
    }

    public function testOffsetGet(): void
    {
        $config = (new CrudConfig())
            ->setSessionName('session');

        $this->assertSame('session', $config->offsetGet('session_name'));
    }

    public function testOffsetSet(): void
    {
        $config = new CrudConfig();
        $config->offsetSet('session_name', 'session');

        $this->assertSame(['session_name' => 'session'], $config->getOptions());
    }

    public function testOffsetUnset(): void
    {
        $config = (new CrudConfig())
            ->setSessionName('session');
        $config->offsetUnset('session_name');

        $this->assertSame([], $config->getOptions());
    }
}

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
use Ecommit\CrudBundle\Crud\Crud;
use Ecommit\CrudBundle\Crud\CrudColumn;
use Ecommit\CrudBundle\Crud\CrudConfig;
use Ecommit\CrudBundle\Crud\CrudSession;
use Ecommit\CrudBundle\Crud\SearchFormBuilder;
use Ecommit\CrudBundle\Form\Searcher\SearcherInterface;
use Ecommit\CrudBundle\Tests\Functional\App\Form\Searcher\UserSearcher;
use Ecommit\DoctrineUtils\Paginator\DoctrineORMPaginator;
use Ecommit\Paginator\PaginatorInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class CrudTest extends AbstractCrudTest
{
    /**
     * @dataProvider getTestCrudWithInvalidSessionNameProvider
     */
    public function testCrudWithInvalidSessionName(mixed $sessionName, string $expectedException, string $expectedExceptionMessage): void
    {
        $this->expectException($expectedException);
        $this->expectExceptionMessageMatches($expectedExceptionMessage);

        $crudOptions = $this->createValidCrudConfig();
        $crudOptions['session_name'] = $sessionName;
        $this->createCrud($crudOptions);
    }

    public function getTestCrudWithInvalidSessionNameProvider(): array
    {
        return [
            ['', InvalidOptionsException::class, '/The option "session_name" with value ".*" is invalid/'],
            ['aa#bb', ValidationFailedException::class, '/Invalid session_name format/'],
            ['aa bb', ValidationFailedException::class, '/Invalid session_name format/'],
            [str_pad('', 101, 'a'), ValidationFailedException::class, '/Invalid session_name format/'],
            [1, InvalidOptionsException::class, '/The option "session_name" with value 1 is expected to be of type "string", but is of type "int"/'],
        ];
    }

    public function testGetSessionName(): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig());
        $this->assertSame('session_name', $crud->getSessionName());
    }

    public function testGetSessionValues(): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig());
        $this->assertInstanceOf(CrudSession::class, $crud->getSessionValues());
    }

    public function testColumns(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->resetOptions('columns')
            ->addColumn(['id' => 'username', 'alias' => 'u.username', 'label' => 'username'])
            ->addColumn(new CrudColumn([
                'id' => 'firstName',
                'alias' => 'u.firstName',
                'label' => 'first_name',
                'sortable' => false,
                'displayed_by_default' => false,
                'alias_search' => 'alias_search2',
                'alias_sort' => 'alias_sort2',
            ]));

        $crud = $this->createCrud($crudConfig);
        $column1 = new CrudColumn(['id' => 'username', 'alias' => 'u.username', 'label' => 'username']);
        $column2 = new CrudColumn(['id' => 'firstName', 'alias' => 'u.firstName', 'label' => 'first_name', 'sortable' => false, 'displayed_by_default' => false, 'alias_search' => 'alias_search2', 'alias_sort' => 'alias_sort2']);
        $this->assertEquals($column1, $crud->getColumn('username'));
        $this->assertEquals($column2, $crud->getColumn('firstName'));
        $columns = [
            'username' => $column1,
            'firstName' => $column2,
        ];
        $this->assertEquals($columns, $crud->getColumns());
    }

    public function testAddColumnWithAliasSort(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->addColumn('my_column', 'u.alias', 'My Column', ['alias_sort' => 'u.lastName'])
            ->setDefaultSort('my_column', Crud::ASC);
        $crud = $this->createCrud($crudConfig)->build();

        $this->assertSame(['u.lastName ASC'], $crud->getQueryBuilder()->getDQLPart('orderBy')[0]->getParts());
    }

    /**
     * @dataProvider getTestAddColumnAlreadyExistsProvider
     */
    public function testAddColumnAlreadyExists(callable $callback): void
    {
        $crudConfig = $this->createValidCrudConfig();
        $callback($crudConfig);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The column "column1" already exists');
        $this->createCrud($crudConfig);
    }

    public function getTestAddColumnAlreadyExistsProvider(): array
    {
        return [
            [function (CrudConfig $crudConfig): void {
                $crudConfig->addColumn(['id' => 'column1', 'alias' => 'alias1'])
                    ->addColumn(['id' => 'column1', 'alias' => 'alias1']);
            }],
            [function (CrudConfig $crudConfig): void {
                $crudConfig->addVirtualColumn(['id' => 'column1', 'alias' => 'alias1'])
                    ->addVirtualColumn(['id' => 'column1', 'alias' => 'alias1']);
            }],
            [function (CrudConfig $crudConfig): void {
                $crudConfig->addColumn(['id' => 'column1', 'alias' => 'alias1'])
                    ->addVirtualColumn(['id' => 'column1', 'alias' => 'alias1']);
            }],
            [function (CrudConfig $crudConfig): void {
                $crudConfig->addVirtualColumn(['id' => 'column1', 'alias' => 'alias1'])
                    ->addColumn(['id' => 'column1', 'alias' => 'alias1']);
            }],
        ];
    }

    public function testAddBadColumns(): void
    {
        $crudConfig = $this->createValidCrudConfig();
        $crudConfig['columns'] = 'bad';

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "columns" with value "bad" is expected to be of type "array"');
        $this->createCrud($crudConfig);
    }

    public function testAddBadColumn(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->addColumn('bad_value');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('A column must be an array or a CrudColum instance');
        $this->createCrud($crudConfig);
    }

    public function testNoColumn(): void
    {
        $crudConfig = $this->createValidCrudConfig();
        $crudConfig['columns'] = [];

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('The CRUD should contain 1 column or more');
        $this->createCrud($crudConfig);
    }

    public function testGetColumnNotExists(): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The column "invalid" does not exist');
        $crud->getColumn('invalid');
    }

    public function testGetDefaultDisplayedColumns(): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig());

        $this->assertSame(['firstName'], $crud->getDefaultDisplayedColumns());
    }

    public function testGetDefaultDisplayedColumnsEmpty(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The CRUD should contain 1 displayed column or more');

        $crudConfig = $this->createValidCrudConfig();
        $columns = $crudConfig['columns'];
        $columns[1]['displayed_by_default'] = false;
        $crudConfig['columns'] = $columns;
        $this->createCrud($crudConfig);
    }

    public function testVirtualColumns(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->addVirtualColumn(['id' => 'columnv1', 'alias' => 'aliasv1'])
            ->addVirtualColumn(new CrudColumn(['id' => 'columnv2', 'alias' => 'aliasv2']));

        $crud = $this->createCrud($crudConfig);
        $column1 = new CrudColumn(['id' => 'columnv1', 'alias' => 'aliasv1']);
        $column2 = new CrudColumn(['id' => 'columnv2', 'alias' => 'aliasv2']);
        $this->assertEquals($column1, $crud->getVirtualColumn('columnv1'));
        $this->assertEquals($column2, $crud->getVirtualColumn('columnv2'));

        $columns = [
            'columnv1' => $column1,
            'columnv2' => $column2,
        ];
        $this->assertEquals($columns, $crud->getVirtualColumns());
    }

    public function testAddBadVirtualColumns(): void
    {
        $crudConfig = $this->createValidCrudConfig();
        $crudConfig['virtual_columns'] = 'bad';

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "virtual_columns" with value "bad" is expected to be of type "array"');
        $this->createCrud($crudConfig);
    }

    public function testAddBadVirtualColumn(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->addVirtualColumn('bad');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('A column must be an array or a CrudColum instance');
        $this->createCrud($crudConfig);
    }

    public function testGetVirtualColumnNotExists(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The column "invalid" does not exist');

        $crud = $this->createCrud($this->createValidCrudConfig());
        $crud->getVirtualColumn('invalid');
    }

    /**
     * @dataProvider getTestQueryBuilderProvider
     */
    public function testQueryBuilder(mixed $queryBuilder): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->setQueryBuilder($queryBuilder);
        $crud = $this->createCrud($crudConfig);
        $this->assertSame($queryBuilder, $crud->getQueryBuilder());
    }

    public function getTestQueryBuilderProvider(): array
    {
        return [
            [$this->createMock(\Doctrine\ORM\QueryBuilder::class)],
            [$this->createMock(\Doctrine\DBAL\Query\QueryBuilder::class)],
            [$this->createMock(\Ecommit\CrudBundle\Crud\QueryBuilderInterface::class)],
        ];
    }

    public function testSetBadQueryBuilder(): void
    {
        $crudConfig = $this->createValidCrudConfig();
        $crudConfig['query_builder'] = 'bad';

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "query_builder" with value "bad" is expected to be');
        $this->createCrud($crudConfig);
    }

    public function testMaxPerPage(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->setMaxPerPage([10, 50, 100], 50);
        $crud = $this->createCrud($crudConfig);

        $this->assertSame([10, 50, 100], $crud->getMaxPerPageChoices());
        $this->assertSame(50, $crud->getDefaultMaxPerPage());
    }

    public function testSetMaxPerPageChoicesWithEmptyArray(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->setMaxPerPage([], 50);

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('The max_per_page_choices should contain 1 value or more');

        $this->createCrud($crudConfig);
    }

    public function testAddBadMaxPerPageChoices(): void
    {
        $crudConfig = $this->createValidCrudConfig();
        $crudConfig['max_per_page_choices'] = 'bad';

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "max_per_page_choices" with value "bad" is expected to be of type "int[]"');
        $this->createCrud($crudConfig);
    }

    public function testAddBadDefaultMaxPerPage(): void
    {
        $crudConfig = $this->createValidCrudConfig();
        $crudConfig['default_max_per_page'] = 'bad';

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "default_max_per_page" with value "bad" is expected to be of type "int"');
        $this->createCrud($crudConfig);
    }

    public function testDefaultSort(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->setDefaultSort('username', Crud::DESC);
        $crud = $this->createCrud($crudConfig);

        $this->assertSame('username', $crud->getDefaultSort());
        $this->assertSame(Crud::DESC, $crud->getDefaultSortDirection());
    }

    public function testAddBadDefaultSort(): void
    {
        $crudConfig = $this->createValidCrudConfig();
        $crudConfig['default_sort'] = 1;

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "default_sort" with value 1 is expected to be of type "string"');
        $this->createCrud($crudConfig);
    }

    public function testAddBadDefaultSortDirection(): void
    {
        $crudConfig = $this->createValidCrudConfig();
        $crudConfig['default_sort_direction'] = 'bad';

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "default_sort_direction" with value "bad" is invalid');
        $this->createCrud($crudConfig);
    }

    public function testDefaultPersonalizedSort(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->setDefaultPersonalizedSort(['u.userId']);
        $crud = $this->createCrud($crudConfig);

        $this->assertSame(['u.userId'], $crud->getDefaultPersonalizedSort());
        $this->assertSame('defaultPersonalizedSort', $crud->getDefaultSort());
    }

    public function testAddBadDefaultPersonalizedSort(): void
    {
        $crudConfig = $this->createValidCrudConfig();
        $crudConfig['default_personalized_sort'] = 'bad';

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "default_personalized_sort" with value "bad" is expected to be of type "string[]"');
        $this->createCrud($crudConfig);
    }

    public function testRouting(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->setRoute('user_ajax_crud', ['param1' => 'val1']);
        $crud = $this->createCrud($crudConfig);

        $this->assertSame('user_ajax_crud', $crud->getRouteName());
        $this->assertSame(['param1' => 'val1'], $crud->getRouteParameters());
        $this->assertSame('/user/ajax-crud?param1=val1', $crud->getUrl());
        $this->assertSame('/user/ajax-crud?param1=val1&param2=val2', $crud->getUrl(['param2' => 'val2']));
        $this->assertSame('/user/ajax-crud?param1=val1&search=1', $crud->getSearchUrl());
        $this->assertSame('/user/ajax-crud?param1=val1&search=1&param2=val2', $crud->getSearchUrl(['param2' => 'val2']));
    }

    public function testAddBadRouteName(): void
    {
        $crudConfig = $this->createValidCrudConfig();
        $crudConfig['route_name'] = 1;

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "route_name" with value 1 is expected to be of type "string"');
        $this->createCrud($crudConfig);
    }

    public function testAddBadRouteParameters(): void
    {
        $crudConfig = $this->createValidCrudConfig();
        $crudConfig['route_parameters'] = 'bad';

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "route_parameters" with value "bad" is expected to be of type "array"');
        $this->createCrud($crudConfig);
    }

    public function testCreateAndGetSearchFormMethods(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->createSearchForm(new UserSearcher());
        $crud = $this->createCrud($crudConfig);

        $this->assertInstanceOf(SearchFormBuilder::class, $crud->getSearchFormBuilder());

        $crud->createView();
        $this->assertInstanceOf(FormView::class, $crud->getSearchForm());
    }

    public function testCreateAndGetNullSearchFormMethods(): void
    {
        $crudConfig = $this->createValidCrudConfig();
        $crudConfig['search_form_data'] = null;
        $crud = $this->createCrud($crudConfig);

        $this->assertNull($crud->getSearchFormBuilder());

        $crud->createView();
        $this->assertNull($crud->getSearchForm());
    }

    public function testGetSearchFormBuilderAfterCreateView(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->createSearchForm(new UserSearcher());
        $crud = $this->createCrud($crudConfig);
        $crud->createView();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The object "SearchFormBuilder" no longer exists since the call of the method "Crud::createView"');
        $crud->getSearchFormBuilder();
    }

    public function testGetSearchFormBeforeCreateView(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->createSearchForm(new UserSearcher());
        $crud = $this->createCrud($crudConfig);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The object "FormView" exists only after the call of the method "Crud::createView"');
        $crud->getSearchForm();
    }

    public function testAddBadSearchFormData(): void
    {
        $crudConfig = $this->createValidCrudConfig();
        $crudConfig['search_form_data'] = 'bad';

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "search_form_data" with value "bad" is expected to be of type "null" or "Ecommit\CrudBundle\Form\Searcher\SearcherInterface"');
        $this->createCrud($crudConfig);
    }

    public function testCreateSearchFormWithType(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->createSearchForm(new UserSearcher(), FormType::class, [
                'form_options' => [
                    'data_class' => UserSearcher::class,
                ],
            ]);
        $crud = $this->createCrud($crudConfig);

        $this->assertInstanceOf(FormType::class, $crud->getSearchFormBuilder()->getForm()->getConfig()->getType()->getInnerType());
    }

    public function testCreateSearchFormWithBadType(): void
    {
        $crudConfig = $this->createValidCrudConfig();
        $crudConfig['search_form_type'] = 1;

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "search_form_type" with value 1 is expected to be of type "null" or "string"');
        $this->createCrud($crudConfig);
    }

    public function testCreateSearchFormWithOptions(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->createSearchForm(new UserSearcher(), null, [
                'validation_groups' => ['MyGroup'],
            ]);
        $crud = $this->createCrud($crudConfig);

        $this->assertSame(['MyGroup'], $crud->getSearchFormBuilder()->getForm()->getConfig()->getOption('validation_groups'));
    }

    public function testCreateSearchFormWithBadOptions(): void
    {
        $crudConfig = $this->createValidCrudConfig();
        $crudConfig['search_form_options'] = 'bad';

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "search_form_options" with value "bad" is expected to be of type "array"');
        $this->createCrud($crudConfig);
    }

    /**
     * @dataProvider getBoolProvider
     */
    public function testSetPersistentSettings(bool $value): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->setPersistentSettings($value);
        $this->createCrud($crudConfig);
    }

    public function testSetPersistentSettingsAutoFalse(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->setPersistentSettings(true);
        $crud = $this->createCrud($crudConfig);

        $this->assertFalse($crud->getOptions()['persistent_settings']);
    }

    public function testBadSetPersistentSettings(): void
    {
        $crudConfig = $this->createValidCrudConfig();
        $crudConfig['persistent_settings'] = 'bad';

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "persistent_settings" with value "bad" is expected to be of type "bool"');
        $this->createCrud($crudConfig);
    }

    public function testSetBuildPaginatorTrue(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->setBuildPaginator(true);
        $crud = $this->createCrud($crudConfig)
            ->build();

        $this->assertInstanceOf(DoctrineORMPaginator::class, $crud->getPaginator());
    }

    public function testSetBuildPaginatorFalse(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->setBuildPaginator(false);
        $crud = $this->createCrud($crudConfig)
            ->build();

        $this->assertNull($crud->getPaginator());
    }

    public function testSetBuildPaginatorClosure(): void
    {
        $paginator = $this->createMock(PaginatorInterface::class);
        $crudConfig = $this->createValidCrudConfig()
            ->setBuildPaginator(function (QueryBuilder $queryBuilder, int $page, int $resultsPerPage) use ($paginator) {
                $this->assertInstanceOf(QueryBuilder::class, $queryBuilder);
                $this->assertSame(1, $page);
                $this->assertSame(50, $resultsPerPage);

                return $paginator;
            });
        $crud = $this->createCrud($crudConfig)
            ->build();

        $this->assertSame($paginator, $crud->getPaginator());
    }

    public function testSetBadBuildPaginator(): void
    {
        $crudConfig = $this->createValidCrudConfig();
        $crudConfig['build_paginator'] = 'bad';

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "build_paginator" with value "bad" is expected to be of type "bool" or "Closure" or "array"');
        $this->createCrud($crudConfig);
    }

    public function testBuild(): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig());

        $this->assertInstanceOf(Crud::class, $crud->build());
    }

    public function testDivIdList(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->setDivIdList('val');
        $crud = $this->createCrud($crudConfig);

        $this->assertSame('val', $crud->getDivIdList());
    }

    public function testSetBadDivIdList(): void
    {
        $crudConfig = $this->createValidCrudConfig();
        $crudConfig['div_id_list'] = 1;

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "div_id_list" with value 1 is expected to be of type "string"');
        $this->createCrud($crudConfig);
    }

    public function testDivIdSearch(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->setDivIdSearch('val');
        $crud = $this->createCrud($crudConfig);

        $this->assertSame('val', $crud->getDivIdSearch());
    }

    public function testSetBadDivIdSearch(): void
    {
        $crudConfig = $this->createValidCrudConfig();
        $crudConfig['div_id_search'] = 1;

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "div_id_search" with value 1 is expected to be of type "string"');
        $this->createCrud($crudConfig);
    }

    public function testDisplayResultsOnlyIfSearch(): void
    {
        $crudConfig = $this->createValidCrudConfig(withSearcher: true)
            ->setDisplayResultsOnlyIfSearch(true);
        $crud = $this->createCrud($crudConfig);
        $this->assertTrue($crud->getDisplayResultsOnlyIfSearch());

        $crud->build();

        $this->assertFalse($crud->getDisplayResults());
        $this->assertNull($crud->getPaginator());
    }

    public function testDisplayResultsOnlyIfSearchWithoutSearcher(): void
    {
        $crudConfig = $this->createValidCrudConfig(withSearcher: false)
            ->setDisplayResultsOnlyIfSearch(true);
        $crud = $this->createCrud($crudConfig);
        $this->assertTrue($crud->getDisplayResultsOnlyIfSearch());

        $crud->build();

        $this->assertTrue($crud->getDisplayResults());
        $this->assertNotNull($crud->getPaginator());
    }

    public function testSetBadDisplayResultsOnlyIfSearch(): void
    {
        $crudConfig = $this->createValidCrudConfig();
        $crudConfig['display_results_only_if_search'] = 'bad';

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "display_results_only_if_search" with value "bad" is expected to be of type "bool"');
        $this->createCrud($crudConfig);
    }

    public function testDisplayResultsTrue(): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig());

        $this->assertTrue($crud->getDisplayResults());
        $this->assertInstanceOf(Crud::class, $crud->setDisplayResults(true));

        $crud->build();

        $this->assertTrue($crud->getDisplayResults());
        $this->assertNotNull($crud->getPaginator());
    }

    public function testDisplayResultsFalse(): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig());

        $this->assertTrue($crud->getDisplayResults());
        $this->assertInstanceOf(Crud::class, $crud->setDisplayResults(false));

        $crud->build();

        $this->assertFalse($crud->getDisplayResults());
        $this->assertNull($crud->getPaginator());
    }

    public function testTwigFunctionsConfiguration(): void
    {
        $config = [
            'function1' => ['val'],
        ];

        $crudConfig = $this->createValidCrudConfig()
            ->setTwigFunctionsConfiguration($config);
        $crud = $this->createCrud($crudConfig);

        $this->assertSame($config, $crud->getTwigFunctionsConfiguration());
        $this->assertSame(['val'], $crud->getTwigFunctionConfiguration('function1'));
    }

    public function testGetTwigFunctionConfigurationNotExists(): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig());

        $this->assertSame([], $crud->getTwigFunctionConfiguration('function1'));
    }

    public function testSetBadDTwigFunctionsConfiguration(): void
    {
        $crudConfig = $this->createValidCrudConfig();
        $crudConfig['twig_functions_configuration'] = 'bad';

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "twig_functions_configuration" with value "bad" is expected to be of type "array"');
        $this->createCrud($crudConfig);
    }

    public function testGetOptions(): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig());

        $this->assertIsArray($crud->getOptions());
    }

    public function testPaginator(): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig());
        $this->assertNull($crud->getPaginator());

        $crud->build();
        $this->assertInstanceOf(PaginatorInterface::class, $crud->getPaginator());

        $this->assertInstanceOf(Crud::class, $crud->setPaginator(null));
        $this->assertNull($crud->getPaginator());

        $this->assertInstanceOf(Crud::class, $crud->setPaginator($this->createMock(PaginatorInterface::class)));
        $this->assertInstanceOf(PaginatorInterface::class, $crud->getPaginator());
    }

    public function testGetDisplaySettingsForm(): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig());
        $this->assertInstanceOf(Form::class, $crud->getDisplaySettingsForm());

        $crud->createView();
        $this->assertInstanceOf(FormView::class, $crud->getDisplaySettingsForm());
    }

    /**
     * @dataProvider getTestRequiredOptionsProvider
     */
    public function testRequiredOptions(string $option): void
    {
        $crudConfig = $this->createValidCrudConfig();
        unset($crudConfig[$option]);

        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage($option);
        $this->createCrud($crudConfig);
    }

    public function getTestRequiredOptionsProvider(): array
    {
        return [
            ['session_name'],
            ['columns'],
            ['max_per_page_choices'],
            ['default_max_per_page'],
            ['default_sort'],
            ['query_builder'],
            ['route_name'],
        ];
    }

    public function testReset(): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig());

        $this->assertInstanceOf(Crud::class, $crud->reset());
    }

    public function testResetSort(): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig());

        $this->assertInstanceOf(Crud::class, $crud->resetSort());
    }

    public function testCreateView(): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig());

        $this->assertInstanceOf(Crud::class, $crud->createView());
    }

    public function testCreateViewAlreadyDone(): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig())->createView();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Crud::createView has already been called');
        $crud->createView();
    }

    public function testLoadSessionWithSearchForm(): void
    {
        $sessionValue = new CrudSession(10, ['username'], 'firstName', Crud::ASC, new UserSearcher());
        $crud = $this->createCrud(crudConfig: $this->createValidCrudConfig(withSearcher: true), sessionValue: clone $sessionValue);

        $this->assertEquals($sessionValue, $crud->getSessionValues());
    }

    public function testLoadSessionWithoutSearchForm(): void
    {
        $sessionValue = new CrudSession(10, ['username'], 'firstName', Crud::ASC);
        $crud = $this->createCrud(crudConfig: $this->createValidCrudConfig(), sessionValue: clone $sessionValue);

        $this->assertEquals($sessionValue, $crud->getSessionValues());
    }

    public function testChangeNumberResultsDisplayed(): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig());

        $reflectionMethod = (new \ReflectionClass($crud))->getMethod('changeNumberResultsDisplayed');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($crud, 10);
        $this->assertSame(10, $crud->getSessionValues()->resultsPerPage);
    }

    public function testChangeNumberResultsDisplayedWithBadValue(): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig());

        $reflectionMethod = (new \ReflectionClass($crud))->getMethod('changeNumberResultsDisplayed');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($crud, 99);
        $this->assertSame(50, $crud->getSessionValues()->resultsPerPage);
    }

    public function testChangeColumnsDisplayed(): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig());

        $reflectionMethod = (new \ReflectionClass($crud))->getMethod('changeColumnsDisplayed');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($crud, ['username']);
        $this->assertSame(['username'], $crud->getSessionValues()->displayedColumns);
    }

    /**
     * @dataProvider getTestChangeColumnsDisplayedWithBadValueProvider
     */
    public function testChangeColumnsDisplayedWithBadValue(array $value): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig());

        $reflectionMethod = (new \ReflectionClass($crud))->getMethod('changeColumnsDisplayed');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($crud, $value);
        $this->assertSame(['firstName'], $crud->getSessionValues()->displayedColumns);
    }

    public function getTestChangeColumnsDisplayedWithBadValueProvider(): array
    {
        return [
            [['bad_column']],
            [[]],
        ];
    }

    public function testChangeSort(): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig());

        $reflectionMethod = (new \ReflectionClass($crud))->getMethod('changeSort');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($crud, 'firstName');
        $this->assertSame('firstName', $crud->getSessionValues()->sort);
    }

    /**
     * @dataProvider getTestChangeSortWithBadValueProvider
     */
    public function testChangeSortWithBadValue(mixed $value): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->addColumn('column_not_sortable', 'alias', 'label', ['sortable' => false]);
        $crud = $this->createCrud($crudConfig);

        $reflectionMethod = (new \ReflectionClass($crud))->getMethod('changeSort');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($crud, $value);
        $this->assertSame('username', $crud->getSessionValues()->sort);
    }

    public function getTestChangeSortWithBadValueProvider(): array
    {
        return [
            [null],
            [['val']], // Not scalar
            ['bad_column'],
            ['column_not_sortable'],
            ['defaultPersonalizedSort'],
        ];
    }

    public function testChangeSortPersonalizedSort(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->setDefaultPersonalizedSort(['criteria']);
        $crud = $this->createCrud($crudConfig);

        $reflectionMethod = (new \ReflectionClass($crud))->getMethod('changeSort');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($crud, 'defaultPersonalizedSort');
        $this->assertSame('defaultPersonalizedSort', $crud->getSessionValues()->sort);
    }

    /**
     * @dataProvider getTestChangeSortPersonalizedSortWithBadValueProvider
     */
    public function testChangeSortPersonalizedSortWithBadValue(mixed $value): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->setDefaultPersonalizedSort(['criteria']);
        $crud = $this->createCrud($crudConfig);

        $reflectionMethod = (new \ReflectionClass($crud))->getMethod('changeSort');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($crud, $value);
        $this->assertSame('defaultPersonalizedSort', $crud->getSessionValues()->sort);
    }

    public function getTestChangeSortPersonalizedSortWithBadValueProvider(): array
    {
        return [
            [null],
            [['val']], // Not scalar
            ['bad_column'],
            ['column_not_sortable'],
        ];
    }

    public function testChangeSortDirection(): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig());

        $reflectionMethod = (new \ReflectionClass($crud))->getMethod('changeSortDirection');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($crud, Crud::ASC);
        $this->assertSame(Crud::ASC, $crud->getSessionValues()->sortDirection);
    }

    /**
     * @dataProvider getTestChangeSortDirectionWithBadValueProvider
     */
    public function testChangeSortDirectionWithBadValue(mixed $value): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig());

        $reflectionMethod = (new \ReflectionClass($crud))->getMethod('changeSortDirection');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($crud, $value);
        $this->assertSame(Crud::DESC, $crud->getSessionValues()->sortDirection);
    }

    public function getTestChangeSortDirectionWithBadValueProvider(): array
    {
        return [
            [null],
            [['val']], // Not scalar
            ['bad_direction'],
        ];
    }

    public function testChangeFilterValues(): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig(withSearcher: true));

        $value = new UserSearcher();
        $value->username = 'val';

        $reflectionMethod = (new \ReflectionClass($crud))->getMethod('changeFilterValues');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($crud, $value);
        $this->assertEquals($value, $crud->getSessionValues()->searchFormData);
    }

    /**
     * @dataProvider getTestChangeFilterValuesWithBadValueProvider
     */
    public function testChangeFilterValuesWithBadValue(?SearcherInterface $value): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig(withSearcher: true));

        $reflectionMethod = (new \ReflectionClass($crud))->getMethod('changeFilterValues');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($crud, $value);
        $this->assertNotEquals($value, $crud->getSessionValues()->searchFormData);
        $this->assertEquals(new UserSearcher(), $crud->getSessionValues()->searchFormData);
    }

    public function getTestChangeFilterValuesWithBadValueProvider(): array
    {
        return [
            [null],
            [$this->createMock(SearcherInterface::class)],
        ];
    }

    public function testChangeFilterValuesWithoutSearchForm(): void
    {
        $crud = $this->createCrud($this->createValidCrudConfig());

        $reflectionMethod = (new \ReflectionClass($crud))->getMethod('changeFilterValues');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($crud, new UserSearcher());
        $this->assertNull($crud->getSessionValues()->searchFormData);
    }

    public function getBoolProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }
}

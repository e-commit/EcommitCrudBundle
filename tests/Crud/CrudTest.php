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
use Doctrine\Persistence\ManagerRegistry;
use Ecommit\CrudBundle\Crud\Crud;
use Ecommit\CrudBundle\Crud\CrudColumn;
use Ecommit\CrudBundle\Crud\CrudFilters;
use Ecommit\CrudBundle\Crud\CrudSession;
use Ecommit\CrudBundle\Crud\SearchFormBuilder;
use Ecommit\CrudBundle\Form\Filter\FilterInterface;
use Ecommit\CrudBundle\Form\Searcher\SearcherInterface;
use Ecommit\CrudBundle\Tests\Functional\App\Entity\TestUser;
use Ecommit\CrudBundle\Tests\Functional\App\Form\Searcher\UserSearcher;
use Ecommit\DoctrineUtils\Paginator\DoctrineORMPaginator;
use Ecommit\Paginator\PaginatorInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class CrudTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    /**
     * @dataProvider getTestCrudWithInvalidSessionNameProvider
     */
    public function testCrudWithInvalidSessionName(string $sessionName): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The session name format is invalid');

        $this->createCrud(sessionName: $sessionName);
    }

    public function getTestCrudWithInvalidSessionNameProvider(): array
    {
        return [
            [''],
            ['aa#bb'],
            ['aa bb'],
            [str_pad('', 101, 'a')],
        ];
    }

    public function testGetSessionName(): void
    {
        $crud = $this->createCrud(sessionName: 'session_name');
        $this->assertSame('session_name', $crud->getSessionName());
    }

    public function testGetSessionValues(): void
    {
        $crud = $this->createValidCrud()
            ->init();

        $this->assertInstanceOf(CrudSession::class, $crud->getSessionValues());
    }

    public function testColumns(): void
    {
        $crud = $this->createCrud();

        $this->assertSame([], $crud->getColumns());
        $this->assertInstanceOf(Crud::class, $crud->addColumn('username', 'u.username', 'username'));
        $this->assertInstanceOf(Crud::class, $crud->addColumn('firstName', 'u.firstName', 'first_name', [
            'sortable' => false,
            'default_displayed' => false,
            'alias_search' => 'alias_search2',
            'alias_sort' => 'alias_sort2',
        ]));
        $column1 = new CrudColumn('username', 'u.username', 'username', true, true);
        $column2 = new CrudColumn('firstName', 'u.firstName', 'first_name', false, false, 'alias_search2', 'alias_sort2');
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
        $crud = $this->createValidCrud()
            ->addColumn('my_column', 'u.alias', 'My Column', ['alias_sort' => 'u.lastName'])
            ->setDefaultSort('my_column', Crud::ASC)
            ->init()
            ->build();

        $this->assertSame(['u.lastName ASC'], $crud->getQueryBuilder()->getDQLPart('orderBy')[0]->getParts());
    }

    public function testAddColumnTooLong(): void
    {
        $columnId = str_pad('', 101, 'a');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The column id "'.$columnId.'" is too long');

        $crud = $this->createCrud();
        $crud->addColumn($columnId, 'alias', 'label');
    }

    /**
     * @dataProvider getTestAddColumnAlreadyExistsProvider
     */
    public function testAddColumnAlreadyExists(callable $callback): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The column "column1" already exists');

        $callback($this->createCrud());
    }

    public function getTestAddColumnAlreadyExistsProvider(): array
    {
        return [
            [function (Crud $crud): void {
                $crud->addColumn('column1', 'alias1', 'label1')
                    ->addColumn('column1', 'alias1', 'label1');
            }],
            [function (Crud $crud): void {
                $crud->addVirtualColumn('column1', 'alias1', 'label1')
                    ->addVirtualColumn('column1', 'alias1', 'label1');
            }],
            [function (Crud $crud): void {
                $crud->addColumn('column1', 'alias1', 'label1')
                    ->addVirtualColumn('column1', 'alias1', 'label1');
            }],
            [function (Crud $crud): void {
                $crud->addVirtualColumn('column1', 'alias1', 'label1')
                    ->addColumn('column1', 'alias1', 'label1');
            }],
        ];
    }

    public function testAddColumnWithInvalidOption(): void
    {
        $this->expectException(UndefinedOptionsException::class);

        $crud = $this->createCrud();
        $crud->addColumn('column1', 'alias1', 'label1', [
            'bad_option' => 'value',
        ]);
    }

    public function testAddColumnWithInvalidTypeSortableOption(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $crud = $this->createCrud();
        $crud->addColumn('column1', 'alias1', 'label1', [
            'sortable' => 'not_bool',
        ]);
    }

    public function testAddColumnWithInvalidTypeDefaultDisplayedOption(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $crud = $this->createCrud();
        $crud->addColumn('column1', 'alias1', 'label1', [
            'default_displayed' => 'not_bool',
        ]);
    }

    public function testGetColumnNotExists(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The column "invalid" does not exist');

        $crud = $this->createCrud();
        $crud->getColumn('invalid');
    }

    public function testGetDefaultDisplayedColumns(): void
    {
        $crud = $this->createValidCrud();

        $this->assertSame(['firstName'], $crud->getDefaultDisplayedColumns());
    }

    public function testGetDefaultDisplayedColumnsEmpty(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('At least one column is required');

        $crud = $this->createCrud();
        $crud->getDefaultDisplayedColumns();
    }

    public function testVirtualColumns(): void
    {
        $crud = $this->createCrud();

        $this->assertSame([], $crud->getVirtualColumns());

        $this->assertInstanceOf(Crud::class, $crud->addVirtualColumn('columnv1', 'aliasv1'));
        $this->assertInstanceOf(Crud::class, $crud->addVirtualColumn('columnv2', 'aliasv2'));

        $column1 = new CrudColumn('columnv1', 'aliasv1', null, false, false);
        $column2 = new CrudColumn('columnv2', 'aliasv2', null, false, false);
        $this->assertEquals($column1, $crud->getVirtualColumn('columnv1'));
        $this->assertEquals($column2, $crud->getVirtualColumn('columnv2'));

        $columns = [
            'columnv1' => $column1,
            'columnv2' => $column2,
        ];
        $this->assertEquals($columns, $crud->getVirtualColumns());
    }

    public function testGetVirtualColumnNotExists(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The column "invalid" does not exist');

        $crud = $this->createCrud();
        $crud->getVirtualColumn('invalid');
    }

    public function testQueryBuilder(): void
    {
        $crud = $this->createCrud();

        $this->assertNull($crud->getQueryBuilder());
        $queryBuilder = self::getContainer()->get(ManagerRegistry::class)->getRepository(TestUser::class)
            ->createQueryBuilder('u')
            ->select('u');
        $this->assertInstanceOf(Crud::class, $crud->setQueryBuilder($queryBuilder));
        $this->assertSame($queryBuilder, $crud->getQueryBuilder());
    }

    public function testAvailableResultsPerPage(): void
    {
        $crud = $this->createCrud();

        $this->assertSame([], $crud->getAvailableResultsPerPage());
        $this->assertNull($crud->getDefaultResultsPerPage());
        $this->assertInstanceOf(Crud::class, $crud->setAvailableResultsPerPage([10, 50, 100], 50));
        $this->assertSame([10, 50, 100], $crud->getAvailableResultsPerPage());
        $this->assertSame(50, $crud->getDefaultResultsPerPage());
    }

    public function testSetAvailableResultsPerPageWithEmptyArray(): void
    {
        $crud = $this->createCrud();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The 1st argument of the "setAvailableResultsPerPage" method must contain at least one integer');

        $crud->setAvailableResultsPerPage([], 1);
    }

    public function testSetAvailableResultsPerPageWithNotInteger(): void
    {
        $crud = $this->createCrud();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The 1st argument of the "setAvailableResultsPerPage" method must contain only integers');

        $crud->setAvailableResultsPerPage([1, '10'], 1);
    }

    public function testSort(): void
    {
        $crud = $this->createCrud();

        $this->assertNull($crud->getDefaultSort());
        $this->assertNull($crud->getDefaultSortDirection());
        $this->assertInstanceOf(Crud::class, $crud->setDefaultSort('username', Crud::DESC));
        $this->assertSame('username', $crud->getDefaultSort());
        $this->assertSame(Crud::DESC, $crud->getDefaultSortDirection());
    }

    public function testDefaultPersonalizedSort(): void
    {
        $crud = $this->createCrud();

        $this->assertSame([], $crud->getDefaultPersonalizedSort());

        $this->assertInstanceOf(Crud::class, $crud->setDefaultPersonalizedSort(['u.userId']));

        $this->assertSame(['u.userId'], $crud->getDefaultPersonalizedSort());
        $this->assertSame('defaultPersonalizedSort', $crud->getDefaultSort());
        $this->assertSame(Crud::ASC, $crud->getDefaultSortDirection());
    }

    public function testRouting(): void
    {
        $crud = $this->createCrud();

        $this->assertNull($crud->getRouteName());
        $this->assertSame([], $crud->getRouteParams());
        $this->assertInstanceOf(Crud::class, $crud->setRoute('user_ajax_crud', ['param1' => 'val1']));
        $this->assertSame('user_ajax_crud', $crud->getRouteName());
        $this->assertSame(['param1' => 'val1'], $crud->getRouteParams());
        $this->assertSame('/user/ajax-crud?param1=val1', $crud->getUrl());
        $this->assertSame('/user/ajax-crud?param1=val1&param2=val2', $crud->getUrl(['param2' => 'val2']));
        $this->assertSame('/user/ajax-crud?param1=val1&search=1', $crud->getSearchUrl());
        $this->assertSame('/user/ajax-crud?param1=val1&search=1&param2=val2', $crud->getSearchUrl(['param2' => 'val2']));
    }

    public function testCreateAndGetSearchForm(): void
    {
        $crud = $this->createValidCrud();

        $this->assertNull($crud->getSearchForm());

        $crud->createSearchForm(new UserSearcher());
        $this->assertInstanceOf(SearchFormBuilder::class, $crud->getSearchForm());

        $crud->init()
            ->createView();
        $this->assertInstanceOf(FormView::class, $crud->getSearchForm());
    }

    public function testSetPersistentSettings(): void
    {
        $crud = $this->createCrud();

        $this->assertInstanceOf(Crud::class, $crud->setPersistentSettings(true));
    }

    public function testSetBuildPaginatorTrue(): void
    {
        $crud = $this->createValidCrud();

        $this->assertInstanceOf(Crud::class, $crud->setBuildPaginator(true));
        $crud->init()
            ->build();

        $this->assertInstanceOf(DoctrineORMPaginator::class, $crud->getPaginator());
    }

    public function testSetBuildPaginatorFalse(): void
    {
        $crud = $this->createValidCrud();

        $this->assertInstanceOf(Crud::class, $crud->setBuildPaginator(false));
        $crud->init()
            ->build();

        $this->assertNull($crud->getPaginator());
    }

    public function testSetBuildPaginatorClosure(): void
    {
        $crud = $this->createValidCrud();

        $this->assertInstanceOf(Crud::class, $crud->setBuildPaginator(function (QueryBuilder $queryBuilder, int $page, int $resultsPerPage) {
            $this->assertInstanceOf(QueryBuilder::class, $queryBuilder);
            $this->assertSame(1, $page);
            $this->assertSame(50, $resultsPerPage);

            return $this->createMock(PaginatorInterface::class);
        }));
        $crud->init()
            ->build();

        $this->assertInstanceOf(PaginatorInterface::class, $crud->getPaginator());
    }

    public function testBuild(): void
    {
        $crud = $this->createValidCrud()
            ->init();

        $this->assertInstanceOf(Crud::class, $crud->build());
    }

    public function testDivIdList(): void
    {
        $crud = $this->createCrud();

        $this->assertSame('crud_list', $crud->getDivIdList());
        $this->assertInstanceOf(Crud::class, $crud->setDivIdList('val'));
        $this->assertSame('val', $crud->getDivIdList());
    }

    public function testDivIdSearch(): void
    {
        $crud = $this->createCrud();

        $this->assertSame('crud_search', $crud->getDivIdSearch());
        $this->assertInstanceOf(Crud::class, $crud->setDivIdSearch('val'));
        $this->assertSame('val', $crud->getDivIdSearch());
    }

    public function testDisplayResultsOnlyIfSearch(): void
    {
        $crud = $this->createValidCrud(withSearcher: true);

        $this->assertTrue($crud->getDisplayResults());
        $this->assertFalse($crud->getDisplayResultsOnlyIfSearch());
        $this->assertInstanceOf(Crud::class, $crud->setDisplayResultsOnlyIfSearch(true));
        $this->assertTrue($crud->getDisplayResultsOnlyIfSearch());

        $crud->init()
            ->build();
        $this->assertFalse($crud->getDisplayResults());
        $this->assertNull($crud->getPaginator());
    }

    public function testDisplayResultsOnlyIfSearchWithoutSearcher(): void
    {
        $crud = $this->createValidCrud();

        $this->assertTrue($crud->getDisplayResults());
        $this->assertFalse($crud->getDisplayResultsOnlyIfSearch());
        $this->assertInstanceOf(Crud::class, $crud->setDisplayResultsOnlyIfSearch(true));
        $this->assertTrue($crud->getDisplayResultsOnlyIfSearch());

        $crud->init()
            ->build();
        $this->assertTrue($crud->getDisplayResults());
        $this->assertNotNull($crud->getPaginator());
    }

    public function testDisplayResultsTrue(): void
    {
        $crud = $this->createValidCrud();

        $this->assertTrue($crud->getDisplayResults());
        $this->assertInstanceOf(Crud::class, $crud->setDisplayResults(true));

        $crud->init()
            ->build();
        $this->assertTrue($crud->getDisplayResults());
        $this->assertNotNull($crud->getPaginator());
    }

    public function testDisplayResultsFalse(): void
    {
        $crud = $this->createValidCrud();

        $this->assertTrue($crud->getDisplayResults());
        $this->assertInstanceOf(Crud::class, $crud->setDisplayResults(false));

        $crud->init()
            ->build();
        $this->assertFalse($crud->getDisplayResults());
        $this->assertNull($crud->getPaginator());
    }

    public function testDisplayResultsFalseAfterInit(): void
    {
        $crud = $this->createValidCrud()
            ->init()
            ->build();

        $this->assertInstanceOf(Crud::class, $crud->setDisplayResults(false));
        $this->assertFalse($crud->getDisplayResults());
        $this->assertNull($crud->getPaginator());
    }

    public function testTwigFunctionsConfiguration(): void
    {
        $crud = $this->createCrud();

        $this->assertSame([], $crud->getTwigFunctionsConfiguration());
        $config = [
            'function1' => ['val'],
        ];
        $this->assertInstanceOf(Crud::class, $crud->setTwigFunctionsConfiguration($config));
        $this->assertSame($config, $crud->getTwigFunctionsConfiguration());
        $this->assertSame(['val'], $crud->getTwigFunctionConfiguration('function1'));
    }

    public function testGetTwigFunctionConfigurationNotExists(): void
    {
        $crud = $this->createCrud();

        $this->assertSame([], $crud->getTwigFunctionConfiguration('function1'));
    }

    public function testPaginator(): void
    {
        $crud = $this->createValidCrud();

        $this->assertNull($crud->getPaginator());

        $crud->init()
            ->build();
        $this->assertInstanceOf(PaginatorInterface::class, $crud->getPaginator());

        $this->assertInstanceOf(Crud::class, $crud->setPaginator(null));
        $this->assertNull($crud->getPaginator());

        $this->assertInstanceOf(Crud::class, $crud->setPaginator($this->createMock(PaginatorInterface::class)));
        $this->assertInstanceOf(PaginatorInterface::class, $crud->getPaginator());
    }

    public function testGetDisplaySettingsForm(): void
    {
        $crud = $this->createValidCrud();
        $this->assertNull($crud->getDisplaySettingsForm());

        $crud->init();
        $this->assertInstanceOf(Form::class, $crud->getDisplaySettingsForm());

        $crud->createView();
        $this->assertInstanceOf(FormView::class, $crud->getDisplaySettingsForm());
    }

    /**
     * @dataProvider getTestIncompleteInitProvider
     */
    public function testIncompleteInit(string $property, string $method, mixed $value): void
    {
        $crud = $this->createValidCrud();
        $reflectionClass = new \ReflectionClass($crud);
        $reflectionProperty = $reflectionClass->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($crud, $value);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($method);

        $crud->init();
    }

    public function getTestIncompleteInitProvider(): array
    {
        return [
            ['availableColumns', 'addColumn', []],
            ['availableResultsPerPage', 'setAvailableResultsPerPage', []],
            ['defaultResultsPerPage', 'setAvailableResultsPerPage', null],
            ['defaultSort', 'setDefaultSort', null],
            ['defaultSortDirection', 'setDefaultSort', null],
            ['queryBuilder', 'setQueryBuilder', null],
            ['routeName', 'setRoute', null],
        ];
    }

    public function testInitIfNecessary(): void
    {
        $crud = $this->createValidCrud();
        $this->assertFalse($crud->isInitialized());

        $this->assertInstanceOf(Crud::class, $crud->initIfNecessary());
        $this->assertTrue($crud->isInitialized());
    }

    public function testInitIfNecessaryAfterInit(): void
    {
        $crud = $this->createValidCrud()
            ->init()
            ->initIfNecessary();

        $this->assertTrue($crud->isInitialized());
    }

    public function testInit(): void
    {
        $crud = $this->createValidCrud();
        $this->assertFalse($crud->isInitialized());

        $this->assertInstanceOf(Crud::class, $crud->init());
        $this->assertTrue($crud->isInitialized());
    }

    public function testInitAfterInit(): void
    {
        $crud = $this->createValidCrud()
            ->init();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('CRUD already initialized');

        $crud->init();
    }

    public function testReset(): void
    {
        $crud = $this->createValidCrud()
            ->init();

        $this->assertInstanceOf(Crud::class, $crud->reset());
    }

    public function testResetSort(): void
    {
        $crud = $this->createValidCrud()
            ->init();

        $this->assertInstanceOf(Crud::class, $crud->resetSort());
    }

    public function testCreateView(): void
    {
        $crud = $this->createValidCrud()
            ->init();

        $this->assertInstanceOf(Crud::class, $crud->createView());
    }

    public function testCallCreateSearchFormBeforeRequiredOptions(): void
    {
        $filter = $this->createMock(FilterInterface::class);
        $filter->expects($this->exactly(4))->method('buildForm')->willReturnCallback(function (SearchFormBuilder $builder, string $property, array $options): void {
            if (\in_array($property, ['userId', 'lastName'])) { // Virtual columns
                $this->assertNull($options['label']);
            } else {
                $expectedLabel = 'label_'.$property;
                $this->assertSame($expectedLabel, $options['label']);
            }
        });
        $filter->expects($this->exactly(4))->method('supportsQueryBuilder')->willReturn(true);
        $filter->expects($this->exactly(4))->method('updateQueryBuilder')->willReturnCallback(function ($queryBuilder, string $property, $value, array $options): void {
            $this->assertSame('u.'.$property, $options['alias_search']);
        });

        $searcherData = $this->getMockBuilder(SearcherInterface::class)
            ->addMethods(['getUsername', 'getFirstName', 'getLastName', 'getUserId'])
            ->onlyMethods(['buildForm', 'updateQueryBuilder', 'configureOptions'])
            ->getMock();
        $searcherData->expects($this->once())->method('buildForm')->willReturnCallback(function (SearchFormBuilder $builder, array $options): void {
            $builder->addFilter('username', 'my_filter');
            $builder->addFilter('userId', 'my_filter');
        });
        $searcherData->method('getUsername')->willReturn('val');
        $searcherData->method('getFirstName')->willReturn('val');
        $searcherData->method('getLastName')->willReturn('val');
        $searcherData->method('getUserId')->willReturn('val');

        $queryBuilder = self::getContainer()->get(ManagerRegistry::class)->getRepository(TestUser::class)
            ->createQueryBuilder('u')
            ->select('u');

        $crud = $this->createCrud(filters: ['my_filter' => $filter])
            ->createSearchForm($searcherData); // Call createSearchForm before setting options : alias_search and label are not known yet
        $crud->getSearchForm()->addFilter('firstName', 'my_filter'); // Call addFilter before setting options : alias_search and label are not known yet
        $crud->getSearchForm()->addFilter('lastName', 'my_filter'); // Call addFilter before setting options : alias_search and label are not known yet
        $crud->addColumn('username', 'u.username', 'label_username')
            ->addColumn('firstName', 'u.firstName', 'label_firstName')
            ->addVirtualColumn('lastName', 'u.lastName')
            ->addVirtualColumn('userId', 'u.userId')
            ->setQueryBuilder($queryBuilder)
            ->setAvailableResultsPerPage([10, 50, 100], 50)
            ->setDefaultSort('username', Crud::ASC)
            ->setRoute('user_ajax_crud')
            ->init()
            ->build();
    }

    /**
     * @dataProvider getTestCallMethodNotAllowedBeforeInitializationProvider
     */
    public function testCallMethodNotAllowedBeforeInitialization(string $method, array $arguments = []): void
    {
        $crud = $this->createValidCrud(withSearcher: true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(sprintf('The method "%s" cannot be called before CRUD initialization', $method));

        $crud->$method(...$arguments);
    }

    public function getTestCallMethodNotAllowedBeforeInitializationProvider(): array
    {
        return [
            ['getSessionValues'],
            ['processSearchForm'],
            ['build'],
            ['createView'],
        ];
    }

    /**
     * @dataProvider getTestCallMethodNotAllowedAfterInitializationProvider
     */
    public function testCallMethodNotAllowedAfterInitialization(string $method, array $arguments = []): void
    {
        $crud = $this->createValidCrud(withSearcher: true)
            ->init();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(sprintf('The method "%s" cannot be called after CRUD initialization', $method));

        $crud->$method(...$arguments);
    }

    public function getTestCallMethodNotAllowedAfterInitializationProvider(): array
    {
        return [
            ['addColumn', ['column', 'alias', 'label']],
            ['addVirtualColumn', ['column', 'alias']],
            ['setQueryBuilder', [$this->createMock(QueryBuilder::class)]],
            ['setAvailableResultsPerPage', [[5, 10], 10]],
            ['setDefaultSort', ['column', Crud::ASC]],
            ['setDefaultPersonalizedSort', [[]]],
            ['setRoute', ['route']],
            ['setDisplayResultsOnlyIfSearch', [true]],
            ['setBuildPaginator', [true]],
            ['setPersistentSettings', [true]],
            ['createSearchForm', [$this->createMock(SearcherInterface::class)]],
            ['setDivIdSearch', ['div']],
            ['setDivIdList', ['div']],
        ];
    }

    public function testLoadSessionWithSearchForm(): void
    {
        $sessionValue = new CrudSession(10, ['username'], 'firstName', Crud::ASC, new UserSearcher());
        $crud = $this->createValidCrud(crud: $this->createCrud(sessionValue: clone $sessionValue), withSearcher: true);
        $crud->init();

        $this->assertEquals($sessionValue, $crud->getSessionValues());
    }

    public function testLoadSessionWithoutSearchForm(): void
    {
        $sessionValue = new CrudSession(10, ['username'], 'firstName', Crud::ASC);
        $crud = $this->createValidCrud(crud: $this->createCrud(sessionValue: clone $sessionValue));
        $crud->init();

        $this->assertEquals($sessionValue, $crud->getSessionValues());
    }

    public function testChangeNumberResultsDisplayed(): void
    {
        $crud = $this->createValidCrud();
        $crud->init();

        $reflectionMethod = (new \ReflectionClass($crud))->getMethod('changeNumberResultsDisplayed');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($crud, 10);
        $this->assertSame(10, $crud->getSessionValues()->resultsPerPage);
    }

    public function testChangeNumberResultsDisplayedWithBadValue(): void
    {
        $crud = $this->createValidCrud();
        $crud->init();

        $reflectionMethod = (new \ReflectionClass($crud))->getMethod('changeNumberResultsDisplayed');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($crud, 99);
        $this->assertSame(50, $crud->getSessionValues()->resultsPerPage);
    }

    public function testChangeColumnsDisplayed(): void
    {
        $crud = $this->createValidCrud();
        $crud->init();

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
        $crud = $this->createValidCrud();
        $crud->init();

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
        $crud = $this->createValidCrud();
        $crud->init();

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
        $crud = $this->createValidCrud()
            ->addColumn('column_not_sortable', 'alias', 'label', ['sortable' => false])
            ->init();

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
        $crud = $this->createValidCrud()
            ->setDefaultPersonalizedSort(['criteria'])
            ->init();

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
        $crud = $this->createValidCrud()
            ->setDefaultPersonalizedSort(['criteria'])
            ->init();

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
        $crud = $this->createValidCrud();
        $crud->init();

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
        $crud = $this->createValidCrud();
        $crud->init();

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
        $crud = $this->createValidCrud(withSearcher: true);
        $crud->init();

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
        $crud = $this->createValidCrud(withSearcher: true);
        $crud->init();

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

    protected function createCrud(string $sessionName = 'session_name', array $filters = [], mixed $sessionValue = null): Crud
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->any())
            ->method('get')
            ->willReturn($sessionValue);
        $session->expects($this->any())
            ->method('set');

        $request = new Request();
        $request->setSession($session);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')
            ->willReturn($request);

        $crudFilters = $this->createMock(CrudFilters::class);
        $crudFilters->method('has')->willReturnCallback(fn (string $name) => \array_key_exists($name, $filters) || static::getContainer()->get('ecommit_crud.filters')->has($name));
        $crudFilters->method('get')->willReturnCallback(function (string $name) use ($filters): FilterInterface {
            if (\array_key_exists($name, $filters)) {
                return $filters[$name];
            }

            return static::getContainer()->get('ecommit_crud.filters')->get($name);
        });

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(function ($name) use ($requestStack, $crudFilters) {
                if ('request_stack' === $name) {
                    return $requestStack;
                } elseif ('ecommit_crud.filters' === $name) {
                    return $crudFilters;
                }

                return static::getContainer()->get('ecommit_crud.locator')->get($name);
            });

        $crud = $this->getMockBuilder(Crud::class)
            ->setConstructorArgs([$sessionName, $container])
            ->onlyMethods(['save'])
            ->getMock();

        return $crud;
    }

    protected function createValidCrud(?Crud $crud = null, bool $withSearcher = false): Crud
    {
        $queryBuilder = self::getContainer()->get(ManagerRegistry::class)->getRepository(TestUser::class)
            ->createQueryBuilder('u')
            ->select('u');

        $crud = ($crud) ?: $this->createCrud('session_name');
        $crud->addColumn('username', 'u.username', 'username', ['default_displayed' => false])
            ->addColumn('firstName', 'u.firstName', 'first_name')
            ->setQueryBuilder($queryBuilder)
            ->setAvailableResultsPerPage([10, 50, 100], 50)
            ->setDefaultSort('username', Crud::DESC)
            ->setRoute('user_ajax_crud', ['param1' => 'val1'])
            ;
        if ($withSearcher) {
            $crud->createSearchForm(new UserSearcher());
        }

        return $crud;
    }
}

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
use Ecommit\CrudBundle\Crud\CrudSession;
use Ecommit\CrudBundle\Crud\SearchFormBuilder;
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
        $this->expectExceptionMessage('Variable sessionName is not given or is invalid');

        $this->createCrud($sessionName);
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
        $crud = $this->createCrud('session_name');
        $this->assertSame('session_name', $crud->getSessionName());
    }

    public function testGetSessionValues(): void
    {
        $crud = $this->createValidCrud();
        $crud->init();

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

    public function testAddColumnTooLong(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Column id is too long');

        $crud = $this->createCrud('session_name');
        $crud->addColumn(str_pad('', 101, 'a'), 'alias', 'label');
    }

    public function testAddColumnWithInvalidOption(): void
    {
        $this->expectException(UndefinedOptionsException::class);

        $crud = $this->createCrud('session_name');
        $crud->addColumn('column1', 'alias1', 'label1', [
            'bad_option' => 'value',
        ]);
    }

    public function testAddColumnWithInvalidTypeSortableOption(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $crud = $this->createCrud('session_name');
        $crud->addColumn('column1', 'alias1', 'label1', [
            'sortable' => 'not_bool',
        ]);
    }

    public function testAddColumnWithInvalidTypeDefaultDisplayedOption(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $crud = $this->createCrud('session_name');
        $crud->addColumn('column1', 'alias1', 'label1', [
            'default_displayed' => 'not_bool',
        ]);
    }

    public function testGetColumnNotExists(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Crud: Column invalid does not exist');

        $crud = $this->createCrud('session_name');
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
        $this->expectExceptionMessage('Config Crud: One column displayed is required');

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
        $this->expectExceptionMessage('Crud: Column invalid does not exist');

        $crud = $this->createCrud('session_name');
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

    public function testSort(): void
    {
        $crud = $this->createCrud();

        $this->assertNull($crud->getDefaultSort());
        $this->assertNull($crud->getDefaultSense());
        $this->assertInstanceOf(Crud::class, $crud->setDefaultSort('username', Crud::DESC));
        $this->assertSame('username', $crud->getDefaultSort());
        $this->assertSame(Crud::DESC, $crud->getDefaultSense());
    }

    public function testDefaultPersonalizedSort(): void
    {
        $crud = $this->createCrud();

        $this->assertSame([], $crud->getDefaultPersonalizedSort());

        $this->assertInstanceOf(Crud::class, $crud->setDefaultPersonalizedSort(['u.userId']));

        $this->assertSame(['u.userId'], $crud->getDefaultPersonalizedSort());
        $this->assertSame('defaultPersonalizedSort', $crud->getDefaultSort());
        $this->assertSame(Crud::ASC, $crud->getDefaultSense());
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

        $crud->init();
        $crud->createView();
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
        $crud->init();
        $crud->build();

        $this->assertInstanceOf(DoctrineORMPaginator::class, $crud->getPaginator());
    }

    public function testSetBuildPaginatorFalse(): void
    {
        $crud = $this->createValidCrud();

        $this->assertInstanceOf(Crud::class, $crud->setBuildPaginator(false));
        $crud->init();
        $crud->build();

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
        $crud->init();
        $crud->build();

        $this->assertInstanceOf(PaginatorInterface::class, $crud->getPaginator());
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
        $crud = $this->createValidCrud(true);

        $this->assertTrue($crud->getDisplayResults());
        $this->assertFalse($crud->getDisplayResultsOnlyIfSearch());
        $this->assertInstanceOf(Crud::class, $crud->setDisplayResultsOnlyIfSearch(true));
        $this->assertTrue($crud->getDisplayResultsOnlyIfSearch());

        $crud->init();
        $crud->build();
        $this->assertFalse($crud->getDisplayResults());
        $this->assertNull($crud->getPaginator());
    }

    public function testDisplayResultsOnlyIfSearchWithoutSearcher(): void
    {
        $crud = $this->createValidCrud(false);

        $this->assertTrue($crud->getDisplayResults());
        $this->assertFalse($crud->getDisplayResultsOnlyIfSearch());
        $this->assertInstanceOf(Crud::class, $crud->setDisplayResultsOnlyIfSearch(true));
        $this->assertTrue($crud->getDisplayResultsOnlyIfSearch());

        $crud->init();
        $crud->build();
        $this->assertTrue($crud->getDisplayResults());
        $this->assertNotNull($crud->getPaginator());
    }

    public function testDisplayResultsTrue(): void
    {
        $crud = $this->createValidCrud();

        $this->assertTrue($crud->getDisplayResults());
        $this->assertInstanceOf(Crud::class, $crud->setDisplayResults(true));

        $crud->init();
        $crud->build();
        $this->assertTrue($crud->getDisplayResults());
        $this->assertNotNull($crud->getPaginator());
    }

    public function testDisplayResultsFalse(): void
    {
        $crud = $this->createValidCrud();

        $this->assertTrue($crud->getDisplayResults());
        $this->assertInstanceOf(Crud::class, $crud->setDisplayResults(false));

        $crud->init();
        $crud->build();
        $this->assertFalse($crud->getDisplayResults());
        $this->assertNull($crud->getPaginator());
    }

    public function testDisplayResultsFalseAfterInit(): void
    {
        $crud = $this->createValidCrud();
        $crud->init();
        $crud->build();

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

        $crud->init();
        $crud->build();
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
            ['defaultSense', 'setDefaultSort', null],
            ['queryBuilder', 'setQueryBuilder', null],
            ['routeName', 'setRoute', null],
        ];
    }

    public function testInitIfNecessary(): void
    {
        $crud = $this->createValidCrud();
        $this->assertFalse($crud->isInitialized());

        $crud->initIfNecessary();
        $this->assertTrue($crud->isInitialized());
    }

    public function testInitIfNecessaryAfterInit(): void
    {
        $crud = $this->createValidCrud();
        $crud->init();

        $crud->initIfNecessary();
        $this->assertTrue($crud->isInitialized());
    }

    public function testInit(): void
    {
        $crud = $this->createValidCrud();
        $this->assertFalse($crud->isInitialized());

        $crud->init();
        $this->assertTrue($crud->isInitialized());
    }

    public function testInitAfterInit(): void
    {
        $crud = $this->createValidCrud();
        $crud->init();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('CRUD already initialized');

        $crud->init();
    }

    protected function createCrud(string $sessionName = 'session_name'): Crud
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->any())
            ->method('get')
            ->willReturn(null);
        $session->expects($this->any())
            ->method('set');

        $request = new Request();
        $request->setSession($session);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')
            ->willReturn($request);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(function ($name) use ($requestStack) {
                if ('request_stack' === $name) {
                    return $requestStack;
                }

                return static::getContainer()->get('ecommit_crud.locator')->get($name);
            });

        $crud = $this->getMockBuilder(Crud::class)
            ->setConstructorArgs([$sessionName, $container])
            ->onlyMethods(['save'])
            ->getMock();

        return $crud;
    }

    protected function createValidCrud(bool $withSearcher = false): Crud
    {
        $queryBuilder = self::getContainer()->get(ManagerRegistry::class)->getRepository(TestUser::class)
            ->createQueryBuilder('u')
            ->select('u');

        $crud = $this->createCrud('session_name')
            ->addColumn('username', 'u.username', 'username', ['default_displayed' => false])
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
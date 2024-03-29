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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Ecommit\CrudBundle\Crud\Crud;
use Ecommit\CrudBundle\Crud\SearchFormBuilder;
use Ecommit\CrudBundle\Form\Filter\FilterInterface;
use Ecommit\CrudBundle\Form\Searcher\SearcherInterface;
use Ecommit\CrudBundle\Form\Type\FormSearchType;
use Psr\Container\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class SearchFormBuilderTest extends AbstractCrudTest
{
    /**
     * @dataProvider getTestCreateFormBuilderProvider
     */
    public function testCreateFormBuilder(?string $type, string $expectedName, string $expectedType): void
    {
        $searchFormBuilder = $this->createSearchFormBuilder(type: $type);
        $this->assertSame($expectedName, $searchFormBuilder->getFormBuilder()->getFormConfig()->getName());
        $this->assertInstanceOf($expectedType, $searchFormBuilder->getFormBuilder()->getFormConfig()->getType()->getInnerType());
    }

    public function getTestCreateFormBuilderProvider(): array
    {
        return [
            [null, 'crud_search_session_name', FormSearchType::class],
            [FormType::class, 'form', FormType::class],
        ];
    }

    /**
     * @dataProvider getTestCreateFormBuilderProvider
     */
    public function testCreateFormBuilderWithFormOptions(?string $type, string $expectedName, string $expectedType): void
    {
        $searchFormBuilder = $this->createSearchFormBuilder(type: $type, options: [
            'form_options' => [
                'attr' => ['class' => 'myclass'],
            ],
        ]);
        $this->assertSame(['class' => 'myclass'], $searchFormBuilder->getFormBuilder()->getFormConfig()->getOption('attr'));
    }

    /**
     * @dataProvider getTestCreateFormBuilderProvider
     */
    public function testCreateFormBuilderWithValidationGroups(?string $type, string $expectedName, string $expectedType): void
    {
        $searchFormBuilder = $this->createSearchFormBuilder(type: $type, options: [
            'validation_groups' => ['my_group'],
        ]);
        $this->assertSame(['my_group'], $searchFormBuilder->getFormBuilder()->getFormConfig()->getOption('validation_groups'));
    }

    /**
     * @dataProvider getTestCreateFormBuilderProvider
     */
    public function testCreateFormBuilderWithValidationGroupsAndFormOptions(?string $type, string $expectedName, string $expectedType): void
    {
        $searchFormBuilder = $this->createSearchFormBuilder(type: $type, options: [
            'validation_groups' => ['my_group1'],
            'form_options' => [
                'validation_groups' => ['my_group2'],
            ],
        ]);
        $this->assertSame(['my_group2'], $searchFormBuilder->getFormBuilder()->getFormConfig()->getOption('validation_groups'));
    }

    public function testAddFilterFormAlreadyCreated(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The "addFilter" method cannot be called after the form creation');

        $this->createSearchFormBuilder()
            ->createForm()
            ->addFilter('property', 'my_filter');
    }

    public function testAddFilterNotExists(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The filter "bad_filter" does not exist');

        $this->createSearchFormBuilder()->addFilter('property', 'bad_filter');
    }

    public function testAddFilter(): void
    {
        $searchFormBuilder = $this->createSearchFormBuilder();
        $this->assertSame($searchFormBuilder, $searchFormBuilder->addFilter('property', 'my_filter'));
        $filters = $this->getPrivateValue($searchFormBuilder, 'filters');
        $this->assertCount(1, $filters);
        $this->assertArrayHasKey('property', $filters);
        $this->assertSame('my_filter', $filters['property']['name']);
    }

    public function testAddFilterAutovalidateDefaultValueFromBuilder(): void
    {
        $searchFormBuilder = $this->createSearchFormBuilder(options: [
            'autovalidate' => false,
        ])
            ->addFilter('property', 'my_filter');
        $filters = $this->getPrivateValue($searchFormBuilder, 'filters');
        $this->assertFalse($filters['property']['options']['autovalidate']);
    }

    public function testAddFilterValidationGroupsDefaultValueFromBuilder(): void
    {
        $searchFormBuilder = $this->createSearchFormBuilder(options: [
            'validation_groups' => ['my_group1'],
        ])
            ->addFilter('property', 'my_filter');
        $filters = $this->getPrivateValue($searchFormBuilder, 'filters');
        $this->assertSame(['my_group1'], $filters['property']['options']['validation_groups']);
    }

    public function testAddFilterAliasSearch(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->addColumn(['id' => 'column1', 'alias' => 'alias1'])
            ->addColumn(['id' => 'column2', 'alias' => 'alias2', 'alias_search' => 'custom_alias2'])
            ->addVirtualColumn('virtual1', 'virtualalias1');
        $crud = $this->createCrud($crudConfig);
        $searchFormBuilder = $this->createSearchFormBuilder(crud: $crud)
            ->addFilter('column1', 'my_filter')
            ->addFilter('column2', 'my_filter')
            ->addFilter('virtual1', 'my_filter');
        $filters = $this->getPrivateValue($searchFormBuilder, 'filters');
        $this->assertSame('alias1', $filters['column1']['options']['alias_search']);
        $this->assertSame('custom_alias2', $filters['column2']['options']['alias_search']);
        $this->assertSame('virtualalias1', $filters['virtual1']['options']['alias_search']);
    }

    public function testAddFilterLabel(): void
    {
        $crudConfig = $this->createValidCrudConfig()
            ->addColumn(['id' => 'column1', 'alias' => 'alias1', 'label' => 'label1'])
            ->addVirtualColumn(['id' => 'virtual1', 'alias' => 'virtualalias1']);
        $crud = $this->createCrud($crudConfig);
        $searchFormBuilder = $this->createSearchFormBuilder(crud: $crud)
            ->addFilter('column1', 'my_filter')
            ->addFilter('virtual1', 'my_filter');
        $filters = $this->getPrivateValue($searchFormBuilder, 'filters');
        $this->assertSame('label1', $filters['column1']['options']['label']);
        $this->assertNull($filters['virtual1']['options']['label']);
    }

    public function testAddField(): void
    {
        $searchFormBuilder = $this->createSearchFormBuilder();
        $this->assertSame($searchFormBuilder, $searchFormBuilder->addField('property', TextType::class, [
            'label' => 'my_label',
        ]));
        $form = $this->getPrivateValue($searchFormBuilder, 'form');
        $this->assertCount(1, $form);
        $this->assertTrue($form->has('property'));
        $field = $form->get('property');
        $this->assertInstanceOf(TextType::class, $field->getFormConfig()->getType()->getInnerType());
        $this->assertSame('my_label', $field->getFormConfig()->getOption('label'));
    }

    public function testGetField(): void
    {
        $searchFormBuilder = $this->createSearchFormBuilder()
            ->addField('property', TextType::class);
        $this->assertInstanceOf(FormBuilderInterface::class, $searchFormBuilder->getField('property'));

        $searchFormBuilder->createForm();
        $this->assertInstanceOf(FormInterface::class, $searchFormBuilder->getField('property'));

        $searchFormBuilder->createFormView();
        $this->assertInstanceOf(FormView::class, $searchFormBuilder->getField('property'));
    }

    public function testCreateFormColumnNotExists(): void
    {
        $searchFormBuilder = $this->createSearchFormBuilder()
            ->addFilter('column1', 'my_filter');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The column "column1" does not exist');

        $searchFormBuilder->createForm();
    }

    public function testCreateForm(): void
    {
        $defaultData = $this->createMock(SearcherInterface::class);
        $defaultData->expects($this->once())->method('buildForm');

        $searchFormBuilder = $this->createSearchFormBuilder(defaultData: $defaultData);
        $this->assertSame($searchFormBuilder, $searchFormBuilder->createForm());
    }

    public function testCreateFormWithDefaultColumnId(): void
    {
        $filter = $this->createMock(FilterInterface::class);
        $filter->expects($this->exactly(2))->method('buildForm')->withConsecutive(
            [self::callback(fn ($value): bool => $value instanceof SearchFormBuilder), 'column1', self::callback(fn ($value): bool => \is_array($value))],
            [self::callback(fn ($value): bool => $value instanceof SearchFormBuilder), 'virtual1', self::callback(fn ($value): bool => \is_array($value))],
        );
        $crudConfig = $this->createValidCrudConfig()
            ->addColumn(['id' => 'column1', 'alias' => 'alias1'])
            ->addVirtualColumn(['id' => 'virtual1', 'alias' => 'alias1']);
        $crud = $this->createCrud($crudConfig);
        $this->createSearchFormBuilder(filters: ['my_filter' => $filter], crud: $crud)
            ->addFilter('column1', 'my_filter')
            ->addFilter('virtual1', 'my_filter')
            ->createForm();
    }

    public function testCreateFormWithColumnIdOption(): void
    {
        $filter = $this->createMock(FilterInterface::class);
        $filter->expects($this->exactly(2))->method('buildForm')->withConsecutive(
            [self::callback(fn ($value): bool => $value instanceof SearchFormBuilder), 'property1', self::callback(fn ($value): bool => \is_array($value))],
            [self::callback(fn ($value): bool => $value instanceof SearchFormBuilder), 'property2', self::callback(fn ($value): bool => \is_array($value))],
        );
        $crudConfig = $this->createValidCrudConfig()
            ->addColumn(['id' => 'column1', 'alias' => 'alias1'])
            ->addVirtualColumn(['id' => 'virtual1', 'alias' => 'alias1']);
        $crud = $this->createCrud($crudConfig);
        $this->createSearchFormBuilder(filters: ['my_filter' => $filter], crud: $crud)
            ->addFilter('property1', 'my_filter', [
                'column_id' => 'column1',
            ])
            ->addFilter('property2', 'my_filter', [
                'column_id' => 'virtual1',
            ])
            ->createForm();
    }

    public function testActionAfterCreateForm(): void
    {
        $searchFormBuilder = $this->createSearchFormBuilder();
        $searchFormBuilder->createForm();
        $this->assertSame('/user/ajax-crud?param1=val1&search=1', $searchFormBuilder->getForm()->getConfig()->getAction());
    }

    public function testCreateFormView(): void
    {
        $searchFormBuilder = $this->createSearchFormBuilder()->createForm();
        $this->assertSame($searchFormBuilder, $searchFormBuilder->createFormView());
    }

    public function testGetFormMethods(): void
    {
        $searchFormBuilder = $this->createSearchFormBuilder();
        $this->assertInstanceOf(FormBuilderInterface::class, $searchFormBuilder->getFormBuilder());

        $searchFormBuilder->createForm();
        $this->assertInstanceOf(FormInterface::class, $searchFormBuilder->getForm());

        $searchFormBuilder->createFormView();
        $this->assertInstanceOf(FormView::class, $searchFormBuilder->getFormView());
    }

    public function testGetFormBuilderAfterCreateForm(): void
    {
        $searchFormBuilder = $this->createSearchFormBuilder();
        $searchFormBuilder->createForm();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The object "FormBuilderInterface" no longer exists since the call of the method "SearchFormBuilder::createForm"');
        $searchFormBuilder->getFormBuilder();
    }

    public function testGetFormBeforeCreateForm(): void
    {
        $searchFormBuilder = $this->createSearchFormBuilder();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The object "FormInterface" exists only after the call of the method "SearchFormBuilder::createForm"');
        $searchFormBuilder->getForm();
    }

    public function testGetFormAfterCreateFormView(): void
    {
        $searchFormBuilder = $this->createSearchFormBuilder();
        $searchFormBuilder->createForm();
        $searchFormBuilder->createFormView();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The object "FormInterface" no longer exists since the call of the method "SearchFormBuilder::createFormView"');
        $searchFormBuilder->getForm();
    }

    public function testGetFormViewBeforeCreateForm(): void
    {
        $searchFormBuilder = $this->createSearchFormBuilder();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The object "FormView" exists only after the call of the method "SearchFormBuilder::createFormView".');
        $searchFormBuilder->getFormView();
    }

    public function testGetFormViewBeforeCreateFormView(): void
    {
        $searchFormBuilder = $this->createSearchFormBuilder();
        $searchFormBuilder->createForm();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The object "FormView" exists only after the call of the method "SearchFormBuilder::createFormView".');
        $searchFormBuilder->getFormView();
    }

    public function testGetDefaultData(): void
    {
        $defaultData = $this->createMock(SearcherInterface::class);
        $searchFormBuilder = $this->createSearchFormBuilder(defaultData: $defaultData);
        $this->assertSame($defaultData, $searchFormBuilder->getDefaultData());
    }

    public function testSetDataWithFormBuilder(): void
    {
        $data = $this->createMock(SearcherInterface::class);
        $searchFormBuilder = $this->createSearchFormBuilder();
        $searchFormBuilder->setData($data);

        $this->assertSame($data, $searchFormBuilder->getFormBuilder()->getData());
    }

    public function testSetDataWithForm(): void
    {
        $data = $this->createMock(SearcherInterface::class);
        $searchFormBuilder = $this->createSearchFormBuilder();
        $searchFormBuilder->createForm();
        $searchFormBuilder->setData($data);

        $this->assertSame($data, $searchFormBuilder->getForm()->getData());
    }

    public function testSetDataWithFormView(): void
    {
        $data = $this->createMock(SearcherInterface::class);
        $searchFormBuilder = $this->createSearchFormBuilder();
        $searchFormBuilder->createForm();
        $searchFormBuilder->createFormView();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The method "SearchFormBuilder::setData" cannot be called after "SearchFormBuilder::createFormView"');
        $searchFormBuilder->setData($data);
    }

    public function testUpdateQueryBuilder(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $filter = $this->createMock(FilterInterface::class);
        $filter->expects($this->once())->method('updateQueryBuilder')->with(
            self::equalTo($queryBuilder),
            'property',
            'value',
            self::callback(fn ($value): bool => \is_array($value))
        );
        $filter->expects($this->once())->method('supportsQueryBuilder')->with($queryBuilder)->willReturn(true);

        $crudConfig = $this->createValidCrudConfig()
            ->addColumn(['id' => 'column1', 'alias' => 'alias1']);
        $crud = $this->createCrud($crudConfig);
        $searchFormBuilder = $this->createSearchFormBuilder(filters: ['my_filter' => $filter], crud: $crud)
            ->addFilter('property', 'my_filter', [
                'column_id' => 'column1',
            ]);

        $searcherData = $this->getMockBuilder(SearcherInterface::class)
            ->addMethods(['getProperty'])
            ->onlyMethods(['buildForm', 'updateQueryBuilder', 'configureOptions'])
            ->getMock();
        $searcherData->expects($this->once())->method('getProperty')->willReturn('value');
        $this->assertSame($searchFormBuilder, $searchFormBuilder->updateQueryBuilder($queryBuilder, $searcherData));
    }

    public function testUpdateQueryBuilderWithUpdateQueryBuilderOption(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $filter = $this->createMock(FilterInterface::class);
        $filter->expects($this->never())->method('updateQueryBuilder');
        $filter->expects($this->never())->method('supportsQueryBuilder');

        $callback = $this->getMockBuilder(\stdClass::class)->addMethods(['getCallback'])->getMock();
        $callback->expects($this->once())->method('getCallback')->with(
            self::equalTo($queryBuilder),
            'property',
            'value',
            self::callback(fn ($value): bool => \is_array($value))
        );

        $crudConfig = $this->createValidCrudConfig()
            ->addColumn(['id' => 'column1', 'alias' => 'alias1']);
        $crud = $this->createCrud($crudConfig);
        $searchFormBuilder = $this->createSearchFormBuilder(filters: ['my_filter' => $filter], crud: $crud)
            ->addFilter('property', 'my_filter', [
                'column_id' => 'column1',
                'update_query_builder' => function ($queryBuilder, $property, $value, $options) use ($callback): void {
                    $callback->getCallback($queryBuilder, $property, $value, $options);
                },
            ]);

        $searcherData = $this->getMockBuilder(SearcherInterface::class)
            ->addMethods(['getProperty'])
            ->onlyMethods(['buildForm', 'updateQueryBuilder', 'configureOptions'])
            ->getMock();
        $searcherData->expects($this->once())->method('getProperty')->willReturn('value');
        $searchFormBuilder->updateQueryBuilder($queryBuilder, $searcherData);
    }

    public function testUpdateQueryBuilderNotSuported(): void
    {
        $queryBuilder = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        $filter = $this->createMock(FilterInterface::class);
        $filter->expects($this->never())->method('updateQueryBuilder');
        $filter->expects($this->once())->method('supportsQueryBuilder')->with($queryBuilder)->willReturn(false);

        $crudConfig = $this->createValidCrudConfig()
            ->addColumn(['id' => 'column1', 'alias' => 'alias1']);
        $crud = $this->createCrud($crudConfig);
        $searchFormBuilder = $this->createSearchFormBuilder(filters: ['my_filter' => $filter], crud: $crud)
            ->addFilter('property', 'my_filter', [
                'column_id' => 'column1',
            ]);

        $searcherData = $this->getMockBuilder(SearcherInterface::class)
            ->addMethods(['getProperty'])
            ->onlyMethods(['buildForm', 'updateQueryBuilder', 'configureOptions'])
            ->getMock();
        $searcherData->expects($this->once())->method('getProperty')->willReturn('value');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The filter "my_filter" does not support "Doctrine\ORM\QueryBuilder" query builder');

        $searchFormBuilder->updateQueryBuilder($queryBuilder, $searcherData);
    }

    protected function createCrudContainer(array $filters = []): ContainerInterface
    {
        $crudFilters = $this->createMock(ContainerInterface::class);
        $crudFilters->method('has')->willReturnCallback(fn (string $id) => \array_key_exists($id, $filters));
        $crudFilters->method('get')->willReturnCallback(fn (string $id) => $filters[$id]);

        $services = [
            'form.factory' => self::getContainer()->get('form.factory'),
            'ecommit_crud.filters' => $crudFilters,
        ];
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(fn (string $id) => \array_key_exists($id, $services));
        $container->method('get')->willReturnCallback(fn (string $id) => $services[$id]);

        return $container;
    }

    protected function createSearchFormBuilder(array $filters = null, Crud $crud = null, SearcherInterface $defaultData = null, string $type = null, array $options = []): SearchFormBuilder
    {
        $filters = (null !== $filters) ? $filters : ['my_filter' => $this->createMock(FilterInterface::class)];
        $crud = ($crud) ?: $this->createCrud($this->createValidCrudConfig());
        $defaultData = ($defaultData) ?: $this->createMock(SearcherInterface::class);

        return new SearchFormBuilder($this->createCrudContainer($filters), $crud, $defaultData, $type, $options);
    }

    protected function getPrivateValue(SearchFormBuilder $searchFormBuilder, string $property): mixed
    {
        $reflectionProperty = (new \ReflectionClass($searchFormBuilder))->getProperty($property);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($searchFormBuilder);
    }
}

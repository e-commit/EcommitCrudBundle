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

use Ecommit\CrudBundle\Form\Filter\BooleanFilter;

class BooleanFilterTest extends AbstractFilterTest
{
    /**
     * @dataProvider getTestViewAndQueryBuilderProvider
     */
    public function testViewAndQueryBuilder($modelData, $expectedViewData, ?string $whereExpected, array $parametersExpected): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.name', $modelData));
        $crud->getSearchForm()->addFilter('propertyName', BooleanFilter::class);
        $view = $this->createFormAndGetFormView($crud);

        $this->assertSame($expectedViewData, $view->children['propertyName']->vars['value']);

        $this->checkQueryBuilder($crud, $whereExpected, $parametersExpected);
    }

    public function getTestViewAndQueryBuilderProvider(): array
    {
        return [
            [null, '', null, []],
            ['', '', null, []],
            ['T', 'T', 'e.name = :value_boolean_true_propertyName', ['value_boolean_true_propertyName' => 1]],
            ['F', 'F', 'e.name = :value_boolean_false_propertyName OR e.name IS NULL', ['value_boolean_false_propertyName' => 0]],
        ];
    }

    public function testInvalidInput(): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.name', 'bad-value'));
        $crud->getSearchForm()->addFilter('propertyName', BooleanFilter::class);

        $this->checkQueryBuilder($crud, null, []);
    }

    /**
     * @dataProvider getTestValueTrueOptionProvider
     */
    public function testValueTrueOption($modelData, ?string $whereExpected, array $parametersExpected): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.name', $modelData));
        $crud->getSearchForm()->addFilter('propertyName', BooleanFilter::class, [
            'value_true' => 'val',
        ]);

        $this->checkQueryBuilder($crud, $whereExpected, $parametersExpected);
    }

    public function getTestValueTrueOptionProvider(): array
    {
        return [
            [null, null, []],
            ['', null, []],
            ['T', 'e.name = :value_boolean_true_propertyName', ['value_boolean_true_propertyName' => 'val']],
            ['F', 'e.name = :value_boolean_false_propertyName OR e.name IS NULL', ['value_boolean_false_propertyName' => 0]],
        ];
    }

    /**
     * @dataProvider getTestValueFalseOptionProvider
     */
    public function testValueFalseOption($modelData, ?string $whereExpected, array $parametersExpected): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.name', $modelData));
        $crud->getSearchForm()->addFilter('propertyName', BooleanFilter::class, [
            'value_false' => 'val',
        ]);

        $this->checkQueryBuilder($crud, $whereExpected, $parametersExpected);
    }

    public function getTestValueFalseOptionProvider(): array
    {
        return [
            [null, null, []],
            ['', null, []],
            ['T', 'e.name = :value_boolean_true_propertyName', ['value_boolean_true_propertyName' => 1]],
            ['F', 'e.name = :value_boolean_false_propertyName OR e.name IS NULL', ['value_boolean_false_propertyName' => 'val']],
        ];
    }

    /**
     * @dataProvider getTestValueFalseNullOptionProvider
     */
    public function testValueFalseNullOption($modelData, ?string $whereExpected, array $parametersExpected): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.name', $modelData));
        $crud->getSearchForm()->addFilter('propertyName', BooleanFilter::class, [
            'value_false' => null,
        ]);

        $this->checkQueryBuilder($crud, $whereExpected, $parametersExpected);
    }

    public function getTestValueFalseNullOptionProvider(): array
    {
        return [
            [null, null, []],
            ['', null, []],
            ['T', 'e.name = :value_boolean_true_propertyName', ['value_boolean_true_propertyName' => 1]],
            ['F', 'e.name IS NULL', []],
        ];
    }

    /**
     * @dataProvider getTestNotNullIsTrueOptionProvider
     */
    public function testNotNullIsTrueOption($notNullIsTrue, $modelData, ?string $whereExpected, array $parametersExpected): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.name', $modelData));
        $crud->getSearchForm()->addFilter('propertyName', BooleanFilter::class, [
            'not_null_is_true' => $notNullIsTrue,
        ]);

        $this->checkQueryBuilder($crud, $whereExpected, $parametersExpected);
    }

    public function getTestNotNullIsTrueOptionProvider(): array
    {
        return [
            [false, null, null, []],
            [false, '', null, []],
            [false, 'T', 'e.name = :value_boolean_true_propertyName', ['value_boolean_true_propertyName' => 1]],
            [false, 'F', 'e.name = :value_boolean_false_propertyName OR e.name IS NULL', ['value_boolean_false_propertyName' => 0]],

            [true, null, null, []],
            [true, '', null, []],
            [true, 'T', 'e.name = :value_boolean_true_propertyName OR (e.name IS NOT NULL AND e.name != :value_boolean_false_propertyName)', ['value_boolean_true_propertyName' => 1, 'value_boolean_false_propertyName' => 0]],
            [true, 'F', 'e.name = :value_boolean_false_propertyName OR e.name IS NULL', ['value_boolean_false_propertyName' => 0]],
        ];
    }

    /**
     * @dataProvider getTestNullIsFalseOptionProvider
     */
    public function testNullIsFalseOption($nullIsFalse, $modelData, ?string $whereExpected, array $parametersExpected): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.name', $modelData));
        $crud->getSearchForm()->addFilter('propertyName', BooleanFilter::class, [
            'null_is_false' => $nullIsFalse,
        ]);

        $this->checkQueryBuilder($crud, $whereExpected, $parametersExpected);
    }

    public function getTestNullIsFalseOptionProvider(): array
    {
        return [
            [true, null, null, []],
            [true, '', null, []],
            [true, 'T', 'e.name = :value_boolean_true_propertyName', ['value_boolean_true_propertyName' => 1]],
            [true, 'F', 'e.name = :value_boolean_false_propertyName OR e.name IS NULL', ['value_boolean_false_propertyName' => 0]],

            [false, null, null, []],
            [false, '', null, []],
            [false, 'T', 'e.name = :value_boolean_true_propertyName', ['value_boolean_true_propertyName' => 1]],
            [false, 'F', 'e.name = :value_boolean_false_propertyName', ['value_boolean_false_propertyName' => 0]],
        ];
    }

    /**
     * @dataProvider getTestSubmitProvider
     */
    public function testSubmit($submittedData, $expectedModelData, $expectedViewData): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.name'));
        $crud->getSearchForm()->addFilter('propertyName', BooleanFilter::class);

        $form = $this->createAndGetForm($crud);
        $form->submit([
            'propertyName' => $submittedData,
        ]);

        $field = $form->get('propertyName');
        $this->assertTrue($field->isSynchronized());
        $this->assertTrue($field->isValid());
        $this->assertSame($expectedModelData, $field->getData());
        $this->assertSame($expectedViewData, $field->getViewData());
    }

    public function getTestSubmitProvider(): array
    {
        return [
            [null, null, ''],
            ['', null, ''],
            ['T', 'T', 'T'],
            ['F', 'F', 'F'],
        ];
    }

    public function testSubmitInvalidFormat(): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.name'));
        $crud->getSearchForm()->addFilter('propertyName', BooleanFilter::class);

        $form = $this->createAndGetForm($crud);
        $form->submit([
            'propertyName' => ['not-scalar'],
        ]);

        $field = $form->get('propertyName');
        $this->assertFalse($field->isSynchronized());
        $this->assertFalse($field->isValid());
        $this->assertNull($field->getData());
    }

    public function testSubmitBadChoice(): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.name'));
        $crud->getSearchForm()->addFilter('propertyName', BooleanFilter::class);

        $form = $this->createAndGetForm($crud);
        $form->submit([
            'propertyName' => 'bad-value',
        ]);

        $field = $form->get('propertyName');
        $this->assertFalse($field->isSynchronized());
        $this->assertFalse($field->isValid());
        $this->assertNull($field->getData());
    }
}

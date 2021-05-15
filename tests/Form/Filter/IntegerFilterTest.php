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

use Ecommit\CrudBundle\Form\Filter\IntegerFilter;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class IntegerFilterTest extends AbstractFilterTest
{
    public const TEST_FILTER = IntegerFilter::class;

    public function testOptionsAreRequired(): void
    {
        $this->expectException(MissingOptionsException::class);

        $crud = $this->createCrud('e.name');
        $crud->getSearchForm()->addFilter('propertyName', static::TEST_FILTER);
    }

    /**
     * @dataProvider getTestViewAndQueryBuilderProvider
     */
    public function testViewAndQueryBuilder($modelData, string $comparator, $expectedViewData, ?string $whereExpected, array $parametersExpected): void
    {
        $crud = $this->createCrud('e.name', $modelData);
        $crud->getSearchForm()->addFilter('propertyName', static::TEST_FILTER, [
            'comparator' => $comparator,
        ]);
        $view = $this->initCrudAndGetFormView($crud);

        $this->assertSame($expectedViewData, $view->children['propertyName']->vars['value']);

        $this->checkQueryBuilder($crud, $whereExpected, $parametersExpected);
    }

    public function getTestViewAndQueryBuilderProvider(): array
    {
        return [
            //Null value
            [null, IntegerFilter::GREATER_THAN, '', null, []],
            [null, IntegerFilter::GREATER_EQUAL, '', null, []],
            [null, IntegerFilter::SMALLER_THAN, '', null, []],
            [null, IntegerFilter::SMALLER_EQUAL, '', null, []],
            [null, IntegerFilter::EQUAL, '', null, []],

            //String value
            ['5', IntegerFilter::GREATER_THAN, '5', 'e.name > :value_integer_propertyName', ['value_integer_propertyName' => '5']],
            ['5', IntegerFilter::GREATER_EQUAL, '5', 'e.name >= :value_integer_propertyName', ['value_integer_propertyName' => '5']],
            ['5', IntegerFilter::SMALLER_THAN, '5', 'e.name < :value_integer_propertyName', ['value_integer_propertyName' => '5']],
            ['5', IntegerFilter::SMALLER_EQUAL, '5', 'e.name <= :value_integer_propertyName', ['value_integer_propertyName' => '5']],
            ['5', IntegerFilter::EQUAL, '5', 'e.name = :value_integer_propertyName', ['value_integer_propertyName' => '5']],

            //Int value
            [5, IntegerFilter::GREATER_THAN, '5', 'e.name > :value_integer_propertyName', ['value_integer_propertyName' => '5']],
            [5, IntegerFilter::GREATER_EQUAL, '5', 'e.name >= :value_integer_propertyName', ['value_integer_propertyName' => '5']],
            [5, IntegerFilter::SMALLER_THAN, '5', 'e.name < :value_integer_propertyName', ['value_integer_propertyName' => '5']],
            [5, IntegerFilter::SMALLER_EQUAL, '5', 'e.name <= :value_integer_propertyName', ['value_integer_propertyName' => '5']],
            [5, IntegerFilter::EQUAL, '5', 'e.name = :value_integer_propertyName', ['value_integer_propertyName' => '5']],
        ];
    }

    public function testInvalidInput(): void
    {
        $crud = $this->createCrud('e.name', ['not-scalar']);
        $crud->getSearchForm()->addFilter('propertyName', static::TEST_FILTER, [
            'comparator' => IntegerFilter::GREATER_THAN,
        ]);

        $crud->init();

        $this->checkQueryBuilder($crud, null, []);
    }

    /**
     * @dataProvider getTestSubmitProvider
     */
    public function testSubmit($submittedData, $expectedModelData, $expectedViewData): void
    {
        $crud = $this->createCrud('e.name');
        $crud->getSearchForm()->addFilter('propertyName', static::TEST_FILTER, [
            'comparator' => IntegerFilter::GREATER_THAN,
        ]);

        $form = $this->initCrudAndGetForm($crud);
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
            ['5', 5, '5'],
        ];
    }

    /**
     * @dataProvider getTestSubmitInvalidFormatProvider
     */
    public function testSubmitInvalidFormat($submittedData): void
    {
        $crud = $this->createCrud('e.name');
        $crud->getSearchForm()->addFilter('propertyName', static::TEST_FILTER, [
            'comparator' => IntegerFilter::GREATER_THAN,
        ]);

        $form = $this->initCrudAndGetForm($crud);
        $form->submit([
            'propertyName' => $submittedData,
        ]);

        $field = $form->get('propertyName');
        $this->assertFalse($field->isSynchronized());
        $this->assertFalse($field->isValid());
        $this->assertNull($field->getData());
    }

    public function getTestSubmitInvalidFormatProvider(): array
    {
        return [
            [['not-scalar']],
            ['aaa'],
        ];
    }
}

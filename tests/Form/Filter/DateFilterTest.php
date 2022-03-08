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

use Ecommit\CrudBundle\Form\Filter\DateFilter;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class DateFilterTest extends AbstractFilterTest
{
    public function testOptionsAreRequired(): void
    {
        $this->expectException(MissingOptionsException::class);

        $crud = $this->createCrud('e.createdAt');
        $crud->getSearchForm()->addFilter('propertyName', DateFilter::class);
    }

    /**
     * @dataProvider getTestViewAndQueryBuilderProvider
     */
    public function testViewAndQueryBuilder($modelData, bool $withTime, string $comparator, $expectedViewData, ?string $whereExpected, array $parametersExpected): void
    {
        $crud = $this->createCrud('e.createdAt', $modelData);
        $crud->getSearchForm()->addFilter('propertyName', DateFilter::class, [
            'comparator' => $comparator,
            'with_time' => $withTime,
        ]);
        $view = $this->initCrudAndGetFormView($crud);

        $this->assertSame($expectedViewData, $view->children['propertyName']->vars['value']);

        $this->checkQueryBuilder($crud, $whereExpected, $parametersExpected);
    }

    public function getTestViewAndQueryBuilderProvider(): array
    {
        $data = [
            // Null value
            [null, false, DateFilter::GREATER_THAN, ['year' => '', 'month' => '', 'day' => ''], null, []],
            [null, false, DateFilter::GREATER_EQUAL, ['year' => '', 'month' => '', 'day' => ''], null, []],
            [null, false, DateFilter::SMALLER_THAN, ['year' => '', 'month' => '', 'day' => ''], null, []],
            [null, false, DateFilter::SMALLER_EQUAL, ['year' => '', 'month' => '', 'day' => ''], null, []],
            [null, false, DateFilter::EQUAL, ['year' => '', 'month' => '', 'day' => ''], null, []],

            [null, true, DateFilter::GREATER_THAN, ['date' => ['year' => '', 'month' => '', 'day' => ''], 'time' => ['hour' => '', 'minute' => '']], null, []],
            [null, true, DateFilter::GREATER_EQUAL, ['date' => ['year' => '', 'month' => '', 'day' => ''], 'time' => ['hour' => '', 'minute' => '']], null, []],
            [null, true, DateFilter::SMALLER_THAN, ['date' => ['year' => '', 'month' => '', 'day' => ''], 'time' => ['hour' => '', 'minute' => '']], null, []],
            [null, true, DateFilter::SMALLER_EQUAL, ['date' => ['year' => '', 'month' => '', 'day' => ''], 'time' => ['hour' => '', 'minute' => '']], null, []],
            [null, true, DateFilter::EQUAL, ['date' => ['year' => '', 'month' => '', 'day' => ''], 'time' => ['hour' => '', 'minute' => '']], null, []],
        ];

        // Not empty value
        foreach ([new \DateTimeImmutable('2021-04-01 13:10:20'), new \DateTime('2021-04-01 13:10:20')] as $inputDate) {
            $data = array_merge($data, [
                [$inputDate, false, DateFilter::GREATER_THAN, ['year' => '2021', 'month' => '4', 'day' => '1'], 'e.createdAt > :value_date_propertyName', ['value_date_propertyName' => '2021-04-01 23:59:59']],
                [$inputDate, false, DateFilter::GREATER_EQUAL, ['year' => '2021', 'month' => '4', 'day' => '1'], 'e.createdAt >= :value_date_propertyName', ['value_date_propertyName' => '2021-04-01 00:00:00']],
                [$inputDate, false, DateFilter::SMALLER_THAN, ['year' => '2021', 'month' => '4', 'day' => '1'], 'e.createdAt < :value_date_propertyName', ['value_date_propertyName' => '2021-04-01 00:00:00']],
                [$inputDate, false, DateFilter::SMALLER_EQUAL, ['year' => '2021', 'month' => '4', 'day' => '1'], 'e.createdAt <= :value_date_propertyName', ['value_date_propertyName' => '2021-04-01 23:59:59']],
                [$inputDate, false, DateFilter::EQUAL, ['year' => '2021', 'month' => '4', 'day' => '1'], 'e.createdAt >= :value_date_inf_propertyName AND e.createdAt <= :value_date_sup_propertyName', ['value_date_inf_propertyName' => '2021-04-01 00:00:00', 'value_date_sup_propertyName' => '2021-04-01 23:59:59']],

                [$inputDate, true, DateFilter::GREATER_THAN, ['date' => ['year' => '2021', 'month' => '4', 'day' => '1'], 'time' => ['hour' => '13', 'minute' => '10']], 'e.createdAt > :value_date_propertyName', ['value_date_propertyName' => '2021-04-01 13:10:20']],
                [$inputDate, true, DateFilter::GREATER_EQUAL, ['date' => ['year' => '2021', 'month' => '4', 'day' => '1'], 'time' => ['hour' => '13', 'minute' => '10']], 'e.createdAt >= :value_date_propertyName', ['value_date_propertyName' => '2021-04-01 13:10:20']],
                [$inputDate, true, DateFilter::SMALLER_THAN, ['date' => ['year' => '2021', 'month' => '4', 'day' => '1'], 'time' => ['hour' => '13', 'minute' => '10']], 'e.createdAt < :value_date_propertyName', ['value_date_propertyName' => '2021-04-01 13:10:20']],
                [$inputDate, true, DateFilter::SMALLER_EQUAL, ['date' => ['year' => '2021', 'month' => '4', 'day' => '1'], 'time' => ['hour' => '13', 'minute' => '10']], 'e.createdAt <= :value_date_propertyName', ['value_date_propertyName' => '2021-04-01 13:10:20']],
                [$inputDate, true, DateFilter::EQUAL, ['date' => ['year' => '2021', 'month' => '4', 'day' => '1'], 'time' => ['hour' => '13', 'minute' => '10']], 'e.createdAt >= :value_date_inf_propertyName AND e.createdAt <= :value_date_sup_propertyName', ['value_date_inf_propertyName' => '2021-04-01 13:10:20', 'value_date_sup_propertyName' => '2021-04-01 13:10:20']],
            ]);
        }

        return $data;
    }

    /**
     * @dataProvider getTestInvalidInputProvider
     */
    public function testInvalidInput($modelData): void
    {
        $crud = $this->createCrud('e.name', $modelData);
        $crud->getSearchForm()->addFilter('propertyName', DateFilter::class, [
            'comparator' => DateFilter::GREATER_THAN,
        ]);

        $crud->init();

        $this->checkQueryBuilder($crud, null, []);
    }

    public function getTestInvalidInputProvider(): array
    {
        return [
            [['not-scalar']],
            ['not-date'],
        ];
    }

    /**
     * @dataProvider getTestSubmitProvider
     */
    public function testSubmit($withTime, $submittedData, $expectedModelData, $expectedViewData): void
    {
        $crud = $this->createCrud('e.name');
        $crud->getSearchForm()->addFilter('propertyName', DateFilter::class, [
            'comparator' => DateFilter::GREATER_THAN,
            'with_time' => $withTime,
        ]);

        $form = $this->initCrudAndGetForm($crud);
        $form->submit([
            'propertyName' => $submittedData,
        ]);

        $field = $form->get('propertyName');
        $this->assertTrue($field->isSynchronized());
        $this->assertTrue($field->isValid());
        $this->assertEquals($expectedModelData, $field->getData());
        $this->assertSame($expectedViewData, $field->getViewData());
    }

    public function getTestSubmitProvider(): array
    {
        return [
            // Without time
            [false, null, null, ['year' => '', 'month' => '', 'day' => '']],
            [false, ['year' => '', 'month' => '', 'day' => ''], null, ['year' => '', 'month' => '', 'day' => '']],
            [false, ['year' => '2021', 'month' => '4', 'day' => '1'], new \DateTime('2021-04-01 00:00:00'), ['year' => '2021', 'month' => '4', 'day' => '1']],

            // With time
            [true, null, null, ['date' => ['year' => '', 'month' => '', 'day' => ''], 'time' => ['hour' => '', 'minute' => '']]],
            [true, ['date' => ['year' => '', 'month' => '', 'day' => ''], 'time' => ['hour' => '', 'minute' => '']], null, ['date' => ['year' => '', 'month' => '', 'day' => ''], 'time' => ['hour' => '', 'minute' => '']]],
            [true, ['date' => ['year' => '2021', 'month' => '4', 'day' => '1'], 'time' => ['hour' => '13', 'minute' => '10']], new \DateTime('2021-04-01 13:10:00'), ['date' => ['year' => '2021', 'month' => '4', 'day' => '1'], 'time' => ['hour' => '13', 'minute' => '10']]],
        ];
    }

    /**
     * @dataProvider getTestSubmitInvalidFormatProvider
     */
    public function testSubmitInvalidFormat($submittedData): void
    {
        $crud = $this->createCrud('e.name');
        $crud->getSearchForm()->addFilter('propertyName', DateFilter::class, [
            'comparator' => DateFilter::GREATER_THAN,
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
            ['bad-value'],
            [['year' => '2021', 'month' => '2', 'day' => '31']], // the date does not exist
        ];
    }

    /**
     * @dataProvider getTestViewAndQueryBuilderWithSingleTextWidgetProvider
     */
    public function testViewAndQueryBuilderWithSingleTextWidget($modelData, bool $withTime, string $comparator, $expectedViewData, ?string $whereExpected, array $parametersExpected): void
    {
        $crud = $this->createCrud('e.createdAt', $modelData);
        $crud->getSearchForm()->addFilter('propertyName', DateFilter::class, [
            'comparator' => $comparator,
            'with_time' => $withTime,
            'type_options' => [
                'widget' => 'single_text',
                'format' => ($withTime) ? 'dd/MM/yyyy HH:mm' : 'dd/MM/yyyy',
                'html5' => false,
            ],
        ]);
        $view = $this->initCrudAndGetFormView($crud);

        $this->assertSame($expectedViewData, $view->children['propertyName']->vars['value']);

        $this->checkQueryBuilder($crud, $whereExpected, $parametersExpected);
    }

    public function getTestViewAndQueryBuilderWithSingleTextWidgetProvider(): array
    {
        $inputValue = new \DateTime('2021-04-01 13:10:20');

        return [
            // Null value
            [null, false, DateFilter::GREATER_THAN, '', null, []],
            [null, true, DateFilter::GREATER_THAN, '', null, []],

            // Not empty value
            [$inputValue, false, DateFilter::GREATER_THAN, '01/04/2021', 'e.createdAt > :value_date_propertyName', ['value_date_propertyName' => '2021-04-01 23:59:59']],
            [$inputValue, true, DateFilter::GREATER_THAN, '01/04/2021 13:10', 'e.createdAt > :value_date_propertyName', ['value_date_propertyName' => '2021-04-01 13:10:20']],
        ];
    }

    /**
     * @dataProvider getTestSubmitWithSingleTextWidgetProvider
     */
    public function testSubmitWithSingleTextWidget($withTime, $submittedData, $expectedModelData, $expectedViewData): void
    {
        $crud = $this->createCrud('e.name');
        $crud->getSearchForm()->addFilter('propertyName', DateFilter::class, [
            'comparator' => DateFilter::GREATER_THAN,
            'with_time' => $withTime,
            'type_options' => [
                'widget' => 'single_text',
                'format' => ($withTime) ? 'dd/MM/yyyy HH:mm' : 'dd/MM/yyyy',
                'html5' => false,
            ],
        ]);

        $form = $this->initCrudAndGetForm($crud);
        $form->submit([
            'propertyName' => $submittedData,
        ]);

        $field = $form->get('propertyName');
        $this->assertTrue($field->isSynchronized());
        $this->assertTrue($field->isValid());
        $this->assertEquals($expectedModelData, $field->getData());
        $this->assertSame($expectedViewData, $field->getViewData());
    }

    public function getTestSubmitWithSingleTextWidgetProvider(): array
    {
        return [
            // Without time
            [false, null, null, ''],
            [false, '', null, ''],
            [false, '01/04/2021', new \DateTime('2021-04-01 00:00:00'), '01/04/2021'],

            // With time
            [true, null, null, ''],
            [true, '', null, ''],
            [true, '01/04/2021 13:10', new \DateTime('2021-04-01 13:10:00'), '01/04/2021 13:10'],
        ];
    }
}

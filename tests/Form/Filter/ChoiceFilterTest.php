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

use Ecommit\CrudBundle\Form\Filter\ChoiceFilter;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;

class ChoiceFilterTest extends AbstractFilterTest
{
    /**
     * @dataProvider getTestViewAndQueryBuilderProvider
     */
    public function testViewAndQueryBuilder($modelData, bool $multiple, $expectedViewData, ?string $whereExpected, array $parametersExpected): void
    {
        $crud = $this->createCrud('e.tag', $modelData);
        $crud->getSearchForm()->addFilter('propertyName', ChoiceFilter::class, [
            'choices' => ['val1' => '1', 'val2' => '2'],
            'multiple' => $multiple,
        ]);
        $view = $this->initCrudAndGetFormView($crud);

        $this->assertEquals($expectedViewData, $view->children['propertyName']->vars['value']);

        $this->checkQueryBuilder($crud, $whereExpected, $parametersExpected);
    }

    public function getTestViewAndQueryBuilderProvider(): array
    {
        return [
            // No multiple
            [null, false, '', null, []],
            ['', false, '', null, []],
            ['2', false, '2', 'e.tag = :value_collectionpropertyName', ['value_collectionpropertyName' => '2']],
            ['5', false, '', 'e.tag = :value_collectionpropertyName', ['value_collectionpropertyName' => '5']], // Not exists

            // Multiple
            [null, true, [], null, []],
            [[], true, [], null, []],
            [['2'], true, ['2'], 'e.tag IN (:value_collectionpropertyName)', ['value_collectionpropertyName' => ['2']]],
            [['5'], true, [], 'e.tag IN (:value_collectionpropertyName)', ['value_collectionpropertyName' => ['5']]], // Not exists
        ];
    }

    public function testInvalidMin(): void
    {
        $crud = $this->createCrud('e.tag', ['1', '2']);
        $crud->getSearchForm()->addFilter('propertyName', ChoiceFilter::class, [
            'choices' => ['val1' => '1', 'val2' => '2'],
            'multiple' => true,
            'min' => 3,
        ]);
        $crud->init();

        $this->checkQueryBuilder($crud, null, []);
    }

    public function testInvalidMax(): void
    {
        $crud = $this->createCrud('e.tag', ['1', '2']);
        $crud->getSearchForm()->addFilter('propertyName', ChoiceFilter::class, [
            'choices' => ['val1' => '1', 'val2' => '2'],
            'multiple' => true,
            'max' => 1,
        ]);
        $crud->init();

        $this->checkQueryBuilder($crud, null, []);
    }

    /**
     * @dataProvider getTestSubmitProvider
     */
    public function testSubmit($submittedData, bool $multiple, $expectedModelData, $expectedViewData): void
    {
        $crud = $this->createCrud('e.name');
        $crud->getSearchForm()->addFilter('propertyName', ChoiceFilter::class, [
            'choices' => ['val1' => '1', 'val2' => '2'],
            'multiple' => $multiple,
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
            // No multiple
            [null, false, null, ''],
            ['', false, null, ''],
            ['1', false, '1', '1'],

            // Multiple
            [null, true, [], []],
            [[], true, [], []],
            [['1'], true, ['1'], ['1']],
        ];
    }

    /**
     * @dataProvider getTestSubmitInvalidFormatProvider
     */
    public function testSubmitInvalidFormat($submittedData, bool $multiple): void
    {
        $crud = $this->createCrud('e.name');
        $crud->getSearchForm()->addFilter('propertyName', ChoiceFilter::class, [
            'choices' => ['val1' => '1', 'val2' => '2'],
            'multiple' => $multiple,
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
            // No multiple
            [['not-scalar'], false],

            // Multiple
            ['not-array', true],
        ];
    }

    public function testSubmitInvalidMinLength(): void
    {
        $crud = $this->createCrud('e.name');
        $crud->getSearchForm()->addFilter('propertyName', ChoiceFilter::class, [
            'choices' => ['val1' => '1', 'val2' => '2'],
            'multiple' => true,
            'min' => 3,
        ]);

        $form = $this->initCrudAndGetForm($crud);
        $form->submit([
            'propertyName' => ['1'],
        ]);

        $field = $form->get('propertyName');
        $this->assertTrue($field->isSynchronized());
        $this->assertFalse($field->isValid());
        $this->assertSame(['1'], $field->getData());
    }

    public function testSubmitInvalidMaxLength(): void
    {
        $crud = $this->createCrud('e.name');
        $crud->getSearchForm()->addFilter('propertyName', ChoiceFilter::class, [
            'choices' => ['val1' => '1', 'val2' => '2'],
            'multiple' => true,
            'max' => 1,
        ]);

        $form = $this->initCrudAndGetForm($crud);
        $form->submit([
            'propertyName' => ['1', '2'],
        ]);

        $field = $form->get('propertyName');
        $this->assertTrue($field->isSynchronized());
        $this->assertFalse($field->isValid());
        $this->assertSame(['1', '2'], $field->getData());
    }

    public function testSubmitWithoutValidation(): void
    {
        $crud = $this->createCrud('e.name');
        $crud->getSearchForm()->addFilter('propertyName', ChoiceFilter::class, [
            'choices' => ['val1' => '1', 'val2' => '2'],
            'multiple' => true,
            'min' => 3,
            'autovalidate' => false,
        ]);

        $form = $this->initCrudAndGetForm($crud);
        $form->submit([
            'propertyName' => ['1'],
        ]);

        $field = $form->get('propertyName');
        $this->assertTrue($field->isSynchronized());
        $this->assertTrue($field->isValid());
        $this->assertSame(['1'], $field->getData());
        $this->assertSame(['1'], $field->getViewData());
    }

    public function testWithType(): void
    {
        $crud = $this->createCrud('e.name');
        $crud->getSearchForm()->addFilter('propertyName', ChoiceFilter::class, [
            'type' => LanguageType::class,
        ]);

        $view = $this->initCrudAndGetFormView($crud);

        $field = $view['propertyName'];
        $this->assertContains('language', $field->vars['block_prefixes']);
        $this->assertGreaterThan(0, \count($field->vars['choices']));
    }
}

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

use Ecommit\CrudBundle\Form\Filter\TextFilter;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TextFilterTest extends AbstractFilterTest
{
    /**
     * @dataProvider getTestViewAndQueryBuilderProvider
     */
    public function testViewAndQueryBuilder($modelData, bool $mustBegin, bool $mustEnd, $expectedViewData, ?string $whereExpected, array $parametersExpected): void
    {
        $crud = $this->createCrud('e.name', $modelData);
        $crud->getSearchForm()->addFilter('propertyName', TextFilter::class, [
            'must_begin' => $mustBegin,
            'must_end' => $mustEnd,
        ]);
        $view = $this->initCrudAndGetFormView($crud);

        $this->assertSame($expectedViewData, $view->children['propertyName']->vars['value']);

        $this->checkQueryBuilder($crud, $whereExpected, $parametersExpected);
    }

    public function getTestViewAndQueryBuilderProvider(): array
    {
        return [
            // Null value
            [null, false, false, '', null, []],
            [null, false, true, '', null, []],
            [null, true, false, '', null, []],
            [null, true, true, '', null, []],

            // Empty value
            ['', false, false, '', null, []],
            ['', false, true, '', null, []],
            ['', true, false, '', null, []],
            ['', true, true, '', null, []],

            // Not empty value
            ['val', false, false, 'val', 'e.name LIKE :value_text_propertyName', ['value_text_propertyName' => '%val%']],
            ['val', false, true, 'val', 'e.name LIKE :value_text_propertyName', ['value_text_propertyName' => '%val']],
            ['val', true, false, 'val', 'e.name LIKE :value_text_propertyName', ['value_text_propertyName' => 'val%']],
            ['val', true, true, 'val', 'e.name = :value_text_propertyName', ['value_text_propertyName' => 'val']],

            // Escape
            ['v%a_l', false, false, 'v%a_l', 'e.name LIKE :value_text_propertyName', ['value_text_propertyName' => '%v\%a\_l%']],
        ];
    }

    public function testInvalidInput(): void
    {
        $crud = $this->createCrud('e.name', ['not-scalar']);
        $crud->getSearchForm()->addFilter('propertyName', TextFilter::class);

        $crud->init();

        $this->checkQueryBuilder($crud, null, []);
    }

    /**
     * @dataProvider getTestSubmitProvider
     */
    public function testSubmit($submittedData, $expectedModelData, $expectedViewData): void
    {
        $crud = $this->createCrud('e.name');
        $crud->getSearchForm()->addFilter('propertyName', TextFilter::class);

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
            ['val', 'val', 'val'],
        ];
    }

    public function testSubmitInvalidFormat(): void
    {
        $crud = $this->createCrud('e.name');
        $crud->getSearchForm()->addFilter('propertyName', TextFilter::class);

        $form = $this->initCrudAndGetForm($crud);
        $form->submit([
            'propertyName' => ['not-scalar'],
        ]);

        $field = $form->get('propertyName');
        $this->assertFalse($field->isSynchronized());
        $this->assertFalse($field->isValid());
        $this->assertNull($field->getData());
    }

    public function testSubmitInvalidMinLength(): void
    {
        $crud = $this->createCrud('e.name');
        $crud->getSearchForm()->addFilter('propertyName', TextFilter::class, [
            'min_length' => 5,
        ]);

        $form = $this->initCrudAndGetForm($crud);
        $form->submit([
            'propertyName' => 'val',
        ]);

        $field = $form->get('propertyName');
        $this->assertTrue($field->isSynchronized());
        $this->assertFalse($field->isValid());
        $this->assertSame('val', $field->getData());
    }

    public function testSubmitInvalidMaxLength(): void
    {
        $crud = $this->createCrud('e.name');
        $crud->getSearchForm()->addFilter('propertyName', TextFilter::class, [
            'max_length' => 2,
        ]);

        $form = $this->initCrudAndGetForm($crud);
        $form->submit([
            'propertyName' => 'val',
        ]);

        $field = $form->get('propertyName');
        $this->assertTrue($field->isSynchronized());
        $this->assertFalse($field->isValid());
        $this->assertSame('val', $field->getData());
    }

    public function testSubmitWithoutValidation(): void
    {
        $crud = $this->createCrud('e.name');
        $crud->getSearchForm()->addFilter('propertyName', TextFilter::class, [
            'max_length' => 2,
            'autovalidate' => false,
        ]);

        $form = $this->initCrudAndGetForm($crud);
        $form->submit([
            'propertyName' => 'val',
        ]);

        $field = $form->get('propertyName');
        $this->assertTrue($field->isSynchronized());
        $this->assertTrue($field->isValid());
        $this->assertSame('val', $field->getData());
        $this->assertSame('val', $field->getViewData());
    }

    public function testWithType(): void
    {
        $crud = $this->createCrud('e.name');
        $crud->getSearchForm()->addFilter('propertyName', TextFilter::class, [
            'type' => TextareaType::class,
        ]);

        $view = $this->initCrudAndGetFormView($crud);

        $field = $view['propertyName'];
        $this->assertContains('textarea', $field->vars['block_prefixes']);
    }
}

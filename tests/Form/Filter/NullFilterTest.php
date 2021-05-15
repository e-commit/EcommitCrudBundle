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

use Ecommit\CrudBundle\Form\Filter\NullFilter;

class NullFilterTest extends AbstractFilterTest
{
    public const TEST_FILTER = NullFilter::class;

    /**
     * @dataProvider getTestViewAndQueryBuilderProvider
     */
    public function testViewAndQueryBuilder($modelData, $expectedChecked, ?string $whereExpected, array $parametersExpected): void
    {
        $crud = $this->createCrud('e.name', $modelData);
        $crud->getSearchForm()->addFilter('propertyName', static::TEST_FILTER);
        $view = $this->initCrudAndGetFormView($crud);

        $this->assertSame('1', $view->children['propertyName']->vars['value']);
        $this->assertSame($expectedChecked, $view->children['propertyName']->vars['checked']);

        $this->checkQueryBuilder($crud, $whereExpected, $parametersExpected);
    }

    public function getTestViewAndQueryBuilderProvider(): array
    {
        return [
            //Null value
            [null, false, null, []],

            //Not checked
            [false, false, null, []],

            //Checked
            [true, true, 'e.name IS NULL', []],
        ];
    }

    /**
     * @dataProvider getTestSubmitProvider
     */
    public function testSubmit($submittedData, $expectedModelData): void
    {
        $crud = $this->createCrud('e.name');
        $crud->getSearchForm()->addFilter('propertyName', static::TEST_FILTER);

        $form = $this->initCrudAndGetForm($crud);
        $form->submit([
            'propertyName' => $submittedData,
        ]);

        $field = $form->get('propertyName');
        $this->assertTrue($field->isSynchronized());
        $this->assertTrue($field->isValid());
        $this->assertSame($expectedModelData, $field->getData());
    }

    public function getTestSubmitProvider(): array
    {
        return [
            [null, false],
            ['', true],
            ['0', true],
            ['2', true],
            ['1', true],
        ];
    }
}

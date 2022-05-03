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

use Doctrine\ORM\EntityRepository;
use Ecommit\CrudBundle\Form\Filter\EntityFilter;
use Ecommit\CrudBundle\Tests\Functional\App\Entity\Tag;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class EntityFilterTest extends AbstractFilterTest
{
    public function testOptionsAreRequired(): void
    {
        $this->expectException(MissingOptionsException::class);

        $crud = $this->createCrud($this->createCrudConfig('e.tag'));
        $crud->getSearchForm()->addFilter('propertyName', EntityFilter::class);
    }

    /**
     * @dataProvider getTestViewAndQueryBuilderProvider
     */
    public function testViewAndQueryBuilder(bool $multiple, $modelData, $expectedViewData, array $expectedIdsFound): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.tag', $modelData));
        $crud->getSearchForm()->addFilter('propertyName', EntityFilter::class, [
            'class' => Tag::class,
            'multiple' => $multiple,
        ]);
        $view = $this->createFormAndGetFormView($crud);

        $this->assertSame($expectedViewData, $view->children['propertyName']->vars['value']);

        $crud->build();
        $idsFound = [];
        foreach ($crud->getQueryBuilder()->getQuery()->getResult() as $entity) {
            $idsFound[] = $entity->getId();
        }
        $this->assertSame($expectedIdsFound, $idsFound);
    }

    public function getTestViewAndQueryBuilderProvider(): array
    {
        return [
            // No multiple
            [false, null, '', [1, 2, 3, 4, 5]],
            [false, 2, '2', [1]],
            [false, 5, '5', [4, 5]],
            [false, 9999, '', []], // 9999 : Entity not found

            // Multiple
            [true, [], [], [1, 2, 3, 4, 5]],
            [true, [2], ['2'], [1]],
            [true, [2, 3], ['2', '3'], [1, 2]],
            [true, [2, 9999], ['2'], [1]], // 9999 : Entity not found
            [true, [9999], [], []], // 9999 : Entity not found
        ];
    }

    /**
     * @dataProvider getTestSubmitProvider
     */
    public function testSubmit(bool $multiple, $submittedData, $expectedModelData, $expectedViewData): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.tag', ($multiple) ? [] : null));
        $crud->getSearchForm()->addFilter('propertyName', EntityFilter::class, [
            'class' => Tag::class,
            'multiple' => $multiple,
        ]);

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
            // No multiple
            [false, null, null, ''],
            [false, '', null, ''],
            [false, '2', '2', '2'],

            // Multiple
            [true, [], [], []],
            [true, ['2'], ['2'], ['2']],
            [true, ['2', '3'], ['2', '3'], ['2', '3']],
        ];
    }

    /**
     * @dataProvider getTestSubmitInvalidProvider
     */
    public function testSubmitInvalid(bool $multiple, $submittedData): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.tag', ($multiple) ? [] : null));
        $crud->getSearchForm()->addFilter('propertyName', EntityFilter::class, [
            'class' => Tag::class,
            'multiple' => $multiple,
        ]);

        $form = $this->createAndGetForm($crud);
        $form->submit([
            'propertyName' => $submittedData,
        ]);

        $field = $form->get('propertyName');
        $this->assertFalse($field->isValid());
    }

    public function getTestSubmitInvalidProvider(): array
    {
        return [
            // No multiple
            [false, []],
            [false, '99999'],

            // Multiple
            [true, '1'],
            [true, ['99999']],
            [true, ['1', '99999']],
        ];
    }

    public function testSubmitInvalidMaxCount(): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.tag', []));
        $crud->getSearchForm()->addFilter('propertyName', EntityFilter::class, [
            'class' => Tag::class,
            'multiple' => true,
            'max' => 2,
        ]);

        $form = $this->createAndGetForm($crud);
        $form->submit([
            'propertyName' => ['1', '2', '3'],
        ]);

        $this->assertFalse($form->isValid());
        $field = $form->get('propertyName');
        $this->assertSame(['1', '2', '3'], $field->getData());
    }

    /**
     * @dataProvider getTestViewWithQueryBuilderProvider
     */
    public function testViewWithQueryBuilder(bool $queryBuilderIsClosure, bool $multiple, $modelData, $expectedViewData, array $expectedIdsFound): void
    {
        if ($queryBuilderIsClosure) {
            $queryBuilder = function (EntityRepository $entityRepository) {
                return $entityRepository->createQueryBuilder('t')
                    ->select('t')
                    ->andWhere('t.id > 2');
            };
        } else {
            $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')
                ->select('t')
                ->andWhere('t.id > 2');
        }

        $crud = $this->createCrud($this->createCrudConfig('e.tag', $modelData));
        $crud->getSearchForm()->addFilter('propertyName', EntityFilter::class, [
            'class' => Tag::class,
            'multiple' => $multiple,
            'type_options' => [
                'query_builder' => $queryBuilder,
            ],
        ]);
        $view = $this->createFormAndGetFormView($crud);

        $this->assertSame($expectedViewData, $view->children['propertyName']->vars['value']);

        $crud->build();
        $idsFound = [];
        foreach ($crud->getQueryBuilder()->getQuery()->getResult() as $entity) {
            $idsFound[] = $entity->getId();
        }
        $this->assertSame($expectedIdsFound, $idsFound);
    }

    public function getTestViewWithQueryBuilderProvider(): array
    {
        return [
            // No multiple - Valid
            [false, false, '4', '4', [3]],
            [true, false, '4', '4', [3]],

            // No multiple - Invalid
            [false, false, '2', '', [1]],
            [true, false, '2', '', [1]],

            // Mutiple - valid
            [false, true, ['4'], ['4'], [3]],
            [true, true, ['4'], ['4'], [3]],

            // Multiple - Invalid
            [false, true, ['2'], [], [1]],
            [true, true, ['2'], [], [1]],
        ];
    }

    /**
     * @dataProvider getTestSubmitWithQueryBuilderProvider
     */
    public function testSubmitWithQueryBuilder(bool $queryBuilderIsClosure, bool $multiple, $submittedData, $expectedValid, $expectedModelData, $expectedViewData): void
    {
        if ($queryBuilderIsClosure) {
            $queryBuilder = function (EntityRepository $entityRepository) {
                return $entityRepository->createQueryBuilder('t')
                    ->select('t')
                    ->andWhere('t.id > 2');
            };
        } else {
            $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')
                ->select('t')
                ->andWhere('t.id > 2');
        }

        $crud = $this->createCrud($this->createCrudConfig('e.tag', ($multiple) ? [] : null));
        $crud->getSearchForm()->addFilter('propertyName', EntityFilter::class, [
            'class' => Tag::class,
            'multiple' => $multiple,
            'type_options' => [
                'query_builder' => $queryBuilder,
            ],
        ]);

        $form = $this->createAndGetForm($crud);
        $form->submit([
            'propertyName' => $submittedData,
        ]);

        $field = $form->get('propertyName');
        $this->assertSame($expectedValid, $field->isValid());
        if ($field->isValid()) {
            $this->assertSame($expectedModelData, $field->getData());
            $this->assertSame($expectedViewData, $field->getViewData());
        }
    }

    public function getTestSubmitWithQueryBuilderProvider(): array
    {
        return [
            // No multiple - Valid
            [false, false, '4', true, '4', '4'],
            [true, false, '4', true, '4', '4'],

            // No multiple - Invalid
            [false, false, '2', false, null, '2'],
            [true, false, '2', false, null, '2'],

            // Multiple - Valid
            [false, true, ['4'], true, ['4'], ['4']],
            [true, true, ['4'], true, ['4'], ['4']],

            // Multiple - Invalid
            [false, true, ['2'], false, [], []],
            [true, true, ['2'], false, [], []],
        ];
    }
}

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
use Ecommit\CrudBundle\Form\Filter\EntityAjaxFilter;
use Ecommit\CrudBundle\Tests\Functional\App\Entity\Tag;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class EntityAjaxFilterTest extends AbstractFilterTest
{
    public function testOptionsAreRequired(): void
    {
        $this->expectException(MissingOptionsException::class);

        $crud = $this->createCrud('e.tag');
        $crud->getSearchForm()->addFilter('propertyName', EntityAjaxFilter::class);
    }

    /**
     * @dataProvider getTestViewAndQueryBuilderProvider
     */
    public function testViewAndQueryBuilder(bool $multiple, $modelData, $expectedViewData, array $expectedIdsFound): void
    {
        $crud = $this->createCrud('e.tag', $modelData);
        $crud->getSearchForm()->addFilter('propertyName', EntityAjaxFilter::class, [
            'class' => Tag::class,
            'route_name' => 'fake_route',
            'multiple' => $multiple,
        ]);
        $view = $this->initCrudAndGetFormView($crud);

        $this->assertSame($expectedViewData, $view->children['propertyName']->vars['value']);

        $crud->buildQuery();
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
            [false, null, null, [1, 2, 3, 4, 5]],
            [false, 2, ['2' => 'tag2'], [1]],
            [false, 5, ['5' => 'tag_name'], [4, 5]],
            [false, 9999, null, []], // 9999 : Entity not found

            // Multiple
            [true, [], [], [1, 2, 3, 4, 5]],
            [true, [2], ['2' => 'tag2'], [1]],
            [true, [2, 3], ['2' => 'tag2', '3' => '3'], [1, 2]],
            [true, [2, 9999], ['2' => 'tag2'], [1]], // 9999 : Entity not found
            [true, [9999], [], []], // 9999 : Entity not found
        ];
    }

    /**
     * @dataProvider getTestSubmitProvider
     */
    public function testSubmit(bool $multiple, $submittedData, $expectedModelData, $expectedViewData): void
    {
        $crud = $this->createCrud('e.tag', ($multiple) ? [] : null);
        $crud->getSearchForm()->addFilter('propertyName', EntityAjaxFilter::class, [
            'class' => Tag::class,
            'route_name' => 'fake_route',
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
            [false, null, null, null],
            [false, '', null, null],
            [false, '2', '2', ['2' => 'tag2']],

            // Multiple
            [true, [], [], []],
            [true, ['2'], ['2'], ['2' => 'tag2']],
            [true, ['2', '3'], ['2', '3'], ['2' => 'tag2', '3' => '3']],
            [true, ['2', ['1']], ['2'], ['2' => 'tag2']], // Ignore not scalar
        ];
    }

    /**
     * @dataProvider getTestSubmitInvalidProvider
     */
    public function testSubmitInvalid(bool $multiple, $submittedData): void
    {
        $crud = $this->createCrud('e.tag', ($multiple) ? [] : null);
        $crud->getSearchForm()->addFilter('propertyName', EntityAjaxFilter::class, [
            'class' => Tag::class,
            'route_name' => 'fake_route',
            'multiple' => $multiple,
            'max' => 2,
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
            [true, ['1', '2', '3']], // max elements
        ];
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

        $crud = $this->createCrud('e.tag', $modelData);
        $crud->getSearchForm()->addFilter('propertyName', EntityAjaxFilter::class, [
            'class' => Tag::class,
            'route_name' => 'fake_route',
            'multiple' => $multiple,
            'type_options' => [
                'query_builder' => $queryBuilder,
            ],
        ]);
        $view = $this->initCrudAndGetFormView($crud);

        $this->assertSame($expectedViewData, $view->children['propertyName']->vars['value']); // Twig doesn't display invalid list

        $crud->buildQuery();
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
            [false, false, '4', ['4' => 'tag_name'], [3]],
            [true, false, '4', ['4' => 'tag_name'], [3]],

            // No multiple - Invalid but display results
            [false, false, '2', null, [1]],
            [true, false, '2', null, [1]],

            // Mutiple - valid
            [false, true, ['4'], ['4' => 'tag_name'], [3]],
            [true, true, ['4'], ['4' => 'tag_name'], [3]],

            // Multiple - Invalid but display results
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

        $crud = $this->createCrud('e.tag', ($multiple) ? [] : null);
        $crud->getSearchForm()->addFilter('propertyName', EntityAjaxFilter::class, [
            'class' => Tag::class,
            'route_name' => 'fake_route',
            'multiple' => $multiple,
            'type_options' => [
                'query_builder' => $queryBuilder,
            ],
        ]);

        $form = $this->initCrudAndGetForm($crud);
        $form->submit([
            'propertyName' => $submittedData,
        ]);

        $field = $form->get('propertyName');
        $this->assertSame($expectedValid, $field->isSynchronized());
        $this->assertSame($expectedValid, $field->isValid());
        $this->assertSame($expectedModelData, $field->getData());
        $this->assertSame($expectedViewData, $field->getViewData()); // Twig doesn't display invalid list
    }

    public function getTestSubmitWithQueryBuilderProvider(): array
    {
        return [
            // No multiple - Valid
            [false, false, '4', true, '4', ['4' => 'tag_name']],
            [true, false, '4', true, '4', ['4' => 'tag_name']],

            // No multiple - Invalid
            [false, false, '2', false, null, '2'],
            [true, false, '2', false, null, '2'],

            // Multiple - Valid
            [false, true, ['4'], true, ['4'], ['4' => 'tag_name']],
            [true, true, ['4'], true, ['4'], ['4' => 'tag_name']],

            // Multiple - Valid
            [false, true, ['2'], false, null, ['2']],
            [true, true, ['2'], false, null, ['2']],
        ];
    }
}

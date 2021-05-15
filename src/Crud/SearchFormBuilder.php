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

namespace Ecommit\CrudBundle\Crud;

use Ecommit\CrudBundle\Form\Filter\FilterInterface;
use Ecommit\CrudBundle\Form\Searcher\SearcherInterface;
use Ecommit\CrudBundle\Form\Type\FormSearchType;
use Psr\Container\ContainerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class SearchFormBuilder
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Crud
     */
    protected $crud;

    /**
     * @var SearcherInterface
     */
    protected $defaultData;

    protected $options;

    protected $form;

    protected $filters = [];

    public function __construct(ContainerInterface $container, Crud $crud, SearcherInterface $defaultData, ?string $type, array $options)
    {
        $this->container = $container;
        $this->crud = $crud;
        $this->defaultData = $defaultData;

        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'autovalidate' => true,
            'validation_groups' => null,
            'form_options' => [],
        ]);
        $defaultData->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        $this->createFormBuilder($type);
    }

    protected function createFormBuilder(?string $type): void
    {
        $formFactory = $this->container->get('form.factory');

        if ($type) {
            $this->form = $formFactory->createBuilder($type, null, $this->options['form_options']);
        } else {
            $formName = sprintf('crud_search_%s', $this->crud->getSessionName());
            $this->form = $formFactory->createNamedBuilder($formName, FormSearchType::class, null, $this->options['form_options']);
        }

        $this->defaultData->buildForm($this, $this->options);
    }

    public function addFilter(string $property, string $filter, array $options = []): self
    {
        if (!$this->container->get('ecommit_crud.filters')->has($filter)) {
            throw new \Exception(sprintf('Filter "%s" not found', $filter));
        }
        /** @var FilterInterface $filterService */
        $filterService = $this->container->get('ecommit_crud.filters')->get($filter);

        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'column_id' => $property,
            'autovalidate' => $this->options['autovalidate'],
            'validation_groups' => $this->options['validation_groups'],
            'required' => false,
            'type_options' => [],
            'alias_search' => function (Options $options) {
                $columns = $this->crud->getColumns();
                if (\array_key_exists($options['column_id'], $columns)) {
                    return $columns[$options['column_id']]->aliasSearch;
                }

                $virtualColumns = $this->crud->getVirtualColumns();
                if (\array_key_exists($options['column_id'], $virtualColumns)) {
                    return $virtualColumns[$options['column_id']]->aliasSearch;
                }

                return null;
            },
            'label' => function (Options $options) {
                $columns = $this->crud->getColumns();
                if (\array_key_exists($options['column_id'], $columns)) {
                    return $columns[$options['column_id']]->label;
                }

                $virtualColumns = $this->crud->getVirtualColumns();
                if (\array_key_exists($options['column_id'], $virtualColumns)) {
                    return $virtualColumns[$options['column_id']]->label;
                }

                return null;
            },
        ]);
        $filterService->configureOptions($resolver);
        $resolvedOptions = $resolver->resolve($options);

        $this->filters[$property] = [
            'name' => $filter,
            'options' => $resolvedOptions,
        ];

        return $this;
    }

    public function addField(string $property, string $type, array $options = []): self
    {
        $this->form->add($property, $type, $options);

        return $this;
    }

    public function getField(string $property)
    {
        return $this->form->get($property);
    }

    public function createForm(): void
    {
        //Add filters
        foreach ($this->filters as $property => $filter) {
            //Check if column exists
            $columnId = $filter['options']['column_id'];
            if (!\array_key_exists($columnId, $this->crud->getColumns()) && !\array_key_exists($columnId, $this->crud->getVirtualColumns())) {
                throw new \Exception(sprintf('Column "%s" not found', $columnId));
            }

            /** @var FilterInterface $filterService */
            $filterService = $this->container->get('ecommit_crud.filters')->get($filter['name']);
            $filterService->buildForm($this, $property, $filter['options']);
        }

        $this->form->setAction($this->crud->getSearchUrl());
        $this->form = $this->form->getForm();
    }

    public function createFormView(): void
    {
        $this->form = $this->form->createView();
    }

    /**
     * @return FormBuilder|Form|FormView
     */
    public function getForm()
    {
        return $this->form;
    }

    public function getDefaultData(): SearcherInterface
    {
        return $this->defaultData;
    }

    public function updateQueryBuilder($queryBuilder, SearcherInterface $searcher): void
    {
        $searcher->updateQueryBuilder($queryBuilder, $this->options);

        foreach ($this->filters as $property => $filter) {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            $value = $propertyAccessor->getValue($searcher, $property);

            /** @var FilterInterface $filterService */
            $filterService = $this->container->get('ecommit_crud.filters')->get($filter['name']);
            if (!$filterService->supportsQueryBuilder($queryBuilder)) {
                throw new \Exception('"%s" filter does not support "%s" query builder', $filter['name'], \get_class($queryBuilder));
            }
            $filterService->updateQueryBuilder($queryBuilder, $property, $value, $filter['options']);
        }
    }
}

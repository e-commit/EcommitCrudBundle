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

use Ecommit\CrudBundle\Form\Searcher\SearcherInterface;

final class CrudConfig implements \ArrayAccess
{
    protected array $options = [];

    public function __construct(?string $sessionName = null)
    {
        if (null !== $sessionName) {
            $this->setSessionName($sessionName);
        }
    }

    public function setSessionName(string $sessionName): self
    {
        $this->options['session_name'] = $sessionName;

        return $this;
    }

    /**
     * Usage:
     *      ->addColumn(['id' => 'column_id', 'alias' => 'column_alias'])
     *  Called with an array of options.
     *
     * @see CrudColumn for available options
     *
     * @psalm-suppress MissingParamType
     */
    public function addColumn(...$args): self
    {
        if (1 === \count($args)) {
            $column = $args[0];
        } elseif (3 === \count($args)) {
            $column = [
                'id' => $args[0],
                'alias' => $args[1],
                'label' => $args[2],
            ];
        } elseif (4 === \count($args) && \is_array($args[3])) {
            $column = [
                'id' => $args[0],
                'alias' => $args[1],
                'label' => $args[2],
            ];
            foreach ($args[3] as $optionName => $optionValue) {
                $column[$optionName] = $optionValue;
            }
        } else {
            throw new \Exception('Bad addColumn call');
        }

        if (!\array_key_exists('columns', $this->options)) {
            $this->options['columns'] = [];
        }
        $this->options['columns'][] = $column;

        return $this;
    }

    /**
     * Usage:
     *      ->addColumn(['id' => 'column_id', 'alias' => 'column_alias'])
     *  Called with an array of options.
     *
     * @see CrudColumn for available options
     *
     * @psalm-suppress MissingParamType
     */
    public function addVirtualColumn(...$args): self
    {
        if (1 === \count($args)) {
            $column = $args[0];
        } elseif (2 === \count($args)) {
            $column = [
                'id' => $args[0],
                'alias' => $args[1],
            ];
        } else {
            throw new \Exception('Bad addVirtualColumn call');
        }

        if (!\array_key_exists('virtual_columns', $this->options)) {
            $this->options['virtual_columns'] = [];
        }
        $this->options['virtual_columns'][] = $column;

        return $this;
    }

    public function setMaxPerPage(array $choices, int $defaultValue): self
    {
        $this->options['max_per_page_choices'] = $choices;
        $this->options['default_max_per_page'] = $defaultValue;

        return $this;
    }

    /**
     * Set the default sort.
     *
     * @param string $sort          Column id
     * @param string $sortDirection Sort direction (Crud::ASC / Crud::DESC)
     */
    public function setDefaultSort(string $sort, string $sortDirection): self
    {
        $this->options['default_sort'] = $sort;
        $this->options['default_sort_direction'] = $sortDirection;

        return $this;
    }

    /**
     * Set the default personalized sort.
     *
     * @param array $criterias Criterias :
     *                         If key is defined: Key = Sort  Value = Sort direction
     *                         If key is not defined: Value = Sort
     */
    public function setDefaultPersonalizedSort(array $criterias): self
    {
        $this->options['default_sort'] = 'defaultPersonalizedSort';
        $this->options['default_personalized_sort'] = $criterias;

        return $this;
    }

    public function setQueryBuilder(\Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder|QueryBuilderInterface $queryBuilder): self
    {
        $this->options['query_builder'] = $queryBuilder;

        return $this;
    }

    public function setRoute(string $name, array $parameters = []): self
    {
        $this->options['route_name'] = $name;
        $this->options['route_parameters'] = $parameters;

        return $this;
    }

    public function createSearchForm(SearcherInterface $defaultData, ?string $type = null, array $options = []): self
    {
        $this->options['search_form_data'] = $defaultData;
        $this->options['search_form_type'] = $type;
        $this->options['search_form_options'] = $options;

        return $this;
    }

    public function setDisplayResultsOnlyIfSearch(bool $value): self
    {
        $this->options['display_results_only_if_search'] = $value;

        return $this;
    }

    /**
     * Enables (or not) the auto build paginator.
     */
    public function setBuildPaginator(bool|\Closure|array $value): self
    {
        $this->options['build_paginator'] = $value;

        return $this;
    }

    public function setPersistentSettings(bool $value): self
    {
        $this->options['persistent_settings'] = $value;

        return $this;
    }

    public function setDivIdSearch(string $value): self
    {
        $this->options['div_id_search'] = $value;

        return $this;
    }

    public function setDivIdList(string $value): self
    {
        $this->options['div_id_list'] = $value;

        return $this;
    }

    public function setTwigFunctionsConfiguration(array $value): self
    {
        $this->options['twig_functions_configuration'] = $value;

        return $this;
    }

    public function resetOptions(null|string|array $options = null): self
    {
        if (null === $options) {
            $this->options = [];
        }

        $options = (\is_array($options)) ? $options : [$options];
        foreach ($options as $option) {
            if (\array_key_exists($option, $this->options)) {
                unset($this->options[$option]);
            }
        }

        return $this;
    }

    public function offsetExists(mixed $offset): bool
    {
        return \array_key_exists($offset, $this->options);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->options[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        /** @psalm-suppress PossiblyNullArrayOffset */
        $this->options[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->options[$offset]);
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}

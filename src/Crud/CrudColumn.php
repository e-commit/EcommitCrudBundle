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

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CrudColumn
{
    /**
     * @var array{
     *     id: string,
     *     alias: string,
     *     label: string,
     *     sortable: bool,
     *     displayed_by_default: bool,
     *     alias_sort: string,
     *     alias_search: string
     * }
     */
    protected array $options;

    /**
     * Constructor.
     *
     * @param array $options Options:
     *                       * id : Column id (used everywhere inside the crud) (Required)
     *                       * alias: Column SQL alias (Required)
     *                       * label: Column label (used in the header table) (Default: id option)
     *                       * sortable: If the column is sortable (Défault: true)
     *                       * displayed_by_default: If the column is displayed, by default (Default: true)
     *                       * alias_sort: Column(s) SQL alias (string or array of strings), used during sorting (Defaut: alias option)
     *                       * alias_search: Column SQL alias, used during searchs (Defaut: alias option)
     */
    public function __construct(array $options)
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired([
            'id',
            'alias',
        ]);
        $resolver->setDefaults([
            'label' => fn (Options $options): string => $options['id'],
            'sortable' => true,
            'displayed_by_default' => true,
            'alias_sort' => fn (Options $options): string => $options['alias'],
            'alias_search' => fn (Options $options): string => $options['alias'],
        ]);
        $resolver->setAllowedTypes('id', 'string');
        $resolver->setAllowedTypes('alias', 'string');
        $resolver->setAllowedTypes('label', 'string');
        $resolver->setAllowedTypes('sortable', 'bool');
        $resolver->setAllowedTypes('displayed_by_default', 'bool');
        $resolver->setAllowedTypes('alias_sort', 'string');
        $resolver->setAllowedTypes('alias_search', 'string');

        $this->options = $resolver->resolve($options);
    }

    public function getId(): string
    {
        return $this->options['id'];
    }

    public function getAlias(): string
    {
        return $this->options['alias'];
    }

    public function getLabel(): string
    {
        return $this->options['label'];
    }

    public function isSortable(): bool
    {
        return $this->options['sortable'];
    }

    public function isDisplayedByDefault(): bool
    {
        return $this->options['displayed_by_default'];
    }

    public function getAliasSearch(): string
    {
        return $this->options['alias_search'];
    }

    public function getAliasSort(): string
    {
        return $this->options['alias_sort'];
    }
}

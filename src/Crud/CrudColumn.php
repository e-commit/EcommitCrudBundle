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

class CrudColumn
{
    public $id;
    public $alias;
    public $aliasSearch;
    public $aliasSort;
    public $label;
    public $sortable;
    public $defaultDisplayed;

    /**
     * Constructor.
     *
     * @param string $id               Column id (used everywhere inside the crud)
     * @param string $alias            Column SQL alias
     * @param string $label            Column label (used in the header table)
     * @param bool   $sortable         If the column is sortable
     * @param bool   $defaultDisplayed If the column is displayed, by default
     * @param string $aliasSearch      Column SQL alias, used during searchs
     * @param string $aliasSort        Column(s) SQL alias (string or array of strings), used during sorting
     */
    public function __construct($id, $alias, $label, $sortable, $defaultDisplayed, $aliasSearch, $aliasSort)
    {
        $this->id = $id;
        $this->alias = $alias;
        $this->label = $label;
        $this->sortable = $sortable;
        $this->defaultDisplayed = $defaultDisplayed;
        $this->aliasSearch = $aliasSearch ?: $alias;
        $this->aliasSort = $aliasSort ?: $alias;
    }
}

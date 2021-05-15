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

class CrudSession
{
    /**
     * Search's object (used by "setData" inside the form). Used to
     * save the data of the search form.
     *
     * @var SearcherInterface
     */
    public $searchFormData = null;

    public $searchFormIsSubmitted = false;

    /**
     * Number of results, in one page.
     *
     * @var int
     */
    public $resultsPerPage = null;

    /**
     * Displayed colums.
     *
     * @var type
     */
    public $displayedColumns = [];

    /**
     * Sortable: Sort (Column id).
     *
     * @var type
     */
    public $sort = null;

    /**
     * Sortable: Sens (ASC / DESC).
     *
     * @var type
     */
    public $sense = null;

    /**
     * Page number.
     *
     * @var int
     */
    public $page = 1;
}

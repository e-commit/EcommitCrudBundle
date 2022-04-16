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

/**
 * @internal
 *
 * $searchFormData: Search's object (used by "setData" inside the form). Used to save the data of the search form.
 */
final class CrudSession
{
    public int $page = 1;
    public bool $searchFormIsSubmittedAndValid = false;

    public function __construct(public int $resultsPerPage, public array $displayedColumns, public string $sort, public string $sortDirection, public ?SearcherInterface $searchFormData = null)
    {
    }
}

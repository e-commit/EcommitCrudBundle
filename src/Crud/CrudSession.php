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

use Ecommit\CrudBundle\Entity\UserCrudSettings;
use Ecommit\CrudBundle\Form\Searcher\SearcherInterface;

final class CrudSession
{
    /**
     * Search's object (used by "setData" inside the form). Used to save the data of the search form.
     */
    protected ?SearcherInterface $searchFormData;

    protected int $page = 1;
    protected bool $searchFormIsSubmittedAndValid = false;

    /**
     * @internal
     */
    public function __construct(protected int $maxPerPage, protected array $displayedColumns, protected string $sort, protected string $sortDirection, SearcherInterface $searchFormData = null)
    {
        $this->searchFormData = ($searchFormData) ? clone $searchFormData : null; // Avoid modification by reference (by the user or an invalid form)
    }

    public function __clone()
    {
        if (null !== $this->searchFormData) {
            $this->searchFormData = clone $this->searchFormData; // Avoid modification by reference (by the user or an invalid form)
        }
    }

    /**
     * @internal
     */
    public function updateFromUserCrudSettings(UserCrudSettings $crudSettings): self
    {
        $instance = clone $this; // Avoid modification by reference (by the user or an invalid form)
        $instance->maxPerPage = $crudSettings->getMaxPerPage();
        $instance->displayedColumns = $crudSettings->getDisplayedColumns();
        $instance->sort = $crudSettings->getSort();
        $instance->sortDirection = $crudSettings->getSortDirection();

        return $instance;
    }

    /**
     * @internal
     */
    public function updateUserCrudSettings(UserCrudSettings $crudSettings): void
    {
        $crudSettings->setMaxPerPage($this->maxPerPage);
        $crudSettings->setDisplayedColumns($this->displayedColumns);
        $crudSettings->setSort($this->sort);
        $crudSettings->setSortDirection($this->sortDirection);
    }

    public function getMaxPerPage(): int
    {
        return $this->maxPerPage;
    }

    /**
     * @internal
     */
    public function setMaxPerPage(int $maxPerPage): self
    {
        $instance = clone $this; // Avoid modification by reference (by the user or an invalid form)
        $instance->maxPerPage = $maxPerPage;

        return $instance;
    }

    public function getDisplayedColumns(): array
    {
        return $this->displayedColumns;
    }

    /**
     * @internal
     */
    public function setDisplayedColumns(array $displayedColumns): self
    {
        $instance = clone $this; // Avoid modification by reference (by the user or an invalid form)
        $instance->displayedColumns = $displayedColumns;

        return $instance;
    }

    public function getSort(): string
    {
        return $this->sort;
    }

    /**
     * @internal
     */
    public function setSort(string $sort): self
    {
        $instance = clone $this; // Avoid modification by reference (by the user or an invalid form)
        $instance->sort = $sort;

        return $instance;
    }

    public function getSortDirection(): string
    {
        return $this->sortDirection;
    }

    /**
     * @internal
     */
    public function setSortDirection(string $sortDirection): self
    {
        $instance = clone $this; // Avoid modification by reference (by the user or an invalid form)
        $instance->sortDirection = $sortDirection;

        return $instance;
    }

    public function getSearchFormData(): ?SearcherInterface
    {
        if (null === $this->searchFormData) {
            return null;
        }

        return clone $this->searchFormData; // Avoid modification by reference (by the user or an invalid form)
    }

    /**
     * @internal
     */
    public function setSearchFormData(?SearcherInterface $searchFormData): self
    {
        $instance = clone $this; // Avoid modification by reference (by the user or an invalid form)
        if (null === $searchFormData) {
            $instance->searchFormData = null;
        } else {
            $instance->searchFormData = clone $searchFormData; // Avoid modification by reference (by the user or an invalid form)
        }

        return $instance;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @internal
     */
    public function setPage(int $page): self
    {
        $instance = clone $this; // Avoid modification by reference (by the user or an invalid form)
        $instance->page = $page;

        return $instance;
    }

    public function isSearchFormIsSubmittedAndValid(): bool
    {
        return $this->searchFormIsSubmittedAndValid;
    }

    /**
     * @internal
     */
    public function setSearchFormIsSubmittedAndValid(bool $searchFormIsSubmittedAndValid): self
    {
        $instance = clone $this; // Avoid modification by reference (by the user or an invalid form)
        $instance->searchFormIsSubmittedAndValid = $searchFormIsSubmittedAndValid;

        return $instance;
    }
}

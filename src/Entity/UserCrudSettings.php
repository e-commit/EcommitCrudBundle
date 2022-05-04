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

namespace Ecommit\CrudBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @psalm-suppress ArgumentTypeCoercion
 */
#[ORM\Entity]
#[ORM\Table(name: 'user_crud_settings')]
class UserCrudSettings
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: 'Ecommit\CrudBundle\Entity\UserCrudInterface')]
    protected UserCrudInterface $user;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 255, name: 'crud_name')]
    protected string $crudName;

    #[ORM\Column(type: 'integer', name: 'max_per_page')]
    protected int $maxPerPage;

    #[ORM\Column(type: 'json', name: 'displayed_columns')]
    protected array $displayedColumns = [];

    #[ORM\Column(type: 'string', length: 100)]
    protected string $sort;

    #[ORM\Column(type: 'string', length: 4)]
    protected string $sortDirection;

    public function __construct(UserCrudInterface $user, string $crudName, int $maxPerPage, array $displayedColumns, string $sort, string $sortDirection)
    {
        $this->user = $user;
        $this->crudName = $crudName;
        $this->maxPerPage = $maxPerPage;
        $this->displayedColumns = $displayedColumns;
        $this->sort = $sort;
        $this->sortDirection = $sortDirection;
    }

    public function setCrudName(string $crudName): self
    {
        $this->crudName = $crudName;

        return $this;
    }

    public function getCrudName(): string
    {
        return $this->crudName;
    }

    public function setMaxPerPage(int $maxPerPage): self
    {
        $this->maxPerPage = $maxPerPage;

        return $this;
    }

    public function getMaxPerPage(): int
    {
        return $this->maxPerPage;
    }

    public function setDisplayedColumns(array $displayedColumns): self
    {
        $this->displayedColumns = $displayedColumns;

        return $this;
    }

    public function getDisplayedColumns(): array
    {
        return $this->displayedColumns;
    }

    public function setSort(string $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    public function getSort(): string
    {
        return $this->sort;
    }

    public function setSortDirection(string $sortDirection): self
    {
        $this->sortDirection = $sortDirection;

        return $this;
    }

    public function getSortDirection(): string
    {
        return $this->sortDirection;
    }

    public function setUser(\Ecommit\CrudBundle\Entity\UserCrudInterface $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): \Ecommit\CrudBundle\Entity\UserCrudInterface
    {
        return $this->user;
    }
}

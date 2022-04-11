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
use Ecommit\CrudBundle\Crud\CrudSession;

#[ORM\Entity]
#[ORM\Table(name: 'user_crud_settings')]
class UserCrudSettings
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: 'Ecommit\CrudBundle\Entity\UserCrudInterface')]
    protected $user;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 255, name: 'crud_name')]
    protected $crudName;

    #[ORM\Column(type: 'integer', name: 'results_displayed')]
    protected $resultsDisplayed;

    #[ORM\Column(type: 'array', name: 'displayed_columns')]
    protected $displayedColumns = [];

    #[ORM\Column(type: 'string', length: 100)]
    protected $sort;

    #[ORM\Column(type: 'string', length: 4)]
    protected $sense;

    public function setCrudName(string $crudName): self
    {
        $this->crudName = $crudName;

        return $this;
    }

    public function getCrudName(): string
    {
        return $this->crudName;
    }

    public function setResultsDisplayed(int $resultsDisplayed): self
    {
        $this->resultsDisplayed = $resultsDisplayed;

        return $this;
    }

    public function getResultsDisplayed(): int
    {
        return $this->resultsDisplayed;
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

    public function setSense(string $sense): self
    {
        $this->sense = $sense;

        return $this;
    }

    public function getSense(): string
    {
        return $this->sense;
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

    /**
     * Create CrudSession from this object.
     */
    public function transformToCrudSession(CrudSession $crudSessionManager): self
    {
        $crudSessionManager->displayedColumns = $this->displayedColumns;
        $crudSessionManager->resultsPerPage = $this->resultsDisplayed;
        $crudSessionManager->sense = $this->sense;
        $crudSessionManager->sort = $this->sort;

        return $crudSessionManager;
    }

    /**
     * Update this object from CrudSession.
     */
    public function updateFromSessionManager(CrudSession $crudSessionManager): void
    {
        $this->displayedColumns = $crudSessionManager->displayedColumns;
        $this->resultsDisplayed = $crudSessionManager->resultsPerPage;
        $this->sense = $crudSessionManager->sense;
        $this->sort = $crudSessionManager->sort;
    }
}

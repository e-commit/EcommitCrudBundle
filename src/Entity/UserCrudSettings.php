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

    /**
     * Set crudName.
     *
     * @param string $crudName
     *
     * @return UserCrudSettings
     */
    public function setCrudName($crudName)
    {
        $this->crudName = $crudName;

        return $this;
    }

    /**
     * Get crudName.
     *
     * @return string
     */
    public function getCrudName()
    {
        return $this->crudName;
    }

    /**
     * Set resultsDisplayed.
     *
     * @param int $resultsDisplayed
     *
     * @return UserCrudSettings
     */
    public function setResultsDisplayed($resultsDisplayed)
    {
        $this->resultsDisplayed = $resultsDisplayed;

        return $this;
    }

    /**
     * Get resultsDisplayed.
     *
     * @return int
     */
    public function getResultsDisplayed()
    {
        return $this->resultsDisplayed;
    }

    /**
     * Set displayedColumns.
     *
     * @param array $displayedColumns
     *
     * @return UserCrudSettings
     */
    public function setDisplayedColumns($displayedColumns)
    {
        $this->displayedColumns = $displayedColumns;

        return $this;
    }

    /**
     * Get displayedColumns.
     *
     * @return array
     */
    public function getDisplayedColumns()
    {
        return $this->displayedColumns;
    }

    /**
     * Set sort.
     *
     * @param string $sort
     *
     * @return UserCrudSettings
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort.
     *
     * @return string
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set sense.
     *
     * @param string $sense
     *
     * @return UserCrudSettings
     */
    public function setSense($sense)
    {
        $this->sense = $sense;

        return $this;
    }

    /**
     * Get sense.
     *
     * @return string
     */
    public function getSense()
    {
        return $this->sense;
    }

    /**
     * Set user.
     *
     * @return UserCrudSettings
     */
    public function setUser(\Ecommit\CrudBundle\Entity\UserCrudInterface $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \Ecommit\CrudBundle\Entity\UserCrudInterface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Create CrudSession from this object.
     *
     * @return \Ecommit\CrudBundle\Crud\CrudSession
     */
    public function transformToCrudSession(CrudSession $crudSessionManager)
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

<?php

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

/**
 * @ORM\Entity()
 * @ORM\Table(name="user_crud_settings")
 */
class UserCrudSettings
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Ecommit\CrudBundle\Entity\UserCrudInterface")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;
    
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=30)
     */
    protected $crud_name;
    
    /**
     * @ORM\Column(type="integer")
     */
    protected $results_displayed;
    
    /**
     * @ORM\Column(type="array", name="displayed_columns")
     */
    protected $displayedColumns = array();
    
    /**
     * @ORM\Column(type="string", length=30)
     */
    protected $sort;
    
    /**
     * @ORM\Column(type="string", length=4)
     */
    protected $sense;

    /**
     * Set crud_name
     *
     * @param string $crudName
     * @return UserCrudSettings
     */
    public function setCrudName($crudName)
    {
        $this->crud_name = $crudName;
    
        return $this;
    }

    /**
     * Get crud_name
     *
     * @return string 
     */
    public function getCrudName()
    {
        return $this->crud_name;
    }

    /**
     * Set results_displayed
     *
     * @param integer $resultsDisplayed
     * @return UserCrudSettings
     */
    public function setResultsDisplayed($resultsDisplayed)
    {
        $this->results_displayed = $resultsDisplayed;
    
        return $this;
    }

    /**
     * Get results_displayed
     *
     * @return integer 
     */
    public function getResultsDisplayed()
    {
        return $this->results_displayed;
    }

    /**
     * Set displayedColumns
     *
     * @param array $displayedColumns
     * @return UserCrudSettings
     */
    public function setDisplayedColumns($displayedColumns)
    {
        $this->displayedColumns = $displayedColumns;
    
        return $this;
    }

    /**
     * Get displayedColumns
     *
     * @return array 
     */
    public function getDisplayedColumns()
    {
        return $this->displayedColumns;
    }

    /**
     * Set sort
     *
     * @param string $sort
     * @return UserCrudSettings
     */
    public function setSort($sort)
    {
        $this->sort = $sort;
    
        return $this;
    }

    /**
     * Get sort
     *
     * @return string 
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set sense
     *
     * @param string $sense
     * @return UserCrudSettings
     */
    public function setSense($sense)
    {
        $this->sense = $sense;
    
        return $this;
    }

    /**
     * Get sense
     *
     * @return string 
     */
    public function getSense()
    {
        return $this->sense;
    }

    /**
     * Set user
     *
     * @param \Ecommit\CrudBundle\Entity\UserCrudInterface $user
     * @return UserCrudSettings
     */
    public function setUser(\Ecommit\CrudBundle\Entity\UserCrudInterface $user)
    {
        $this->user = $user;
    
        return $this;
    }

    /**
     * Get user
     *
     * @return \Ecommit\CrudBundle\Entity\UserCrudInterface 
     */
    public function getUser()
    {
        return $this->user;
    }
    
    /**
     * Create CrudSession from this object
     * 
     * @param \Ecommit\CrudBundle\Crud\CrudSession $crud_session_manager
     * @return \Ecommit\CrudBundle\Crud\CrudSession
     */
    public function transformToCrudSession(CrudSession $crud_session_manager)
    {
        $crud_session_manager->columns_diplayed = $this->displayedColumns;
        $crud_session_manager->resultsPerPage = $this->results_displayed;
        $crud_session_manager->sense = $this->sense;
        $crud_session_manager->sort = $this->sort;
        
        return $crud_session_manager;
    }
    
    /**
     * Update this object from CrudSession
     * 
     * @param \Ecommit\CrudBundle\Crud\CrudSession $crud_session_manager
     */
    public function updateFromSessionManager(CrudSession $crud_session_manager)
    {
        $this->displayedColumns = $crud_session_manager->columns_diplayed;
        $this->results_displayed = $crud_session_manager->resultsPerPage;
        $this->sense = $crud_session_manager->sense;
        $this->sort = $crud_session_manager->sort;
    }
}
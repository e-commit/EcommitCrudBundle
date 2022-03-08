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

namespace Ecommit\CrudBundle\Tests\Functional\App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Ecommit\CrudBundle\Controller\AbstractCrudController;
use Ecommit\CrudBundle\Crud\Crud;
use Ecommit\CrudBundle\Tests\Functional\App\Entity\TestUser;
use Ecommit\CrudBundle\Tests\Functional\App\Form\Searcher\UserSearcher;

class UserController extends AbstractCrudController
{
    protected function getCrud(): Crud
    {
        $em = $this->container->get('doctrine')->getManager();

        $queryBuilder = $em->getRepository(TestUser::class)
            ->createQueryBuilder('u')
            ->select('u');

        $crud = $this->createCrud('user');
        $crud->addColumn('username', 'u.username', 'username', ['default_displayed' => false])
            ->addColumn('firstName', 'u.firstName', 'first_name')
            ->addColumn('lastName', 'u.lastName', 'last_name')
            ->setQueryBuilder($queryBuilder)
            ->setAvailableResultsPerPage([5, 5, 10, 50], 5)
            ->setDefaultSort('firstName', Crud::ASC)
            ->createSearchForm(new UserSearcher())
            ->setRoute('user_ajax_crud')
            ->setPersistentSettings(true);

        $request = $this->container->get('request_stack')->getCurrentRequest();
        if ($request->query->has('manual-reset')) {
            $crud->raz();
        }
        if ($request->query->has('manual-reset-sort')) {
            $crud->razSort();
        }

        return $crud;
    }

    protected function getTemplateName(string $action): string
    {
        return sprintf('user/%s.html.twig', $action);
    }

    public function crudAction()
    {
        return $this->getCrudResponse();
    }

    public function ajaxCrudAction()
    {
        return $this->getAjaxCrudResponse();
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'doctrine' => ManagerRegistry::class,
        ]);
    }
}

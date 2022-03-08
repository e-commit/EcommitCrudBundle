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
use Ecommit\CrudBundle\Crud\Crud;
use Ecommit\CrudBundle\Crud\CrudFactory;
use Ecommit\CrudBundle\Crud\CrudResponseGenerator;
use Ecommit\CrudBundle\Tests\Functional\App\Entity\TestUser;
use Ecommit\CrudBundle\Tests\Functional\App\Form\Searcher\UserSearcher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class UserWithoutTraitController extends AbstractController
{
    protected function getCrud(string $sessionName, array $routeParams = []): Crud
    {
        $em = $this->container->get('doctrine')->getManager();

        $queryBuilder = $em->getRepository(TestUser::class)
            ->createQueryBuilder('u')
            ->select('u');

        $crud = $this->container->get(CrudFactory::class)->create($sessionName);
        $crud->addColumn('username', 'u.username', 'username', ['default_displayed' => false])
            ->addColumn('firstName', 'u.firstName', 'first_name')
            ->addColumn('lastName', 'u.lastName', 'last_name')
            ->setQueryBuilder($queryBuilder)
            ->setAvailableResultsPerPage([5, 5, 10, 50], 5)
            ->setDefaultSort('firstName', Crud::ASC)
            ->createSearchForm(new UserSearcher())
            ->setRoute('user_without_trait_ajax_crud', $routeParams)
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

    public function crudAction(Request $request, CrudResponseGenerator $crudResponseGenerator)
    {
        if ($request->query->has('test-before-and-after-build-query')) {
            return $crudResponseGenerator->getResponse($this->getCrud('user_without_trait_with_data', ['test-before-and-after-build-query' => 1]), [
                'template_generator' => function (string $action) {
                    return sprintf('user/%s.html.twig', $action);
                },
                'before_build_query' => function (Crud $crud, array $data) {
                    $data['test_before_after_build_query'] = 'BEFORE';

                    return $data;
                },
                'after_build_query' => function (Crud $crud, array $data) {
                    $data['test_before_after_build_query'] = $data['test_before_after_build_query'].' AFTER';

                    return $data;
                },
            ]);
        }

        return $crudResponseGenerator->getResponse($this->getCrud('user_without_trait'), [
            'template_generator' => function (string $action) {
                return sprintf('user/%s.html.twig', $action);
            },
        ]);
    }

    public function ajaxCrudAction(Request $request, CrudResponseGenerator $crudResponseGenerator)
    {
        if ($request->query->has('test-before-and-after-build-query')) {
            return $crudResponseGenerator->getAjaxResponse($this->getCrud('user_without_trait_with_data', ['test-before-and-after-build-query' => 1]), [
                'template_generator' => function (string $action) {
                    return sprintf('user/%s.html.twig', $action);
                },
                'before_build_query' => function (Crud $crud, array $data) {
                    $data['test_before_after_build_query'] = 'BEFORE';

                    return $data;
                },
                'after_build_query' => function (Crud $crud, array $data) {
                    $data['test_before_after_build_query'] = $data['test_before_after_build_query'].' AFTER';

                    return $data;
                },
            ]);
        }

        return $crudResponseGenerator->getAjaxResponse($this->getCrud('user_without_trait'), [
            'template_generator' => function (string $action) {
                return sprintf('user/%s.html.twig', $action);
            },
        ]);
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            CrudFactory::class,
            'doctrine' => ManagerRegistry::class,
        ]);
    }
}

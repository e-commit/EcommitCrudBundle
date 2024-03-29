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
use Ecommit\CrudBundle\Crud\CrudConfig;
use Ecommit\CrudBundle\Crud\CrudFactory;
use Ecommit\CrudBundle\Crud\CrudResponseGenerator;
use Ecommit\CrudBundle\Tests\Functional\App\Entity\TestUser;
use Ecommit\CrudBundle\Tests\Functional\App\Form\Searcher\UserSearcher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class UserWithoutTraitController extends AbstractController
{
    protected function getCrud(string $sessionName, array $routeParameters = []): Crud
    {
        $em = $this->container->get('doctrine')->getManager();

        $queryBuilder = $em->getRepository(TestUser::class)
            ->createQueryBuilder('u')
            ->select('u');

        $crudConfig = new CrudConfig($sessionName);
        $crudConfig->addColumn('username', 'u.username', 'username', ['displayed_by_default' => false])
            ->addColumn('firstName', 'u.firstName', 'first_name')
            ->addColumn('lastName', 'u.lastName', 'last_name')
            ->setQueryBuilder($queryBuilder)
            ->setMaxPerPage([5, 5, 10, 50], 5)
            ->setDefaultSort('firstName', Crud::ASC)
            ->createSearchForm(new UserSearcher())
            ->setRoute('user_without_trait_ajax_crud', $routeParameters)
            ->setPersistentSettings(true);
        $crud = $this->container->get(CrudFactory::class)->create($crudConfig->getOptions());

        $request = $this->container->get('request_stack')->getCurrentRequest();
        if ($request->query->has('manual-reset')) {
            $crud->reset();
        }
        if ($request->query->has('manual-reset-sort')) {
            $crud->resetSort();
        }

        return $crud;
    }

    public function crudAction(Request $request, CrudResponseGenerator $crudResponseGenerator)
    {
        if ($request->query->has('test-before-and-after-build-query')) {
            return $crudResponseGenerator->getResponse($this->getCrud('user_without_trait_with_data', ['test-before-and-after-build-query' => 1]), [
                'template_generator' => fn (string $action) => sprintf('user/%s.html.twig', $action),
                'before_build' => function (Crud $crud, array $data) {
                    $data['test_before_after_build'] = 'BEFORE';

                    return $data;
                },
                'after_build' => function (Crud $crud, array $data) {
                    $data['test_before_after_build'] .= ' AFTER';

                    return $data;
                },
            ]);
        }

        return $crudResponseGenerator->getResponse($this->getCrud('user_without_trait'), [
            'template_generator' => fn (string $action) => sprintf('user/%s.html.twig', $action),
        ]);
    }

    public function ajaxCrudAction(Request $request, CrudResponseGenerator $crudResponseGenerator)
    {
        if ($request->query->has('test-before-and-after-build-query')) {
            return $crudResponseGenerator->getAjaxResponse($this->getCrud('user_without_trait_with_data', ['test-before-and-after-build-query' => 1]), [
                'template_generator' => fn (string $action) => sprintf('user/%s.html.twig', $action),
                'before_build' => function (Crud $crud, array $data) {
                    $data['test_before_after_build'] = 'BEFORE';

                    return $data;
                },
                'after_build' => function (Crud $crud, array $data) {
                    $data['test_before_after_build'] .= ' AFTER';

                    return $data;
                },
            ]);
        }

        return $crudResponseGenerator->getAjaxResponse($this->getCrud('user_without_trait'), [
            'template_generator' => fn (string $action) => sprintf('user/%s.html.twig', $action),
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

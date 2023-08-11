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

use Ecommit\CrudBundle\Controller\AbstractCrudController;
use Ecommit\CrudBundle\Crud\Crud;
use Ecommit\CrudBundle\Tests\Functional\App\Entity\TestUser;
use Ecommit\CrudBundle\Tests\Functional\App\Form\Searcher\UserSearcher;
use Symfony\Component\Translation\TranslatableMessage;

class UserController extends AbstractCrudController
{
    protected function getCrudOptions(): array
    {
        $em = $this->container->get('doctrine')->getManager();

        $queryBuilder = $em->getRepository(TestUser::class)
            ->createQueryBuilder('u')
            ->select('u');

        $crudConfig = $this->createCrudConfig(static::getCrudName())
            ->addColumn('username', 'u.username', 'username', ['displayed_by_default' => false])
            ->addColumn('firstName', 'u.firstName', 'first_name')
            ->addColumn('lastName', 'u.lastName', new TranslatableMessage('last_name'))
            ->setQueryBuilder($queryBuilder)
            ->setMaxPerPage([5, 10, 50], 5)
            ->setDefaultSort('firstName', Crud::ASC)
            ->createSearchForm(new UserSearcher())
            ->setRoute(static::getCrudRouteName(), static::getCrudRouteParameters())
            ->setPersistentSettings(static::getPersistentSettings());

        return $crudConfig->getOptions();
    }

    protected function beforeBuild(Crud $crud, array $data): array
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        if ($request->query->has('manual-reset')) {
            $crud->reset();
        }
        if ($request->query->has('manual-reset-sort')) {
            $crud->resetSort();
        }

        return $data;
    }

    public function getCrudName(): string
    {
        return 'user';
    }

    public function getCrudRouteName(): string
    {
        return 'user_ajax_crud';
    }

    public function getCrudRouteParameters(): array
    {
        return [];
    }

    public function getPersistentSettings(): bool
    {
        return false;
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
}

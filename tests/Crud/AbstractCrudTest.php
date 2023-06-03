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

namespace Ecommit\CrudBundle\Tests\Crud;

use Doctrine\Persistence\ManagerRegistry;
use Ecommit\CrudBundle\Crud\Crud;
use Ecommit\CrudBundle\Crud\CrudConfig;
use Ecommit\CrudBundle\Crud\CrudFilters;
use Ecommit\CrudBundle\Form\Filter\FilterInterface;
use Ecommit\CrudBundle\Tests\Functional\App\Entity\TestUser;
use Ecommit\CrudBundle\Tests\Functional\App\Form\Searcher\UserSearcher;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class AbstractCrudTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    protected function createCrud(array|CrudConfig $crudConfig, array $filters = [], mixed $sessionValue = null): Crud
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->any())
            ->method('get')
            ->willReturn($sessionValue);
        $session->expects($this->any())
            ->method('set');

        $request = new Request();
        $request->setSession($session);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')
            ->willReturn($request);

        $crudFilters = $this->createMock(CrudFilters::class);
        $crudFilters->method('has')->willReturnCallback(fn (string $name) => \array_key_exists($name, $filters) || static::getContainer()->get('ecommit_crud.filters')->has($name));
        $crudFilters->method('get')->willReturnCallback(function (string $name) use ($filters): FilterInterface {
            if (\array_key_exists($name, $filters)) {
                return $filters[$name];
            }

            return static::getContainer()->get('ecommit_crud.filters')->get($name);
        });

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(function ($name) use ($requestStack, $crudFilters) {
                if ('request_stack' === $name) {
                    return $requestStack;
                } elseif ('ecommit_crud.filters' === $name) {
                    return $crudFilters;
                }

                return static::getContainer()->get('ecommit_crud.locator')->get($name);
            });

        if ($crudConfig instanceof CrudConfig) {
            $crudConfig = $crudConfig->getOptions();
        }
        $crud = $this->getMockBuilder(Crud::class)
            ->setConstructorArgs([$crudConfig, $container])
            ->onlyMethods(['save'])
            ->getMock();

        return $crud;
    }

    protected function createValidCrudConfig(CrudConfig $crudConfig = null, bool $withSearcher = false): CrudConfig
    {
        $queryBuilder = self::getContainer()->get(ManagerRegistry::class)->getRepository(TestUser::class)
            ->createQueryBuilder('u')
            ->select('u');

        $crudConfig = ($crudConfig) ?: new CrudConfig('session_name');
        $crudConfig->addColumn(['id' => 'username', 'alias' => 'u.username',  'label' => 'username', 'displayed_by_default' => false])
            ->addColumn(['id' => 'firstName', 'alias' => 'u.firstName', 'label' => 'first_name'])
            ->setQueryBuilder($queryBuilder)
            ->setMaxPerPage([10, 50, 100], 50)
            ->setDefaultSort('username', Crud::DESC)
            ->setRoute('user_ajax_crud', ['param1' => 'val1'])
        ;
        if ($withSearcher) {
            $crudConfig->createSearchForm(new UserSearcher());
        }

        return $crudConfig;
    }
}

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

namespace Ecommit\CrudBundle\Tests\Form\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;
use Ecommit\CrudBundle\Crud\Crud;
use Ecommit\CrudBundle\Crud\CrudFactory;
use Ecommit\CrudBundle\Tests\Functional\App\Entity\EntityManyToOne;
use Ecommit\CrudBundle\Tests\Functional\App\Form\Searcher\EntityToManyToOneSearcher;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class AbstractFilterTest extends KernelTestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var FormFactoryInterface
     */
    protected $factory;

    /**
     * @var CrudFactory
     */
    protected $crudFactory;

    protected function setUp(): void
    {
        static::bootKernel();

        $this->em = static::getContainer()->get(ManagerRegistry::class)->getManager();
        $this->factory = static::getContainer()->get(FormFactoryInterface::class);

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->any())
            ->method('get')
            ->willReturn(null);
        $session->expects($this->any())
            ->method('set');

        $request = new Request();
        $request->setSession($session);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($name) use ($requestStack) {
                if ('request_stack' === $name) {
                    return $requestStack;
                }

                return static::getContainer()->get('ecommit_crud.locator')->get($name);
            });

        $this->crudFactory = new CrudFactory($container);
    }

    protected function tearDown(): void
    {
        $this->em = null;
        $this->factory = null;
        $this->crudFactory = null;
    }

    protected function createCrud(string $alias, $propertyData = null): Crud
    {
        $searcher = new EntityToManyToOneSearcher();
        $searcher->propertyName = $propertyData;

        $queryBuilder = $this->em->getRepository(EntityManyToOne::class)->createQueryBuilder('e');

        $crud = $this->crudFactory->create('session_name');
        $crud->addColumn('propertyName', $alias, 'label1')
            ->addColumn('id', 'e.id', 'id')
            ->setQueryBuilder($queryBuilder)
            ->setAvailableResultsPerPage([5, 5, 10, 50], 5)
            ->setDefaultSort('id', Crud::ASC)
            ->createSearchForm($searcher)
            ->setRoute('user_ajax_crud');

        return $crud;
    }

    protected function initCrudAndGetForm(Crud $crud): FormInterface
    {
        $crud->init();

        return $crud->getSearchForm()->getForm();
    }

    protected function initCrudAndGetFormView(Crud $crud): FormView
    {
        $crud->init();
        $crud->getSearchForm()->createFormView();

        return $crud->getSearchForm()->getForm();
    }

    protected function checkQueryBuilder(Crud $crud, ?string $whereExpected, array $parametersExpected = []): void
    {
        $crud->build();

        $queryBuilder = $crud->getQueryBuilder();
        if (null === $whereExpected) {
            $this->assertDoesNotMatchRegularExpression('/WHERE/', $queryBuilder->getDql());
        } else {
            $pattern = '/WHERE '.preg_quote($whereExpected, '/').' ORDER BY/i';
            $this->assertMatchesRegularExpression($pattern, $queryBuilder->getDql());
        }

        $parameters = [];
        /** @var Parameter $parameter */
        foreach ($queryBuilder->getParameters() as $parameter) {
            $parameters[$parameter->getName()] = $parameter->getValue();
        }
        $this->assertEquals($parametersExpected, $parameters);
    }
}

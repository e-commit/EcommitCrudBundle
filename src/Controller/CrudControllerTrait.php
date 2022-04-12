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

namespace Ecommit\CrudBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Ecommit\CrudBundle\Crud\Crud;
use Ecommit\CrudBundle\Crud\CrudFactory;
use Ecommit\CrudBundle\Crud\CrudResponseGenerator;
use Symfony\Component\HttpFoundation\Response;

trait CrudControllerTrait
{
    abstract protected function getCrud(): Crud;

    abstract protected function getTemplateName(string $action): string;

    final protected function createCrud(string $sessionName): Crud
    {
        return $this->container->get(CrudFactory::class)->create($sessionName);
    }

    public function getCrudResponse(): Response
    {
        return $this->container->get(CrudResponseGenerator::class)->getResponse($this->getCrud(), $this->getCrudOptions());
    }

    public function getAjaxCrudResponse(): Response
    {
        return $this->container->get(CrudResponseGenerator::class)->getAjaxResponse($this->getCrud(), $this->getCrudOptions());
    }

    protected function getCrudOptions(): array
    {
        return [
            'template_generator' => fn (string $action) => $this->getTemplateName($action),
            'before_build_query' => fn (Crud $crud, array $data) => $this->beforeBuildQuery($crud, $data),
            'after_build_query' => fn (Crud $crud, array $data) => $this->afterBuildQuery($crud, $data),
        ];
    }

    protected function beforeBuildQuery(Crud $crud, array $data): array
    {
        return $data;
    }

    protected function afterBuildQuery(Crud $crud, array $data): array
    {
        return $data;
    }

    protected static function getCrudRequiredServices(): array
    {
        return [
            CrudFactory::class,
            CrudResponseGenerator::class,
            'doctrine' => '?'.ManagerRegistry::class,
        ];
    }
}

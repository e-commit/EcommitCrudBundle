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
use Ecommit\CrudBundle\Crud\CrudConfig;
use Ecommit\CrudBundle\Crud\CrudFactory;
use Ecommit\CrudBundle\Crud\CrudResponseGenerator;
use Symfony\Component\HttpFoundation\Response;

trait CrudControllerTrait
{
    abstract protected function getCrudOptions(): array;

    abstract protected function getTemplateName(string $action): string;

    protected function createCrudConfig(string $sessionName = null): CrudConfig
    {
        return new CrudConfig($sessionName);
    }

    final protected function createCrud(array $options): Crud
    {
        return $this->container->get(CrudFactory::class)->create($options);
    }

    public function getCrudResponse(): Response
    {
        $crud = $this->createCrud($this->getCrudOptions());

        return $this->container->get(CrudResponseGenerator::class)->getResponse($crud, $this->getCrudResponseGeneratorOptions());
    }

    public function getAjaxCrudResponse(): Response
    {
        $crud = $this->createCrud($this->getCrudOptions());

        return $this->container->get(CrudResponseGenerator::class)->getAjaxResponse($crud, $this->getCrudResponseGeneratorOptions());
    }

    protected function getCrudResponseGeneratorOptions(): array
    {
        return [
            'template_generator' => fn (string $action) => $this->getTemplateName($action),
            'before_build' => fn (Crud $crud, array $data) => $this->beforeBuild($crud, $data),
            'after_build' => fn (Crud $crud, array $data) => $this->afterBuild($crud, $data),
        ];
    }

    protected function beforeBuild(Crud $crud, array $data): array
    {
        return $data;
    }

    protected function afterBuild(Crud $crud, array $data): array
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

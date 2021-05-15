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

namespace Ecommit\CrudBundle\Tests\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\DataTransformerInterface;

abstract class AbstractEntityTransformerTest extends KernelTestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    protected function setUp(): void
    {
        static::bootKernel();
        $this->em = static::$container->get(ManagerRegistry::class)->getManager();
    }

    protected function tearDown(): void
    {
        $this->em = null;
    }

    abstract protected function createTransformer(...$args): DataTransformerInterface;
}

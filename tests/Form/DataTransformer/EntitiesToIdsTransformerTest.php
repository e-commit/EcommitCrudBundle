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

use Doctrine\Common\Collections\ArrayCollection;
use Ecommit\CrudBundle\Form\DataTransformer\Entity\EntitiesToIdsTransformer;
use Ecommit\CrudBundle\Tests\Functional\App\Entity\EntityManyToOne;
use Ecommit\CrudBundle\Tests\Functional\App\Entity\Tag;
use Symfony\Component\Form\DataTransformerInterface;

class EntitiesToIdsTransformerTest extends EntitiesToChoicesTransformerTest
{
    protected function createTransformer(...$args): DataTransformerInterface
    {
        return new EntitiesToIdsTransformer(...$args);
    }

    public function getTestTransformProvider(): array
    {
        $closure = function (Tag $tag) {
            return sprintf('name: %s', $tag->getName());
        };

        return [
            [null, ['1', '3']], // Choice label: null
            ['name', ['1', '3']], // Choice label: property
            [$closure, ['1', '3']], // Choice label: closure
        ];
    }

    public function testTransformWithChoiceLabelError(): void
    {
        $entity = $this->em->getRepository(EntityManyToOne::class)->find(1);

        $collection = new ArrayCollection();
        $collection->add($entity);

        $queryBuilder = $this->em->getRepository(EntityManyToOne::class)->createQueryBuilder('e')->select('e');
        $transformer = $this->createTransformer($queryBuilder, 'id', null, false, 10);

        // No throws exception
        $this->assertSame(['1'], $transformer->transform($collection));
    }
}

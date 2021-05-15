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

namespace Ecommit\CrudBundle\Tests\Functional\App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Ecommit\CrudBundle\Tests\Functional\App\Entity\EntityManyToOne;
use Ecommit\CrudBundle\Tests\Functional\App\Entity\Tag;

class EntityManyToOneFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $dataSet = [
            [1, 'entity1', 2],
            [2, 'entity2', 3],
            [3, 'entity3', 4],
            [4, 'entity4', 5],
            [5, 'entity5', 5],
        ];

        $tagRepository = $manager->getRepository(Tag::class);
        foreach ($dataSet as $data) {
            $entity = new EntityManyToOne($data[0], $data[1], $tagRepository->find($data[2]));
            $manager->persist($entity);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            TagFixtures::class,
        ];
    }
}

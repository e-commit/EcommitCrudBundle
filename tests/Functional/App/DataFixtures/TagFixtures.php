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
use Doctrine\Persistence\ObjectManager;
use Ecommit\CrudBundle\Tests\Functional\App\Entity\Tag;

class TagFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $dataSet = [
            [1, 'tag1'],
            [2, 'tag2'],
            [3, '3'],
            [4, 'tag_name'],
            [5, 'tag_name'],
        ];

        foreach ($dataSet as $data) {
            $tag = new Tag($data[0], $data[1]);
            $manager->persist($tag);
        }

        $manager->flush();
    }
}

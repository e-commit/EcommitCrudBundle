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
use Ecommit\CrudBundle\Crud\Crud;
use Ecommit\CrudBundle\Entity\UserCrudSettings;
use Ecommit\CrudBundle\Tests\Functional\App\Entity\TestUser;

class TestUserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $dataSet = [
            ['Eve', 'Reste'],
            ['Henri', 'Poste'],
            ['Henri', 'Plait'],
            ['Jean', 'Serrien'],
            ['Clément', 'Tine'],
            ['Aude', 'Javel'],
            ['Yvon', 'Embavé'],
            ['Judie', 'Cieux'],
            ['Paul', 'Ochon'],
            ['Sarah', 'Pelle'],
            ['Thierry', 'Gollo'],
        ];

        foreach ($dataSet as $data) {
            $user = new TestUser();
            $user->setUsername($data[0].$data[1])
                ->setFirstName($data[0])
                ->setLastName($data[1]);
            $manager->persist($user);
            $this->addReference('user_'.$data[0].$data[1], $user);
        }

        $userCrudSettings = new UserCrudSettings();
        $userCrudSettings->setUser($this->getReference('user_EveReste'))
            ->setCrudName('crud_persistent_settings')
            ->setDisplayedColumns(['username', 'firstName'])
            ->setResultsDisplayed(50)
            ->setSort('firstName')
            ->setSense(Crud::DESC);
        $manager->persist($userCrudSettings);

        $manager->flush();
    }
}

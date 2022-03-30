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

namespace Ecommit\CrudBundle\Tests\Functional\App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ecommit\CrudBundle\Entity\UserCrudInterface;

#[ORM\Entity]
#[ORM\Table(name: 'user')]
class TestUser implements UserCrudInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $userId;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    protected $username;

    #[ORM\Column(type: 'string', length: 30)]
    protected $firstName;

    #[ORM\Column(type: 'string', length: 30)]
    protected $lastName;

    /*
     * Getters / Setters (auto-generated)
     */

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }
}

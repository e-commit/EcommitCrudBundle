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
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'user')]
class TestUser implements UserCrudInterface, UserInterface, PasswordAuthenticatedUserInterface
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
     * Methods
     */

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->username;
    }

    /**
     * SF < 6.
     */
    public function getSalt(): string
    {
        return '';
    }

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

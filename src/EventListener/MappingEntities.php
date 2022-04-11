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

namespace Ecommit\CrudBundle\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

final class MappingEntities
{
    protected $isLoad = false;

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $metadata = $eventArgs->getClassMetadata();

        if ($metadata->isMappedSuperclass) {
            return;
        }

        $className = $metadata->getName();
        if (!$this->isLoad && is_subclass_of($className, 'Ecommit\CrudBundle\Entity\UserCrudInterface')) {
            $userCrudSettingsMetadata = $eventArgs->getEntityManager()->getMetadataFactory()->getMetadataFor('Ecommit\CrudBundle\Entity\UserCrudSettings');
            $this->mappUserCrudSettings($userCrudSettingsMetadata, $metadata);
        }
        if (!$this->isLoad && 'Ecommit\CrudBundle\Entity\UserCrudSettings' === $className) {
            $userMetadata = $eventArgs->getEntityManager()->getMetadataFactory()->getMetadataFor('Ecommit\CrudBundle\Entity\UserCrudInterface');
            $this->mappUserCrudSettings($metadata, $userMetadata);
        }
    }

    protected function mappUserCrudSettings(ClassMetadataInfo $userCrudSettingsMetadata, ClassMetadataInfo $userMetadata): void
    {
        $this->isLoad = true;

        $userCrudSettingsMetadata->setAssociationOverride(
            'user',
            [
                'targetEntity' => $userMetadata->getName(),
                'fieldName' => 'user',
                'id' => true,
                'joinColumns' => [[
                    'name' => 'user_id',
                    'referencedColumnName' => $userMetadata->getSingleIdentifierColumnName(),
                    'onDelete' => 'CASCADE',
                ]],
            ]
        );
    }
}

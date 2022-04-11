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

namespace Ecommit\CrudBundle\Form\DataTransformer\Entity;

use Symfony\Component\Form\Exception\UnexpectedTypeException;

class EntityToIdTransformer extends EntityToChoiceTransformer
{
    public function transform(mixed $entity)
    {
        if (null === $entity || '' === $entity) {
            return null;
        }

        if (!\is_object($entity)) {
            throw new UnexpectedTypeException($entity, 'object');
        }

        $identifier = (string) $this->accessor->getValue($entity, $this->identifier);

        return $identifier;
    }
}

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

use Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class EntityToChoiceTransformer extends AbstractEntityTransformer
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
        $label = $this->extractLabel($entity);

        $results[$identifier] = $label;

        return $results;
    }

    public function reverseTransform(mixed $identifier)
    {
        if ('' === $identifier || null === $identifier) {
            return null;
        }

        if (!\is_scalar($identifier)) {
            throw new TransformationFailedException('Value is not scalar');
        }

        $hash = $this->getCacheHash($identifier);
        if (\array_key_exists($hash, $this->cachedResults)) {
            $entity = $this->cachedResults[$hash];
        } else {
            // Result not in cache

            try {
                $queryBuilderLoader = new ORMQueryBuilderLoader($this->queryBuilder);
                $entities = $queryBuilderLoader->getEntitiesByIds($this->identifier, [$identifier]);
            } catch (\Exception $exception) {
                throw new TransformationFailedException('Tranformation: Query Error');
            }
            if (1 !== \count($entities)) {
                if ($this->throwExceptionIfValueNotFoundInReverse) {
                    throw new TransformationFailedException(sprintf('The entity with key "%s" could not be found or is not unique', $identifier));
                }

                return null;
            }

            $entity = $entities[0];
            $this->cachedResults[$hash] = $entity; // Saves result in cache
        }

        return $entity;
    }
}

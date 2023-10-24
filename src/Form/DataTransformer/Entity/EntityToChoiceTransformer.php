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
    public function transform(mixed $value): mixed
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!\is_object($value)) {
            throw new UnexpectedTypeException($value, 'object');
        }

        $identifier = (string) $this->accessor->getValue($value, $this->identifier);
        $label = $this->extractLabel($value);

        $results = [];
        $results[$identifier] = $label;

        return $results;
    }

    public function reverseTransform(mixed $value): mixed
    {
        if ('' === $value || null === $value) {
            return null;
        }

        if (!\is_scalar($value)) {
            throw new TransformationFailedException('Value is not scalar');
        }

        $hash = $this->getCacheHash($value);
        if (\array_key_exists($hash, $this->cachedResults)) {
            $entity = $this->cachedResults[$hash];
        } else {
            // Result not in cache

            try {
                $queryBuilderLoader = new ORMQueryBuilderLoader($this->queryBuilder);
                $entities = $queryBuilderLoader->getEntitiesByIds($this->identifier, [$value]);
            } catch (\Exception $exception) {
                throw new TransformationFailedException('Tranformation: Query Error');
            }
            if (1 !== \count($entities)) {
                if ($this->throwExceptionIfValueNotFoundInReverse) {
                    throw new TransformationFailedException(sprintf('The entity with key "%s" could not be found or is not unique', (string) $value));
                }

                return null;
            }

            $entity = $entities[0];
            $this->cachedResults[$hash] = $entity; // Saves result in cache
        }

        return $entity;
    }
}

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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;
use Ecommit\ScalarValues\ScalarValues;
use Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class EntitiesToChoicesTransformer extends AbstractEntityTransformer
{
    public function __construct(QueryBuilder $queryBuilder, string $identifier, mixed $choiceLabel, bool $throwExceptionIfValueNotFoundInReverse, protected int $maxResults)
    {
        parent::__construct($queryBuilder, $identifier, $choiceLabel, $throwExceptionIfValueNotFoundInReverse);
    }

    public function transform(mixed $value): mixed
    {
        if (null === $value) {
            return [];
        }

        if (!($value instanceof Collection)) {
            throw new UnexpectedTypeException($value, Collection::class);
        }

        $results = [];
        foreach ($value as $entity) {
            $identifier = (string) $this->accessor->getValue($entity, $this->identifier);
            $label = $this->extractLabel($entity);

            $results[$identifier] = $label;
        }

        return $results;
    }

    public function reverseTransform(mixed $value): mixed
    {
        $collection = new ArrayCollection();

        if ('' === $value || null === $value) {
            return $collection;
        }

        if (!\is_array($value)) {
            throw new TransformationFailedException('This collection must be an array');
        }
        $value = ScalarValues::filterScalarValues($value);

        if (0 === \count($value)) {
            return $collection;
        }
        $value = array_unique($value);
        if (\count($value) > $this->maxResults) {
            throw new TransformationFailedException(sprintf('This collection should contain %s elements or less.', $this->maxResults));
        }

        $hash = $this->getCacheHash($value);
        if (\array_key_exists($hash, $this->cachedResults)) {
            $collection = $this->cachedResults[$hash];
        } else {
            // Result not in cache

            try {
                $queryBuilderLoader = new ORMQueryBuilderLoader($this->queryBuilder);

                foreach ($queryBuilderLoader->getEntitiesByIds($this->identifier, $value) as $entity) {
                    $collection->add($entity);
                }
            } catch (\Exception $exception) {
                throw new TransformationFailedException('Tranformation: Query Error');
            }

            if ($collection->count() !== \count($value) && $this->throwExceptionIfValueNotFoundInReverse) {
                throw new TransformationFailedException('Entities not found');
            }

            $this->cachedResults[$hash] = $collection; // Saves result in cache
        }

        return $collection;
    }
}

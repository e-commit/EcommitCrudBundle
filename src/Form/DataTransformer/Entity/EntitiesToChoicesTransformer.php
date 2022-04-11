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

    public function transform(mixed $collection)
    {
        if (null === $collection) {
            return [];
        }

        if (!($collection instanceof Collection)) {
            throw new UnexpectedTypeException($collection, Collection::class);
        }

        $results = [];
        foreach ($collection as $entity) {
            $identifier = (string) $this->accessor->getValue($entity, $this->identifier);
            $label = $this->extractLabel($entity);

            $results[$identifier] = $label;
        }

        return $results;
    }

    public function reverseTransform(mixed $identifiers)
    {
        $collection = new ArrayCollection();

        if ('' === $identifiers || null === $identifiers) {
            return $collection;
        }

        if (!\is_array($identifiers)) {
            throw new TransformationFailedException('This collection must be an array');
        }
        $identifiers = ScalarValues::filterScalarValues($identifiers);

        if (0 === \count($identifiers)) {
            return $collection;
        }
        $identifiers = array_unique($identifiers);
        if (\count($identifiers) > $this->maxResults) {
            throw new TransformationFailedException(sprintf('This collection should contain %s elements or less.', $this->maxResults));
        }

        $hash = $this->getCacheHash($identifiers);
        if (\array_key_exists($hash, $this->cachedResults)) {
            $collection = $this->cachedResults[$hash];
        } else {
            // Result not in cache

            try {
                $queryBuilderLoader = new ORMQueryBuilderLoader($this->queryBuilder);

                foreach ($queryBuilderLoader->getEntitiesByIds($this->identifier, $identifiers) as $entity) {
                    $collection->add($entity);
                }
            } catch (\Exception $exception) {
                throw new TransformationFailedException('Tranformation: Query Error');
            }

            if ($collection->count() !== \count($identifiers) && $this->throwExceptionIfValueNotFoundInReverse) {
                throw new TransformationFailedException('Entities not found');
            }

            $this->cachedResults[$hash] = $collection; // Saves result in cache
        }

        return $collection;
    }
}

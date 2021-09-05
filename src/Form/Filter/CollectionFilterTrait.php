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

namespace Ecommit\CrudBundle\Form\Filter;

use Ecommit\DoctrineUtils\QueryBuilderFilter;
use Ecommit\ScalarValues\ScalarValues;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

trait CollectionFilterTrait
{
    protected function getCollectionTypeOptions(array $options): array
    {
        $constraints = [];
        if ($options['multiple']) {
            $constraints = [
                new Assert\Count([
                    'min' => $options['min'],
                    'max' => $options['max'],
                ]),
            ];
        }

        return [
            'multiple' => $options['multiple'],
            'constraints' => $constraints,
        ];
    }

    protected function updateCollectionQueryBuilder($queryBuilder, string $property, $value, array $options): void
    {
        if (null === $value || '' === $value || [] === $value) {
            return;
        }

        $parameterName = 'value_collection'.str_replace(' ', '', $property);

        if ($options['multiple']) {
            if (!\is_array($value)) {
                $value = [$value];
            }
            $value = ScalarValues::filterScalarValues($value);
            if (\count($value) > $options['max'] || 0 === \count($value)) {
                return;
            }
            if ($options['min'] && \count($value) < $options['min']) {
                return;
            }
            QueryBuilderFilter::addMultiFilter($queryBuilder, QueryBuilderFilter::SELECT_IN, $value, $options['alias_search'], $parameterName);
        } else {
            if (!is_scalar($value)) {
                return;
            }
            $queryBuilder->andWhere(sprintf('%s = :%s', $options['alias_search'], $parameterName))
                ->setParameter($parameterName, $value);
        }
    }

    protected function configureCollectionOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'multiple' => false,
            'min' => null,
            'max' => 1000,
        ]);
    }
}

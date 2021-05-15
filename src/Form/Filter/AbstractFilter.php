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

use Ecommit\CrudBundle\Crud\SearchFormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractFilter implements FilterInterface
{
    public function buildForm(SearchFormBuilder $builder, string $property, array $options): void
    {
    }

    public function updateQueryBuilder($queryBuilder, string $property, $value, array $options): void
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    public function supportsQueryBuilder(object $queryBuilder): bool
    {
        return $queryBuilder instanceof \Doctrine\ORM\QueryBuilder || $queryBuilder instanceof \Doctrine\DBAL\Query\QueryBuilder;
    }

    public static function getName(): string
    {
        return static::class;
    }

    protected function getTypeOptions(array $options, array $filterTypeOptions = []): array
    {
        $typeOptions = array_merge(
            [
                'required' => $options['required'],
                'label' => $options['label'],
            ],
            $filterTypeOptions
        );

        if ($options['autovalidate']) {
            $typeOptions['validation_groups'] = $options['validation_groups'];
        } elseif (isset($typeOptions['constraints'])) {
            unset($typeOptions['constraints']);
        }

        return array_merge(
            $typeOptions,
            $options['type_options']
        );
    }
}

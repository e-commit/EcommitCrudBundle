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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BooleanFilter extends AbstractFilter
{
    public const VALUE_TRUE = 'T';
    public const VALUE_FALSE = 'F';

    public function buildForm(SearchFormBuilder $builder, string $property, array $options): void
    {
        $typeOptions = $this->getTypeOptions($options, [
            'choices' => [
                'filter.true' => static::VALUE_TRUE,
                'filter.false' => static::VALUE_FALSE,
            ],
            'choice_translation_domain' => 'EcommitCrudBundle',
        ]);
        $builder->addField($property, ChoiceType::class, $typeOptions);
    }

    public function updateQueryBuilder(mixed $queryBuilder, string $property, mixed $value, array $options): void
    {
        if (null === $value || '' === $value || !\is_scalar($value)) {
            return;
        }

        $parameterTrueName = 'value_boolean_true_'.str_replace(' ', '', $property);
        $parameterFalseName = 'value_boolean_false_'.str_replace(' ', '', $property);

        if (static::VALUE_TRUE === $value) {
            $or = $queryBuilder->expr()->orX();
            $or->add(sprintf('%s = :%s', $options['alias_search'], $parameterTrueName));
            $queryBuilder->setParameter($parameterTrueName, $options['value_true']);
            if ($options['not_null_is_true']) {
                $or->add(sprintf('%s IS NOT NULL AND %s != :%s', $options['alias_search'], $options['alias_search'], $parameterFalseName));
                $queryBuilder->setParameter($parameterFalseName, $options['value_false']);
            }
            $queryBuilder->andWhere($or);
        } elseif (static::VALUE_FALSE === $value) {
            if (null === $options['value_false']) {
                $queryBuilder->andWhere(sprintf('%s IS NULL', $options['alias_search']));

                return;
            }

            $or = $queryBuilder->expr()->orX();
            $or->add(sprintf('%s = :%s', $options['alias_search'], $parameterFalseName));
            $queryBuilder->setParameter($parameterFalseName, $options['value_false']);
            if ($options['null_is_false']) {
                $or->add(sprintf('%s IS NULL', $options['alias_search']));
            }
            $queryBuilder->andWhere($or);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'value_true' => 1,
            'value_false' => 0,
            'not_null_is_true' => false,
            'null_is_false' => true,
        ]);
        $resolver->setAllowedTypes('value_true', ['string', 'integer']);
        $resolver->setAllowedTypes('value_false', ['null', 'string', 'integer']);
        $resolver->setAllowedTypes('not_null_is_true', 'boolean');
        $resolver->setAllowedTypes('null_is_false', 'boolean');
    }
}

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
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntegerFilter extends AbstractFilter
{
    public const GREATER_THAN = '>';
    public const GREATER_EQUAL = '>=';
    public const SMALLER_THAN = '<';
    public const SMALLER_EQUAL = '<=';
    public const EQUAL = '=';

    public function buildForm(SearchFormBuilder $builder, string $property, array $options): void
    {
        $typeOptions = $this->getTypeOptions($options);
        $builder->addField($property, IntegerType::class, $typeOptions);
    }

    public function updateQueryBuilder(mixed $queryBuilder, string $property, mixed $value, array $options): void
    {
        if (null === $value || '' === $value || !\is_scalar($value) || !$this->testNumberValue($value)) {
            return;
        }

        $parameterName = 'value_integer_'.str_replace(' ', '', $property);

        $queryBuilder->andWhere(sprintf('%s %s :%s', $options['alias_search'], $options['comparator'], $parameterName))
            ->setParameter($parameterName, $value);
    }

    protected function testNumberValue(mixed $value): bool
    {
        return \is_int($value) || (\is_string($value) && preg_match('/^\d+$/', $value));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            'comparator',
        ]);

        $resolver->setAllowedValues('comparator', [
            self::EQUAL,
            self::GREATER_EQUAL,
            self::GREATER_THAN,
            self::SMALLER_EQUAL,
            self::SMALLER_THAN,
        ]);
    }
}

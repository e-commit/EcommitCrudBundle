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
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateFilter extends AbstractFilter
{
    public const GREATER_THAN = '>';
    public const GREATER_EQUAL = '>=';
    public const SMALLER_THAN = '<';
    public const SMALLER_EQUAL = '<=';
    public const EQUAL = '=';

    public function buildForm(SearchFormBuilder $builder, string $property, array $options): void
    {
        $typeOptions = $this->getTypeOptions($options, [
            'input' => 'datetime',
            'widget' => 'choice',
        ]);
        $type = ($options['with_time']) ? DateTimeType::class : DateType::class;
        $builder->addField($property, $type, $typeOptions);
    }

    public function updateQueryBuilder(mixed $queryBuilder, string $property, mixed $value, array $options): void
    {
        if (null === $value || '' === $value || !$value instanceof \DateTimeInterface) {
            return;
        }

        if ($value instanceof \DateTime) {
            $value = \DateTimeImmutable::createFromMutable($value);
        }
        $parameterName = 'value_date_'.str_replace(' ', '', $property);

        switch ($options['comparator']) {
            case self::SMALLER_THAN:
            case self::GREATER_EQUAL:
                if (!$options['with_time']) {
                    $value = $value->setTime(0, 0, 0);
                }
                $value = $value->format('Y-m-d H:i:s');
                $queryBuilder->andWhere(
                    sprintf('%s %s :%s', $options['alias_search'], $options['comparator'], $parameterName)
                )
                    ->setParameter($parameterName, $value);
                break;
            case self::SMALLER_EQUAL:
            case self::GREATER_THAN:
                if (!$options['with_time']) {
                    $value = $value->setTime(23, 59, 59);
                }
                $value = $value->format('Y-m-d H:i:s');
                $queryBuilder->andWhere(
                    sprintf('%s %s :%s', $options['alias_search'], $options['comparator'], $parameterName)
                )
                    ->setParameter($parameterName, $value);
                break;
            default:
                $valueDateInf = $value;
                $valueDateSup = $value;
                if (!$options['with_time']) {
                    $valueDateInf = $valueDateInf->setTime(0, 0, 0);
                    $valueDateSup = $valueDateSup->setTime(23, 59, 59);
                }
                $valueDateInf = $valueDateInf->format('Y-m-d H:i:s');
                $valueDateSup = $valueDateSup->format('Y-m-d H:i:s');
                $parameterNameInf = 'value_date_inf_'.str_replace(' ', '', $property);
                $parameterNameSup = 'value_date_sup_'.str_replace(' ', '', $property);
                $queryBuilder->andWhere(
                    sprintf(
                        '%s >= :%s AND %s <= :%s',
                        $options['alias_search'],
                        $parameterNameInf,
                        $options['alias_search'],
                        $parameterNameSup
                    )
                )
                    ->setParameter($parameterNameInf, $valueDateInf)
                    ->setParameter($parameterNameSup, $valueDateSup);
                break;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'with_time' => false,
        ]);

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

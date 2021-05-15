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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TextFilter extends AbstractFilter
{
    public function buildForm(SearchFormBuilder $builder, string $property, array $options): void
    {
        $typeOptions = $this->getTypeOptions($options, [
            'constraints' => [
                new Assert\Length([
                    'min' => $options['min_length'],
                    'max' => $options['max_length'],
                ]),
            ],
        ]);
        $builder->addField($property, $options['type'], $typeOptions);
    }

    public function updateQueryBuilder($queryBuilder, string $property, $value, array $options): void
    {
        if (null === $value || '' === $value || !is_scalar($value)) {
            return;
        }

        $parameterName = 'value_text_'.str_replace(' ', '', $property);

        if ($options['must_begin'] && $options['must_end']) {
            $queryBuilder->andWhere(sprintf('%s = :%s', $options['alias_search'], $parameterName))
                ->setParameter($parameterName, $value);
        } else {
            $after = ($options['must_begin']) ? '' : '%';
            $before = ($options['must_end']) ? '' : '%';
            $value = addcslashes($value, '%_');
            $like = $after.$value.$before;
            $queryBuilder->andWhere($queryBuilder->expr()->like($options['alias_search'], ':'.$parameterName))
                ->setParameter($parameterName, $like);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'must_begin' => false,
            'must_end' => false,
            'min_length' => null,
            'max_length' => 255,
            'type' => TextType::class,
        ]);
    }
}

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
use Ecommit\CrudBundle\Form\DataTransformer\Entity\EntitiesToIdsTransformer;
use Ecommit\CrudBundle\Form\DataTransformer\Entity\EntityToIdTransformer;
use Ecommit\CrudBundle\Form\Type\EntityAjaxType;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityAjaxFilter extends AbstractFilter
{
    use CollectionFilterTrait;

    public function buildForm(SearchFormBuilder $builder, string $property, array $options): void
    {
        $typeOptions = $this->getTypeOptions($options, array_merge($this->getCollectionTypeOptions($options), [
            'class' => $options['class'],
            'route_name' => $options['route_name'],
            'route_params' => $options['route_params'],
            'max_elements' => $options['max'],
        ]));

        $builder->addField($property, EntityAjaxType::class, $typeOptions);

        $typeOptions = $builder->getField($property)->getOptions();
        if ($options['multiple']) {
            $builder->getField($property)->addModelTransformer(
                new ReversedTransformer(
                    new EntitiesToIdsTransformer($typeOptions['query_builder'], $typeOptions['identifier'], $typeOptions['choice_label'], false, $typeOptions['max_elements'])
                )
            );
        } else {
            $builder->getField($property)->addModelTransformer(
                new ReversedTransformer(
                    new EntityToIdTransformer($typeOptions['query_builder'], $typeOptions['identifier'], $typeOptions['choice_label'], false)
                )
            );
        }
    }

    public function updateQueryBuilder(mixed $queryBuilder, string $property, mixed $value, array $options): void
    {
        $this->updateCollectionQueryBuilder($queryBuilder, $property, $value, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $this->configureCollectionOptions($resolver);
        $resolver->setDefaults([
            'route_params' => [],
        ]);
        $resolver->setRequired([
            'class',
            'route_name',
        ]);
    }
}

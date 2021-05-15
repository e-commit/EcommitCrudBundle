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

class ChoiceFilter extends AbstractFilter
{
    use CollectionFilterTrait;

    public function buildForm(SearchFormBuilder $builder, string $property, array $options): void
    {
        $typeOptions = $this->getTypeOptions($options, array_merge($this->getCollectionTypeOptions($options), [
            'choices' => $options['choices'],
        ]));
        $builder->addField($property, $options['type'], $typeOptions);
    }

    public function updateQueryBuilder($queryBuilder, string $property, $value, array $options): void
    {
        $this->updateCollectionQueryBuilder($queryBuilder, $property, $value, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $this->configureCollectionOptions($resolver);
        $resolver->setDefaults([
            'choices' => [],
            'type' => ChoiceType::class,
        ]);
    }
}

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

use Doctrine\ORM\EntityRepository;
use Ecommit\CrudBundle\Crud\SearchFormBuilder;
use Ecommit\CrudBundle\Form\DataTransformer\Entity\EntitiesToIdsTransformer;
use Ecommit\CrudBundle\Form\DataTransformer\Entity\EntityToIdTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityFilter extends AbstractFilter
{
    use CollectionFilterTrait;

    public function buildForm(SearchFormBuilder $builder, string $property, array $options): void
    {
        $typeOptions = $this->getTypeOptions($options, array_merge($this->getCollectionTypeOptions($options), [
            'class' => $options['class'],
            'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('e'),
        ]));

        if (isset($typeOptions['choices'])) {
            throw new \Exception('"Choices" option is not allowed. Use "query_builder" option');
        }

        $builder->addField($property, EntityType::class, $typeOptions);

        $typeOptions = $builder->getField($property)->getOptions();

        $em = $typeOptions['em'];
        $identifiers = $em->getClassMetadata($options['class'])->getIdentifierFieldNames();
        if (1 !== \count($identifiers)) {
            throw new InvalidConfigurationException('Identifier not unique');
        }
        $identifier = $identifiers[0];

        if ($options['multiple']) {
            $builder->getField($property)->addModelTransformer(
                new ReversedTransformer(
                    new EntitiesToIdsTransformer($typeOptions['query_builder'], $identifier, $typeOptions['choice_label'], false, $options['max'])
                )
            );
        } else {
            $builder->getField($property)->addModelTransformer(
                new ReversedTransformer(
                    new EntityToIdTransformer($typeOptions['query_builder'], $identifier, $typeOptions['choice_label'], false)
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
        $resolver->setRequired([
            'class',
        ]);
    }
}

<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Form\Filter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Ecommit\JavascriptBundle\Form\Type\EntityNormalizerTrait;
use Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class FieldFilterEntity extends FieldFilterChoice implements FieldFilterDoctrineInterface
{
    use EntityNormalizerTrait;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolverInterface $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            array(
                'property' => null,
                'em' => null,
                'query_builder' => null,
                'identifier' => null,
            )
        );

        $resolver->setRequired(
            array(
                'class',
            )
        );

        $resolver->setNormalizers(
            array(
                'em' => $this->getEmNormalizer($this->registry),
                'query_builder' => $this->getQueryBuilderNormalizer(),
                'identifier' => $this->getIdentifierNormalizer(),
            )
        );
    }

    protected function configureTypeOptions($typeOptions)
    {
        $typeOptions = parent::configureTypeOptions($typeOptions);

        $queryBuilderLoader = new ORMQueryBuilderLoader(
            $this->options['query_builder'],
            $this->options['em'],
            $this->options['class']
        );

        $accessor = PropertyAccess::createPropertyAccessor();
        $choices = array();
        foreach ($queryBuilderLoader->getEntities() as $entity) {
            $id = $accessor->getValue($entity, $this->options['identifier']);
            $choices[$id] = $this->extractLabel($entity);
        }

        $typeOptions['choices'] = $choices;

        return $typeOptions;
    }

    /**
     * Extract property that should be used for displaying the entities as text in the HTML element
     * @param object $object
     * @throws \Exception
     */
    protected function extractLabel($object)
    {
        if ($this->options['property']) {
            $accessor = PropertyAccess::createPropertyAccessor();

            return $accessor->getValue($object, $this->options['property']);
        } elseif (method_exists($object, '__toString')) {
            return (string)$object;
        } else {
            throw new \Exception('"property" option or "__toString" method must be defined"');
        }
    }

    public function getRegistry()
    {
        return $this->registry;
    }

    public function setRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }
}

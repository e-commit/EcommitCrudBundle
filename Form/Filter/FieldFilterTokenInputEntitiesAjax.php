<?php
/**
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Form\Filter;

use Ecommit\CrudBundle\Form\Searcher\AbstractFormSearcher;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class FieldFilterTokenInputEntitiesAjax extends AbstractFieldFilter
{
    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'em' => null,
                'query_builder' => null,
                'property' => null,
                'identifier' => null,
                'url' => null, //Required in FormType if route_name is empty
                'route_name' => null,
                'route_params' => null,
                'min' => null,
                'max' => 99,
            )
        );

        $resolver->setRequired(
            array(
                'class',
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function configureTypeOptions($typeOptions)
    {
        foreach ($this->options as $optionName => $optionValue) {
            if (!empty($optionValue) && !in_array($optionName, array('validate', 'min'))) {
                $typeOptions[$optionName] = $optionValue;
            }
        }
        $typeOptions['input'] = 'key';

        return $typeOptions;
    }

    /**
     * {@inheritDoc}
     */
    public function addField(FormBuilder $formBuilder)
    {
        $formBuilder->add($this->property, 'ecommit_javascript_tokeninputentitiesajax', $this->typeOptions);

        return $formBuilder;
    }

    /**
     * {@inheritDoc}
     */
    protected function getAutoConstraints()
    {
        return array(
            new Assert\Count(
                array(
                    'min' => $this->options['min'],
                    'max' => $this->options['max'],
                )
            ),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function changeQuery($queryBuilder, AbstractFormSearcher $formData, $aliasSearch)
    {
        $value = $formData->get($this->property);
        $parameterName = 'value_choice' . str_replace(' ', '', $this->property);
        if (empty($value) || !is_array($value)) {
            return $queryBuilder;
        }

        if (count($value) > $this->options['max']) {
            return $queryBuilder;
        }
        if ($this->options['min'] && count($value) < $this->options['min']) {
            return $queryBuilder;
        }
        return $queryBuilder->andWhere($queryBuilder->expr()->in($aliasSearch, ':' . $parameterName))
            ->setParameter($parameterName, $value);
    }
}
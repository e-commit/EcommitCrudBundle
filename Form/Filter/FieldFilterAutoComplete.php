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

use Ecommit\CrudBundle\Crud\CrudColumn;
use Ecommit\CrudBundle\Form\Searcher\AbstractFormSearcher;
use Symfony\Component\Form\FormBuilder;

class FieldFilterAutoComplete extends FieldFilterAbstract
{
    protected $multiple;
    protected $limit = 50;
    
    /**
     * {@inheritDoc} 
     */
    public function __construct($column_id, $field_name, $options = array(), $field_options = array())
    {
        if(empty($options['class']))
        {
            throw new \Exception(\get_class($this).'"class" option is required');
        }
        $field_options['class'] = $options['class'];
        
        if(!empty($options['query_builder']))
        {
            $field_options['query_builder'] = $options['query_builder'];
        }
        
        if(!empty($options['url']))
        {
            $field_options['url'] = $options['url'];
        }
        else
        {
            throw new \Exception(\get_class($this).'"url" option is required');
        }
        
        if(!empty($options['root_alias']))
        {
            $field_options['root_alias'] = $options['root_alias'];
        }
        
        if(!empty($options['identifier']))
        {
            $field_options['identifier'] = $options['identifier'];
        }
        if(!empty($options['property']))
        {
            $field_options['property'] = $options['property'];
        }
        
        $this->multiple = isset($options['multiple'])? $options['multiple'] : false;
        if($this->multiple)
        {
            if(!empty($options['limit']))
            {
                $field_options['max'] = $options['limit'];
                $this->limit = $options['limit'];
            }
        }
        
        $field_options['input'] = 'key';
        
        parent::__construct($column_id, $field_name, $options, $field_options);
    }
    
    /**
     * {@inheritDoc} 
     */
    public function addField(FormBuilder $form_builder)
    {
        if($this->multiple)
        {
            $form_builder->add($this->field_name, 'ecommit_javascript_tokeninputentitiesajax', $this->field_options);
        }
        else
        {
            $form_builder->add($this->field_name, 'ecommit_javascript_jqueryautocompleteentityajax', $this->field_options);
        }
        return $form_builder;
    }

    /**
     * {@inheritDoc} 
     */
    public function changeQuery($query_builder, AbstractFormSearcher $form_data, CrudColumn $column)
    {
        $value_list = $form_data->get($this->field_name);
        $parameter_name = 'value_autocomplete'.str_replace(' ', '', $this->field_name);
        if(empty($value_list))
        {
            return $query_builder;
        }
        
        if($this->multiple)
        {
            if(!is_array($value_list))
            {
                $value_list = array($value_list);
            }
            if(count($value_list) > $this->limit)
            {
                return $query_builder; 
            }
            $query_builder->andWhere($query_builder->expr()->in($this->getAliasSearch($column), ':'.$parameter_name))
            ->setParameter($parameter_name, $value_list);
        }
        else
        {
            if(is_array($value_list))
            {
                return $query_builder;
            }
            $query_builder->andWhere(sprintf('%s = :%s',$this->getAliasSearch($column), $parameter_name))
            ->setParameter($parameter_name, $value_list);
        }
        return $query_builder;
    }
}
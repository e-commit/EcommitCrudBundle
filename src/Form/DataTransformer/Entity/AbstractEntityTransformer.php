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

namespace Ecommit\CrudBundle\Form\DataTransformer\Entity;

use Ecommit\ScalarValues\ScalarValues;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

abstract class AbstractEntityTransformer implements DataTransformerInterface
{
    protected $queryBuilder;
    protected $identifier;
    protected $choiceLabel;
    protected $throwExceptionIfValueNotFoundInReverse;

    /**
     * @var PropertyAccessor
     */
    protected $accessor;

    protected $cachedResults = [];

    public function __construct($queryBuilder, $identifier, $choiceLabel, bool $throwExceptionIfValueNotFoundInReverse)
    {
        $this->queryBuilder = $queryBuilder;
        $this->identifier = $identifier;
        $this->choiceLabel = $choiceLabel;
        $this->throwExceptionIfValueNotFoundInReverse = $throwExceptionIfValueNotFoundInReverse;
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    protected function getCacheHash($id): string
    {
        if (\is_array($id)) {
            $id = ScalarValues::filterScalarValues($id);
            $id = array_map(function ($child) {
                return (string) $child; //Converts ids from integer to string => Parameters for transform and reverse functions must be identicals
            }, $id);
            sort($id);
        } else {
            $id = (string) $id;
        }

        return md5(json_encode([
            spl_object_hash($this->queryBuilder),
            $this->identifier,
            $id,
        ]));
    }

    protected function extractLabel($entity): string
    {
        if ($this->choiceLabel) {
            if ($this->choiceLabel instanceof \Closure) {
                return (string) $this->choiceLabel->__invoke($entity);
            }

            return (string) $this->accessor->getValue($entity, $this->choiceLabel);
        } elseif (method_exists($entity, '__toString')) {
            return (string) $entity;
        }

        throw new \Exception('"choice_label" option or "__toString" method must be defined"');
    }
}

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

use Symfony\Component\Form\Exception\UnexpectedTypeException;

class EntityToIdTransformer extends EntityToChoiceTransformer
{
    public function transform(mixed $value)
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!\is_object($value)) {
            throw new UnexpectedTypeException($value, 'object');
        }

        $identifier = (string) $this->accessor->getValue($value, $this->identifier);

        return $identifier;
    }
}

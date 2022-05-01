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

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class EntitiesToIdsTransformer extends EntitiesToChoicesTransformer
{
    public function transform(mixed $value)
    {
        if (null === $value) {
            return [];
        }

        if (!($value instanceof Collection)) {
            throw new UnexpectedTypeException($value, Collection::class);
        }

        $results = [];
        foreach ($value as $entity) {
            $identifier = (string) $this->accessor->getValue($entity, $this->identifier);

            $results[] = $identifier;
        }

        return $results;
    }
}

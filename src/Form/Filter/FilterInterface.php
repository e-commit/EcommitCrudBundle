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
use Symfony\Component\OptionsResolver\OptionsResolver;

interface FilterInterface
{
    public function buildForm(SearchFormBuilder $builder, string $property, array $options): void;

    public function updateQueryBuilder(mixed $queryBuilder, string $property, mixed $value, array $options): void;

    public function configureOptions(OptionsResolver $resolver): void;

    public function supportsQueryBuilder(object $queryBuilder): bool;

    public static function getName(): string;
}

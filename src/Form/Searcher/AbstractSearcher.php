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

namespace Ecommit\CrudBundle\Form\Searcher;

use Ecommit\CrudBundle\Crud\SearchFormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractSearcher implements SearcherInterface
{
    public function buildForm(SearchFormBuilder $builder, array $options): void
    {
    }

    public function updateQueryBuilder(mixed $queryBuilder, array $options): void
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
    }
}

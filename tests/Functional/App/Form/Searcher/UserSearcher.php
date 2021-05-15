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

namespace Ecommit\CrudBundle\Tests\Functional\App\Form\Searcher;

use Ecommit\CrudBundle\Crud\SearchFormBuilder;
use Ecommit\CrudBundle\Form\Filter as Filter;
use Ecommit\CrudBundle\Form\Searcher\AbstractSearcher;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class UserSearcher extends AbstractSearcher
{
    public $username;

    public $firstName;

    public $lastName;

    public function buildForm(SearchFormBuilder $builder, array $options): void
    {
        $builder->addFilter('username', Filter\TextFilter::class);
        $builder->addFilter('firstName', Filter\TextFilter::class);
        $builder->addField('lastName', TextType::class, [
            'required' => false,
            'label' => 'last_name',
        ]);
    }

    public function updateQueryBuilder($queryBuilder, array $options): void
    {
        if (null !== $this->lastName) {
            $queryBuilder->andWhere('u.lastName = :lastName')
                ->setParameter('lastName', $this->lastName);
        }
    }
}

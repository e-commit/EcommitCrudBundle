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

namespace Ecommit\CrudBundle\Form\Type;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Ecommit\CrudBundle\Form\DataTransformer\Entity\EntitiesToChoicesTransformer;
use Ecommit\CrudBundle\Form\DataTransformer\Entity\EntityToChoiceTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class EntityAjaxType extends AbstractType
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var RouterInterface
     */
    protected $router;

    public function __construct(ManagerRegistry $registry, RouterInterface $router)
    {
        $this->registry = $registry;
        $this->router = $router;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['multiple']) {
            $builder->addViewTransformer(
                new EntitiesToChoicesTransformer($options['query_builder'], $options['identifier'], $options['choice_label'], true, $options['max_elements'])
            );
        } else {
            $builder->addViewTransformer(
                new EntityToChoiceTransformer($options['query_builder'], $options['identifier'], $options['choice_label'], true)
            );
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['url'] = $this->router->generate($options['route_name'], $options['route_params']);
        $view->vars['multiple'] = $options['multiple'];

        if ($options['multiple']) {
            $view->vars['full_name'] .= '[]';
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            'class',
            'route_name',
        ]);
        $resolver->setDefaults([
            'compound' => false,
            'multiple' => false,
            'em' => null,
            'query_builder' => null,
            'choice_label' => null,
            'route_params' => [],
            'max_elements' => 10000,
            'identifier' => null, // Internal
        ]);

        $emNormalizer = function (Options $options, ?string $em): ObjectManager {
            if (null !== $em) {
                return $this->registry->getManager($em);
            }

            $em = $this->registry->getManagerForClass($options['class']);
            if (null === $em) {
                throw new RuntimeException(sprintf('Class "%s" : Entity manager not found', $options['class']));
            }

            return $em;
        };
        $resolver->setNormalizer('em', $emNormalizer);

        $queryBuilderNormalizer = function (Options $options, null|QueryBuilder|\Closure $queryBuilder): QueryBuilder {
            $em = $options['em'];
            $class = $options['class'];

            if (null === $queryBuilder) {
                $queryBuilder = $em->createQueryBuilder()
                    ->from($class, 'c')
                    ->select('c');
            }

            if ($queryBuilder instanceof \Closure) {
                $queryBuilder = $queryBuilder($em->getRepository($class));
            }

            return $queryBuilder;
        };
        $resolver->setNormalizer('query_builder', $queryBuilderNormalizer);

        $identifierNormalizer = function (Options $options, ?string $identifier): string {
            if (null !== $identifier) {
                return $identifier;
            }

            $em = $options['em'];
            $identifiers = $em->getClassMetadata($options['class'])->getIdentifierFieldNames();
            if (1 !== \count($identifiers)) {
                throw new InvalidConfigurationException('Identifier not unique');
            }

            return $identifiers[0];
        };
        $resolver->setNormalizer('identifier', $identifierNormalizer);
    }

    public function getBlockPrefix()
    {
        return 'ecommit_crud_entity_ajax';
    }
}

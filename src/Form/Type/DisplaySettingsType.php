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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;

class DisplaySettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('resultsPerPage', ChoiceType::class, [
            'choices' => array_flip($options['results_per_page_choices']),
            'label' => 'display_settings.results_per_page',
            'translation_domain' => 'EcommitCrudBundle',
            'choice_translation_domain' => false,
            'constraints' => new NotBlank(),
        ]);

        $builder->add('displayedColumns', ChoiceType::class, [
            'choices' => array_flip($options['columns_choices']),
            'multiple' => true,
            'expanded' => true,
            'label' => 'display_settings.displayed_columns',
            'translation_domain' => 'EcommitCrudBundle',
            'choice_translation_domain' => 'messages',
            'constraints' => [new NotBlank(), new Count(['min' => 1])],
        ]);

        $builder->add('reset', ButtonType::class, [
            'label' => 'display_settings.reset_display_settings',
            'translation_domain' => 'EcommitCrudBundle',
            'attr' => [
                'data-ec-crud-toggle' => 'display-settings-reset',
                'data-reset-url' => $options['reset_settings_url'],
            ],
        ]);

        $builder->add('save', SubmitType::class, [
            'label' => 'display_settings.save',
            'translation_domain' => 'EcommitCrudBundle',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);

        $resolver->setRequired([
            'results_per_page_choices',
            'columns_choices',
            'reset_settings_url',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'crud_display_settings';
    }
}

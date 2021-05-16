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

namespace Ecommit\CrudBundle\Twig;

use Ecommit\CrudBundle\Crud\Crud;
use Ecommit\CrudBundle\Paginator\AbstractPaginator;
use Symfony\Component\Form\FormRendererInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CrudExtension extends AbstractExtension
{
    /**
     * @var FormRendererInterface
     */
    protected $formRenderer;

    protected $theme;
    protected $iconTheme;

    protected $lastTdValues = [];

    public function __construct(FormRendererInterface $formRenderer, string $theme, string $iconTheme)
    {
        $this->formRenderer = $formRenderer;
        $this->theme = $theme;
        $this->iconTheme = $iconTheme;
    }

    public function getName()
    {
        return 'ecommit_crud_crud_extension';
    }

    public function getFunctions()
    {
        return [
            new TwigFunction(
                'paginator_links',
                [$this, 'paginatorLinks'],
                [
                    'needs_environment' => true,
                    'is_safe' => ['html'],
                ]
            ),
            new TwigFunction(
                'crud_paginator_links',
                [$this, 'crudPaginatorLinks'],
                [
                    'needs_environment' => true,
                    'is_safe' => ['html'],
                ]
            ),
            new TwigFunction(
                'crud_th',
                [$this, 'th'],
                [
                    'needs_environment' => true,
                    'is_safe' => ['html'],
                ]
            ),
            new TwigFunction(
                'crud_td',
                [$this, 'td'],
                [
                    'needs_environment' => true,
                    'is_safe' => ['html'],
                ]
            ),
            new TwigFunction(
                'crud_display_settings',
                [$this, 'displaySettings'],
                [
                    'needs_environment' => true,
                    'is_safe' => ['all'],
                ]
            ),
            new TwigFunction(
                'crud_search_form_start',
                [$this, 'searchFormStart'],
                [
                    'needs_environment' => true,
                    'is_safe' => ['all'],
                ]
            ),
            new TwigFunction(
                'crud_search_form_submit',
                [$this, 'searchFormSubmit'],
                [
                    'needs_environment' => true,
                    'is_safe' => ['all'],
                ]
            ),
            new TwigFunction(
                'crud_search_form_reset',
                [$this, 'searchFormReset'],
                [
                    'needs_environment' => true,
                    'is_safe' => ['all'],
                ]
            ),
            new TwigFunction(
                'form_start_ajax',
                [$this, 'formStartAjax'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'ajax_attributes',
                [$this, 'ajaxAttributes'],
                [
                    'needs_environment' => true,
                    'is_safe' => ['html'],
                ]
            ),
            new TwigFunction(
                'crud_icon',
                [$this, 'crudIcon'],
                [
                    'needs_environment' => true,
                    'is_safe' => ['html'],
                ]
            ),
        ];
    }

    /**
     * Returns links paginator.
     *
     * @param string $routeName   Route name
     * @param array  $routeParams Route parameters
     * @param array  $options     Options:
     *                            * ajax_options: Ajax Options. If null, Ajax is not used. Default: null
     *                            * attribute_page: Attribute inside url. Default: page
     *                            * type: Type of links paginator: elastic (all links) or sliding. Default: sliding
     *                            * max_pages_before: Max links before current page (only if sliding type is used). Default: 3
     *                            * max_pages_after: Max links after current page (only if sliding type is used). Default: 3
     *                            * nav_attr: "nav" attributes
     *                            * ul_attr: "ul" attributes
     *                            * li_attr: "li" attributes for each page type (sub arrays: first_page, previous_page, current_page, next_page, last_page, other_page)
     *                            * a_attr: "a" CSS attributes for each page type (sub arrays: first_page, previous_page, current_page, next_page, last_page, other_page)
     *                            * render: Template used for generation. If null, default template is used
     */
    public function paginatorLinks(Environment $environment, AbstractPaginator $paginator, string $routeName, array $routeParams = [], array $options = []): string
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'ajax_options' => null,
            'attribute_page' => 'page',
            'type' => 'sliding',
            'max_pages_before' => 3,
            'max_pages_after' => 3,
            'nav_attr' => [],
            'ul_attr' => [],
            'li_attr' => function (OptionsResolver $liResolver): void {
                $liResolver->setDefaults([
                    'first_page' => [],
                    'previous_page' => [],
                    'current_page' => [],
                    'next_page' => [],
                    'last_page' => [],
                    'other_page' => [],
                ]);
                foreach (['first_page', 'previous_page', 'current_page', 'next_page', 'last_page', 'other_page'] as $option) {
                    $liResolver->setAllowedTypes($option, 'array');
                }
            },
            'a_attr' => function (OptionsResolver $aResolver): void {
                $aResolver->setDefaults([
                    'first_page' => [],
                    'previous_page' => [],
                    'current_page' => [],
                    'next_page' => [],
                    'last_page' => [],
                    'other_page' => [],
                ]);
                foreach (['first_page', 'previous_page', 'current_page', 'next_page', 'last_page', 'other_page'] as $option) {
                    $aResolver->setAllowedTypes($option, 'array');
                }
            },
            'render' => null,
            'theme' => $this->theme,
            'block' => 'paginator_links',
        ]);
        $resolver->setAllowedTypes('ajax_options', ['null', 'array']);
        $resolver->setAllowedTypes('max_pages_before', 'int');
        $resolver->setAllowedTypes('max_pages_after', 'int');
        $resolver->setAllowedValues('type', ['sliding', 'elastic']);
        $options = $resolver->resolve($options);

        if ($options['render']) {
            return $environment->render($options['render'], [
                'paginator' => $paginator,
                'routeName' => $routeName,
                'routeParams' => $routeParams,
                'options' => $options,
            ]);
        }

        $pages = [
            'first' => (1 !== $paginator->getPage()) ? 1 : null,
            'previous' => (1 !== $paginator->getPage()) ? $paginator->getPreviousPage() : null,
            'before_current' => [],
            'current' => $paginator->getPage(),
            'after_current' => [],
            'next' => ($paginator->getPage() !== $paginator->getLastPage()) ? $paginator->getNextPage() : null,
            'last' => ($paginator->getPage() !== $paginator->getLastPage()) ? $paginator->getLastPage() : null,
        ];

        //Pages before the current page
        $limit = ('sliding' == $options['type']) ? $paginator->getPage() - $options['max_pages_before'] : 1;
        for ($page = $limit; $page < $paginator->getPage(); ++$page) {
            if ($page <= $paginator->getLastPage() && $page >= $paginator->getFirstPage()) {
                $pages['before_current'][] = $page;
            }
        }

        //Pages after the current page
        $limit = ('sliding' == $options['type']) ? $paginator->getPage() + $options['max_pages_after'] : $paginator->getLastPage();
        for ($page = $paginator->getPage() + 1; $page <= $limit; ++$page) {
            if ($page <= $paginator->getLastPage() && $page >= $paginator->getFirstPage()) {
                $pages['after_current'][] = $page;
            }
        }

        return $this->renderBlock($environment, $options['theme'], $options['block'], array_merge($options, [
            'paginator' => $paginator,
            'pages' => $pages,
            'route_name' => $routeName,
            'route_params' => $routeParams,
            'options' => $options,
        ]));
    }

    /**
     * Returns CRUD links paginator.
     *
     * @see CrudExtension::paginatorLinks()
     */
    public function crudPaginatorLinks(Environment $environment, Crud $crud, array $options = []): string
    {
        if (!isset($options['ajax_options']['update'])) {
            $options['ajax_options']['update'] = '#'.$crud->getDivIdList();
        }

        return $this->paginatorLinks($environment, $crud->getPaginator(), $crud->getRouteName(), $crud->getRouteParams(), $options);
    }

    /**
     * Returns CRUD th tag.
     *
     * @param string $columnId Column to display
     * @param array  $options  Options:
     *                         * ajax_options: Ajax Options. If null, Ajax is not used. Default: null
     *                         * label: Th label. If null, CRUD configuration is used
     *                         * th_attr: "th" attributes
     *                         * a_attr: "a" attributes
     *                         * render: Template used for generation. If null, default template is used
     */
    public function th(Environment $environment, string $columnId, Crud $crud, array $options = []): string
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'ajax_options' => null,
            'label' => null,
            'th_attr' => function (OptionsResolver $thResolver): void {
                $thResolver->setDefaults([
                    'not_sortable' => [],
                    'sortable_active_asc' => [],
                    'sortable_active_desc' => [],
                    'sortable_not_active' => [],
                ]);
                foreach (['not_sortable', 'sortable_active_asc', 'sortable_active_desc', 'sortable_not_active'] as $option) {
                    $thResolver->setAllowedTypes($option, 'array');
                }
            },
            'a_attr' => function (OptionsResolver $aResolver): void {
                $aResolver->setDefaults([
                    'sortable_active_asc' => [],
                    'sortable_active_desc' => [],
                    'sortable_not_active' => [],
                ]);
                foreach (['sortable_active_asc', 'sortable_active_desc', 'sortable_not_active'] as $option) {
                    $aResolver->setAllowedTypes($option, 'array');
                }
            },
            'render' => null,
            'theme' => $this->theme,
            'block' => 'th',
        ]);
        $resolver->setAllowedTypes('ajax_options', ['null', 'array']);
        $resolver->setAllowedTypes('label', ['null', 'string']);
        $options = $resolver->resolve($options);

        //If the column is not to be shown, returns empty
        $sessionValues = $crud->getSessionValues();
        if (!\in_array($columnId, $sessionValues->displayedColumns)) {
            return '';
        }
        $column = $crud->getColumn($columnId);

        if ($options['render']) {
            return $environment->render($options['render'], [
                'column' => $column,
                'crud' => $crud,
                'options' => $options,
            ]);
        }

        if (!isset($options['ajax_options']['update'])) {
            $options['ajax_options']['update'] = '#'.$crud->getDivIdList();
        }

        //If the label was not defined, we take default label
        $label = $options['label'];
        if (null === $label) {
            $label = $column->label;
        }

        return $this->renderBlock($environment, $options['theme'], $options['block'], array_merge($options, [
            'column' => $column,
            'crud' => $crud,
            'options' => $options,
            'label' => $label,
        ]));
    }

    /**
     * Returns CRUD td tag.
     *
     * @param string $columnId Column to display
     * @param array  $options  Options:
     *                         * escape: Escape or not value. Default: true
     *                         * repeated_values_string: If not null, use this value if the original value is repeated. Default: null
     *                         * td_attr: "td" attributes
     *                         * render: Template used for generation. If null, default template is used
     */
    public function td(Environment $environment, string $columnId, Crud $crud, $value, $options = []): string
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'escape' => true,
            'repeated_values_string' => null,
            'td_attr' => [],
            'render' => null,
            'theme' => $this->theme,
            'block' => 'td',
        ]);
        $resolver->setAllowedTypes('escape', 'bool');
        $resolver->setAllowedTypes('repeated_values_string', ['null', 'string']);
        $resolver->setAllowedTypes('td_attr', ['null', 'array']);
        $options = $resolver->resolve($options);

        //If the column is not to be shown, returns empty
        $sessionValues = $crud->getSessionValues();
        if (!\in_array($columnId, $sessionValues->displayedColumns)) {
            return '';
        }
        $column = $crud->getColumn($columnId);

        if ($options['render']) {
            return $environment->render($options['render'], [
                'column' => $column,
                'crud' => $crud,
                'value' => $value,
                'options' => $options,
            ]);
        }

        $repeatedValue = false;
        if (null !== $options['repeated_values_string']) {
            $value = (string) $value; //transform to string is important : eg: Twig Markup
            if (isset($this->lastTdValues[$crud->getSessionName()][$columnId]) && $this->lastTdValues[$crud->getSessionName()][$columnId] === $value) {
                if ('' !== $value) {
                    $repeatedValue = true;
                }
            } else {
                $this->lastTdValues[$crud->getSessionName()][$columnId] = $value;
            }
        }

        return $this->renderBlock($environment, $options['theme'], $options['block'], array_merge($options, [
            'column' => $column,
            'crud' => $crud,
            'value' => $value,
            'repeated_value' => $repeatedValue,
            'options' => $options,
        ]));
    }

    /**
     * Returns CRUD td tag.
     *
     * @param array $options Options:
     *                       * modal: Use modal or not. Default: true
     *                       * render: Template used for generation. If null, default template is used
     */
    public function displaySettings(Environment $environment, Crud $crud, array $options = []): string
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'modal' => true,
            'render' => null,
            'theme' => $this->theme,
            'block' => 'display_settings',
        ]);
        $resolver->setAllowedTypes('modal', 'bool');
        $options = $resolver->resolve($options);

        $form = $crud->getDisplaySettingsForm();

        if ($options['render']) {
            return $environment->render($options['render'], [
                'crud' => $crud,
                'form' => $form,
                'options' => $options,
            ]);
        }

        return $this->renderBlock($environment, $options['theme'], $options['block'], array_merge($options, [
            'crud' => $crud,
            'form' => $form,
            'options' => $options,
        ]));
    }

    /**
     * @param array $options Options:
     *                       * ajax_options: Ajax options. Default: []
     *                       * form_attr: "form" attributes
     *                       * render: Template used for generation. If null, default template is used
     */
    public function searchFormStart(Environment $environment, Crud $crud, array $options = [])
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'ajax_options' => [],
            'form_attr' => [],
            'render' => null,
            'theme' => $this->theme,
            'block' => 'search_form_start',
        ]);
        $resolver->setAllowedTypes('ajax_options', ['array']);
        $resolver->setAllowedTypes('form_attr', ['array']);
        $options = $resolver->resolve($options);

        if ($options['render']) {
            return $environment->render($options['render'], [
                'crud' => $crud,
                'options' => $options,
            ]);
        }

        $options['form_attr'] = array_merge(
            [
                'data-crud-search-id' => $crud->getDivIdSearch(),
                'data-crud-list-id' => $crud->getDivIdList(),
            ],
            $options['form_attr'],
            $this->getAjaxAttributes($this->validateAjaxOptions($options['ajax_options']))
        );
        $class = 'ec-crud-search-form';
        if (isset($options['form_attr']['class'])) {
            $options['form_attr']['class'] = sprintf('%s %s', $class, $options['form_attr']['class']);
        } else {
            $options['form_attr']['class'] = $class;
        }

        if (!isset($options['form_attr']['novalidate'])) {
            $options['form_attr']['novalidate'] = 'novalidate';
        }

        return $this->renderBlock($environment, $options['theme'], $options['block'], array_merge($options, [
            'crud' => $crud,
            'options' => $options,
        ]));
    }

    /**
     * @param array $options Options:
     *                       * button_attr: "button" attributes
     *                       * render: Template used for generation. If null, default template is used
     */
    public function searchFormSubmit(Environment $environment, Crud $crud, $options = [], $ajaxOptions = [], $htmlOptions = [])
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'button_attr' => [],
            'render' => null,
            'theme' => $this->theme,
            'block' => 'search_form_submit',
        ]);
        $resolver->setAllowedTypes('button_attr', ['array']);
        $options = $resolver->resolve($options);

        if ($options['render']) {
            return $environment->render($options['render'], [
                'crud' => $crud,
                'options' => $options,
            ]);
        }

        return $this->renderBlock($environment, $options['theme'], $options['block'], array_merge($options, [
            'crud' => $crud,
            'options' => $options,
        ]));
    }

    /**
     * @param array $options Options:
     *                       * ajax_options: Ajax options. Default: []
     *                       * button_attr: "button" attributes
     *                       * render: Template used for generation. If null, default template is used
     */
    public function searchFormReset(Environment $environment, Crud $crud, $options = [], $ajaxOptions = [], $htmlOptions = [])
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'ajax_options' => [],
            'button_attr' => [],
            'render' => null,
            'theme' => $this->theme,
            'block' => 'search_form_reset',
        ]);
        $resolver->setAllowedTypes('ajax_options', ['array']);
        $resolver->setAllowedTypes('button_attr', ['array']);
        $options = $resolver->resolve($options);

        if ($options['render']) {
            return $environment->render($options['render'], [
                'crud' => $crud,
                'options' => $options,
            ]);
        }

        $options['button_attr'] = array_merge(
            [
                'data-crud-search-id' => $crud->getDivIdSearch(),
                'data-crud-list-id' => $crud->getDivIdList(),
                'data-ec-crud-ajax-url' => $crud->getSearchUrl(['raz' => 1]),
            ],
            $options['button_attr'],
            $this->getAjaxAttributes($this->validateAjaxOptions($options['ajax_options']))
        );
        $class = 'ec-crud-search-reset';
        if (isset($options['button_attr']['class'])) {
            $options['button_attr']['class'] = sprintf('%s %s', $class, $options['button_attr']['class']);
        } else {
            $options['button_attr']['class'] = $class;
        }

        return $this->renderBlock($environment, $options['theme'], $options['block'], array_merge($options, [
            'crud' => $crud,
            'options' => $options,
        ]));
    }

    public function formStartAjax(FormView $formView, array $options = []): string
    {
        $autoClass = 'ec-crud-ajax-form-auto';
        if (isset($options['auto_class']) && null !== isset($options['auto_class'])) {
            $autoClass = $options['auto_class'];
            unset($options['auto_class']);
        }
        if (isset($options['attr']['class'])) {
            $options['attr']['class'] = sprintf('%s %s', $autoClass, $options['attr']['class']);
        } else {
            $options['attr']['class'] = $autoClass;
        }

        if (isset($options['ajax_options'])) {
            $this->validateAjaxOptions($options['ajax_options']);
            $options['attr'] = array_merge(
                $options['attr'],
                $this->getAjaxAttributes($options['ajax_options'])
            );
            unset($options['ajax_options']);
        }

        return $this->formRenderer->renderBlock($formView, 'form_start', $options);
    }

    public function ajaxAttributes(Environment $environment, array $ajaxOptions): string
    {
        $this->validateAjaxOptions($ajaxOptions);
        $attributes = $this->getAjaxAttributes($ajaxOptions);
        if (0 === \count($attributes)) {
            return '';
        }

        return $this->renderBlock($environment, $this->theme, 'attributes', [
            'attr' => $attributes,
        ]);
    }

    protected function validateAjaxOptions(array $options, array $requiredOptions = []): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'url' => null,
            'update' => null,
            'update_mode' => null,
            'on_before_send' => null,
            'on_success' => null,
            'on_error' => null,
            'on_complete' => null,
            'data_type' => null,
            'method' => null,
            'data' => null,
            'cache' => null,
            'options' => null,
        ]);
        $resolver->setRequired($requiredOptions);

        return $resolver->resolve($options);
    }

    protected function getAjaxAttributes(array $options): array
    {
        $attributes = [];
        foreach ($options as $optionName => $optionValue) {
            if (null === $optionValue) {
                continue;
            }

            if (\is_bool($optionValue)) {
                $optionValue = ($optionValue) ? 'true' : 'false';
            } elseif (\is_array($optionValue)) {
                $optionValue = json_encode($optionValue);
            }

            $optionName = str_replace('_', '-', $optionName);
            $attributes['data-ec-crud-ajax-'.$optionName] = (string) $optionValue;
        }

        return $attributes;
    }

    public function crudIcon(Environment $environment, string $iconName): string
    {
        return $this->renderBlock($environment, $this->iconTheme, $iconName);
    }

    protected function renderBlock(Environment $environment, string $templateName, string $blockName, array $parameters = []): ?string
    {
        $template = $environment->load($templateName);

        ob_start();
        $template->displayBlock($blockName, array_merge(['template_name' => $templateName], $parameters));

        return ob_get_clean();
    }
}

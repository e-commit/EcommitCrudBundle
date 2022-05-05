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
use Ecommit\Paginator\PaginatorInterface;
use Symfony\Component\Form\FormRendererInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class CrudExtension extends AbstractExtension
{
    protected array $lastTdValues = [];

    public function __construct(protected FormRendererInterface $formRenderer, protected string $theme, protected string $iconTheme, protected array $configuration)
    {
    }

    public function getName(): string
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
     * Displays links paginator.
     *
     * @param string $routeName       Route name
     * @param array  $routeParameters Route parameters
     * @param array  $options         Options:
     *                                * ajax_options: Ajax Options. If null, Ajax is not used. Default: null
     *                                * attribute_page: Attribute inside url. Default: page
     *                                * type: Type of links paginator: elastic (all links) or sliding. Default: sliding
     *                                * max_pages_before: Max links before current page (only if sliding type is used). Default: 3
     *                                * max_pages_after: Max links after current page (only if sliding type is used). Default: 3
     *                                * nav_attr: "nav" attributes
     *                                * ul_attr: "ul" attributes
     *                                * li_attr: "li" attributes for each page type (sub arrays: first_page, previous_page, current_page, next_page, last_page, other_page)
     *                                * a_attr: "a" CSS attributes for each page type (sub arrays: first_page, previous_page, current_page, next_page, last_page, other_page)
     *                                * render: Template used for generation without the default process. If null, default process is used
     *                                * theme: Theme used. If null, default theme is used
     *                                * block: Twig block used. Default: paginator_links
     */
    public function paginatorLinks(Environment $environment, PaginatorInterface $paginator, string $routeName, array $routeParameters = [], array $options = []): string
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
            'a_attr' => function (OptionsResolver $aResolver, Options $parent): void {
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
                    $aResolver->addNormalizer($option, function (Options $options, mixed $value) use ($parent): array {
                        if (null !== $parent['ajax_options']) {
                            return array_merge(
                                $value,
                                ['class' => (isset($value['class'])) ? $value['class'].' ec-crud-ajax-link-auto' : 'ec-crud-ajax-link-auto'],
                                $this->getAjaxAttributes($this->validateAjaxOptions($parent['ajax_options'])),
                            );
                        }

                        return $value;
                    });
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
        $options = $resolver->resolve($this->buildOptions('paginator_links', $options));

        if ($options['render']) {
            return $environment->render($options['render'], [
                'paginator' => $paginator,
                'route_name' => $routeName,
                'route_parameters' => $routeParameters,
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

        // Pages before the current page
        $limit = ('sliding' == $options['type']) ? $paginator->getPage() - $options['max_pages_before'] : 1;
        for ($page = $limit; $page < $paginator->getPage(); ++$page) {
            if ($page <= $paginator->getLastPage() && $page >= $paginator->getFirstPage()) {
                $pages['before_current'][] = $page;
            }
        }

        // Pages after the current page
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
            'route_parameters' => $routeParameters,
            'options' => $options,
        ]));
    }

    /**
     * Displays CRUD links paginator.
     *
     * @see CrudExtension::paginatorLinks()
     */
    public function crudPaginatorLinks(Environment $environment, Crud $crud, array $options = []): string
    {
        if (!$crud->getPaginator()) {
            throw new \Exception('The paginator is not defined');
        }
        if (!isset($options['ajax_options']['update'])) {
            $options['ajax_options']['update'] = '#'.$crud->getDivIdList();
        }

        $options = $this->buildOptions('crud_paginator_links', $options, $crud);

        return $this->paginatorLinks($environment, $crud->getPaginator(), $crud->getRouteName(), $crud->getRouteParameters(), $options);
    }

    /**
     * Displays CRUD th tag.
     *
     * @param string $columnId Column to display
     * @param array  $options  Options:
     *                         * ajax_options: Ajax Options. If null, Ajax is not used. Default: null
     *                         * label: Th label. If null, CRUD configuration is used
     *                         * th_attr: "th" attributes
     *                         * a_attr: "a" attributes
     *                         * render: Template used for generation without the default process. If null, default process is used
     *                         * theme: Theme used. If null, default theme is used
     *                         * block: Twig block used. Default: th
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
            'a_attr' => function (OptionsResolver $aResolver, Options $parent): void {
                $aResolver->setDefaults([
                    'sortable_active_asc' => [],
                    'sortable_active_desc' => [],
                    'sortable_not_active' => [],
                ]);
                foreach (['sortable_active_asc', 'sortable_active_desc', 'sortable_not_active'] as $option) {
                    $aResolver->setAllowedTypes($option, 'array');
                    $aResolver->addNormalizer($option, function (Options $options, mixed $value) use ($parent): array {
                        if (null !== $parent['ajax_options']) {
                            return array_merge(
                                $value,
                                ['class' => (isset($value['class'])) ? $value['class'].' ec-crud-ajax-link-auto' : 'ec-crud-ajax-link-auto'],
                                $this->getAjaxAttributes($this->validateAjaxOptions($parent['ajax_options'])),
                            );
                        }

                        return $value;
                    });
                }
            },
            'render' => null,
            'theme' => $this->theme,
            'block' => 'th',
        ]);
        $resolver->setAllowedTypes('ajax_options', ['null', 'array']);
        $resolver->addNormalizer('ajax_options', function (Options $options, mixed $value) use ($crud): array {
            if (!isset($value['update'])) {
                $value['update'] = '#'.$crud->getDivIdList();
            }

            return $value;
        });
        $resolver->setAllowedTypes('label', ['null', 'string']);
        $options = $resolver->resolve($this->buildOptions('crud_th', $options, $crud));

        // If the column is not to be shown, returns empty
        $sessionValues = $crud->getSessionValues();
        if (!\in_array($columnId, $sessionValues->getDisplayedColumns())) {
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

        // If the label was not defined, we take default label
        $label = $options['label'];
        if (null === $label) {
            $label = $column->getLabel();
        }

        return $this->renderBlock($environment, $options['theme'], $options['block'], array_merge($options, [
            'column' => $column,
            'crud' => $crud,
            'options' => $options,
            'label' => $label,
        ]));
    }

    /**
     * Displays CRUD td tag.
     *
     * @param string $columnId Column to display
     * @param array  $options  Options:
     *                         * escape: Escape or not value. Default: true
     *                         * repeated_values_string: If not null, use this value if the original value is repeated. Default: null
     *                         * td_attr: "td" attributes
     *                         * render: Template used for generation without the default process. If null, default process is used
     *                         * theme: Theme used. If null, default theme is used
     *                         * block: Twig block used. Default: td
     */
    public function td(Environment $environment, string $columnId, Crud $crud, mixed $value, array $options = []): string
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
        $options = $resolver->resolve($this->buildOptions('crud_td', $options, $crud));

        // If the column is not to be shown, returns empty
        $sessionValues = $crud->getSessionValues();
        if (!\in_array($columnId, $sessionValues->getDisplayedColumns())) {
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
            $value = (string) $value; // transform to string is important : eg: Twig Markup
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
     * Returns CRUD display settings button.
     *
     * @param array $options Options:
     *                       * modal: Use modal or not. Default: true
     *                       * render: Template used for generation without the default process. If null, default process is used
     *                       * theme: Theme used. If null, default theme is used
     *                       * block: Twig block used. Default: display_settings
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
        $options = $resolver->resolve($this->buildOptions('crud_display_settings', $options, $crud));

        $form = $crud->getDisplaySettingsForm();
        if (!$form instanceof FormView) {
            throw new \Exception('Method "Crud::createView" must be called before using Twig function "crud_display_settings"');
        }

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
     * Search form start tag.
     *
     * @param array $options Options:
     *                       * ajax_options: Ajax options. Default: []
     *                       * form_attr: "form" attributes
     *                       * render: Template used for generation without the default process. If null, default process is used
     *                       * theme: Theme used. If null, default theme is used
     *                       * block: Twig block used. Default: search_form_start
     */
    public function searchFormStart(Environment $environment, Crud $crud, array $options = []): string
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
        $resolver->addNormalizer('form_attr', function (Options $options, mixed $value) use ($crud): array {
            /** @var FormView $searchForm */
            $searchForm = $crud->getSearchForm();

            return array_merge(
                [
                    'data-crud-search-id' => $crud->getDivIdSearch(),
                    'data-crud-list-id' => $crud->getDivIdList(),
                ],
                $searchForm->vars['attr'],
                $value,
                $this->getAjaxAttributes($this->validateAjaxOptions($options['ajax_options'])),
                ['class' => (isset($value['class'])) ? $value['class'].' ec-crud-search-form' : 'ec-crud-search-form'],
            );
        });
        $options = $resolver->resolve($this->buildOptions('crud_search_form_start', $options, $crud));

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
     * Search form submit button.
     *
     * @param array $options Options:
     *                       * button_attr: "button" attributes
     *                       * render: Template used for generation without the default process. If null, default process is used
     *                       * theme: Theme used. If null, default theme is used
     *                       * block: Twig block used. Default: search_form_submit
     */
    public function searchFormSubmit(Environment $environment, Crud $crud, array $options = []): string
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'button_attr' => [],
            'render' => null,
            'theme' => $this->theme,
            'block' => 'search_form_submit',
        ]);
        $resolver->setAllowedTypes('button_attr', ['array']);
        $options = $resolver->resolve($this->buildOptions('crud_search_form_submit', $options, $crud));

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
     * Search form reset button.
     *
     * @param array $options Options:
     *                       * ajax_options: Ajax options. Default: []
     *                       * button_attr: "button" attributes
     *                       * render: Template used for generation without the default process. If null, default process is used
     *                       * theme: Theme used. If null, default theme is used
     *                       * block: Twig block used. Default: search_form_reset
     */
    public function searchFormReset(Environment $environment, Crud $crud, array $options = []): string
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
        $resolver->addNormalizer('button_attr', function (Options $options, mixed $value) use ($crud): array {
            return array_merge(
                [
                    'data-crud-search-id' => $crud->getDivIdSearch(),
                    'data-crud-list-id' => $crud->getDivIdList(),
                    'data-ec-crud-ajax-url' => $crud->getSearchUrl(['reset' => 1]),
                ],
                $value,
                $this->getAjaxAttributes($this->validateAjaxOptions($options['ajax_options'])),
                ['class' => (isset($value['class'])) ? $value['class'].' ec-crud-search-reset' : 'ec-crud-search-reset'],
            );
        });
        $options = $resolver->resolve($this->buildOptions('crud_search_form_reset', $options, $crud));

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
     * Ajax form start tag.
     *
     * @param array $options Options:
     *                       * auto_class: Auto CSS class used. Default: ec-crud-ajax-form-auto
     *                       * ajax_options: See "ajaxAttributes" method options
     *                       * + form_start function options
     */
    public function formStartAjax(FormView $formView, array $options = []): string
    {
        $autoClass = 'ec-crud-ajax-form-auto';
        if (isset($options['auto_class']) && null !== $options['auto_class']) {
            $autoClass = $options['auto_class'];
            unset($options['auto_class']);
        }
        if (isset($options['attr']['class']) && null !== $options['attr']['class']) {
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

    /**
     * Displays Ajax attributes.
     *
     * @param array $ajaxOptions Options:
     *                           * url: Ajax url
     *                           * update: Update the DOM with the response - jquery selector (eg: #mydiv)
     *                           * update_mode: Update the DOM with the response - mode (update / before / after / prepend / append). Default: update
     *                           * on_before_send: Callback
     *                           * on_success: Callback
     *                           * on_error: Callback
     *                           * on_complete: Callback
     *                           * data_type: Request type. Default: html
     *                           * method: Resquest method: Default: POST
     *                           * data: Data sent
     *                           * cache: Use cache. Default: false
     *                           * options: Array of options
     */
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

    public function crudIcon(Environment $environment, string $iconName, ?string $iconTheme = null): string
    {
        if (null === $iconTheme) {
            $iconTheme = $this->iconTheme;
        }

        return $this->renderBlock($environment, $iconTheme, $iconName);
    }

    protected function renderBlock(Environment $environment, string $templateName, string $blockName, array $parameters = []): string
    {
        $template = $environment->load($templateName);

        ob_start();
        $template->displayBlock($blockName, array_merge(['template_name' => $templateName], $parameters));

        return ob_get_clean();
    }

    protected function buildOptions(string $function, array $inlineOptions, ?Crud $crud = null): array
    {
        $options = [];

        if (isset($this->configuration[$function])) {
            $options = $this->configuration[$function];
        }

        if (null !== $crud) {
            $options = array_merge($options, $crud->getTwigFunctionConfiguration($function));
        }

        return array_merge($options, $inlineOptions);
    }
}

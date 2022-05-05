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

namespace Ecommit\CrudBundle\Crud;

use Ecommit\CrudBundle\Entity\UserCrudSettings;
use Ecommit\CrudBundle\Form\Searcher\SearcherInterface;
use Ecommit\CrudBundle\Form\Type\DisplaySettingsType;
use Ecommit\DoctrineUtils\Paginator\DoctrinePaginatorBuilder;
use Ecommit\Paginator\PaginatorInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

final class Crud
{
    public const DESC = 'DESC';
    public const ASC = 'ASC';

    /**
     * @var array{
     *     session_name: string,
     *     columns: array<string, CrudColumn>,
     *     virtual_columns: array<string, CrudColumn>,
     *     max_per_page_choices: array<int>,
     *     default_max_per_page: int,
     *     default_sort: string,
     *     default_sort_direction: string,
     *     default_personalized_sort: array,
     *     query_builder: \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder|QueryBuilderInterface,
     *     route_name: string,
     *     route_parameters: array,
     *     search_form_data: ?SearcherInterface,
     *     search_form_type: ?string,
     *     search_form_options: array,
     *     display_results_only_if_search: bool,
     *     build_paginator: bool|\Closure|array,
     *     persistent_settings: bool,
     *     div_id_search: string,
     *     div_id_list: string,
     *     twig_functions_configuration: array,
     * }
     */
    protected array $options;

    protected CrudSession $sessionValues;
    protected SearchFormBuilder|FormView|null $searchForm = null;
    /** @psalm-suppress PropertyNotSetInConstructor */
    protected FormInterface|FormView $displaySettingsForm;
    protected bool $updateDatabase = false;
    protected ?PaginatorInterface $paginator = null;
    protected bool $displayResults = true;
    protected bool $viewCreated = false;

    public function __construct(array $options, protected ContainerInterface $container)
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired([
            'session_name',
            'columns',
            'max_per_page_choices',
            'default_max_per_page',
            'default_sort',
            'query_builder',
            'route_name',
        ]);
        $resolver->setDefaults([
            'virtual_columns' => [],
            'default_sort_direction' => self::ASC,
            'default_personalized_sort' => [],
            'route_parameters' => [],
            'search_form_data' => null,
            'search_form_type' => null,
            'search_form_options' => [],
            'display_results_only_if_search' => false,
            'build_paginator' => true,
            'persistent_settings' => false,
            'div_id_search' => 'crud_search',
            'div_id_list' => 'crud_list',
            'twig_functions_configuration' => [],
        ]);

        $resolver->setAllowedTypes('session_name', 'string');
        $resolver->setAllowedValues('session_name', Validation::createCallable(
            new Assert\Regex(pattern: '/^[a-zA-Z0-9_]{1,50}$/', message: 'Invalid session_name format.'),
        ));

        $resolver->setAllowedTypes('columns', ['array']);
        $resolver->setAllowedValues('columns', Validation::createCallable(
            new Assert\Count(min: 1, minMessage: 'The CRUD should contain 1 column or more.'),
        ));
        $resolver->setNormalizer('columns', function (Options $options, array $value): array {
            $columns = [];
            foreach ($value as $column) {
                if (\is_array($column)) {
                    $column = new CrudColumn($column);
                } elseif (!$column instanceof CrudColumn) {
                    throw new \Exception('A column must be an array or a CrudColum instance.');
                }
                if (\array_key_exists($column->getId(), $columns)) {
                    throw new \Exception(sprintf('The column "column1" already exists.', $column->getId()));
                }
                $columns[$column->getId()] = $column;
            }

            return $columns;
        });

        $resolver->setAllowedTypes('virtual_columns', 'array');
        $resolver->setNormalizer('virtual_columns', function (Options $options, array $value): array {
            $columns = [];
            foreach ($value as $column) {
                if (\is_array($column)) {
                    $column = new CrudColumn($column);
                } elseif (!$column instanceof CrudColumn) {
                    throw new \Exception('A column must be an array or a CrudColum instance.');
                }
                if (\array_key_exists($column->getId(), $columns)) {
                    throw new \Exception(sprintf('The column "column1" already exists.', $column->getId()));
                }
                $columns[$column->getId()] = $column;
            }

            return $columns;
        });

        $resolver->setAllowedTypes('max_per_page_choices', 'int[]');
        $resolver->setAllowedValues('max_per_page_choices', Validation::createCallable(
            new Assert\Count(min: 1, minMessage: 'The max_per_page_choices should contain 1 value or more.'),
        ));
        $resolver->setAllowedTypes('default_max_per_page', 'int');

        $resolver->setAllowedTypes('default_sort', 'string');
        $resolver->setAllowedValues('default_sort_direction', [self::ASC, self::DESC]);
        $resolver->setAllowedTypes('default_personalized_sort', 'string[]');

        $resolver->setAllowedTypes('query_builder', [
            \Doctrine\ORM\QueryBuilder::class,
            \Doctrine\DBAL\Query\QueryBuilder::class,
            QueryBuilderInterface::class,
        ]);

        $resolver->setAllowedTypes('route_name', 'string');
        $resolver->setAllowedTypes('route_parameters', 'array');

        $resolver->setAllowedTypes('search_form_data', ['null', SearcherInterface::class]);
        $resolver->setAllowedTypes('search_form_type', ['null', 'string']);
        $resolver->setAllowedTypes('search_form_options', 'array');

        $resolver->setAllowedTypes('display_results_only_if_search', 'bool');
        $resolver->setAllowedTypes('build_paginator', ['bool', \Closure::class, 'array']);

        $resolver->setAllowedTypes('persistent_settings', 'bool');
        $resolver->setNormalizer('persistent_settings', function (Options $options, bool $value): bool {
            if ($value && (null === $this->container->get('security.token_storage')->getToken() || !($this->container->get('security.token_storage')->getToken()->getUser() instanceof UserInterface))) {
                return false;
            }

            return $value;
        });

        $resolver->setAllowedTypes('div_id_search', 'string');
        $resolver->setAllowedTypes('div_id_list', 'string');

        $resolver->setAllowedTypes('twig_functions_configuration', 'array');

        $this->options = $resolver->resolve($options);

        // Check duplicates in columns / vitual columns
        $duplicates = array_intersect_key($this->options['columns'], $this->options['virtual_columns']);
        if (\count($duplicates) > 0) {
            throw new \Exception(sprintf('The column "column1" already exists.', array_keys($duplicates)[0]));
        }

        $this->init();

        return $this;
    }

    protected function init(): void
    {
        $this->createSearchForm();

        // Loads user values inside this object
        $this->load();

        // Display or not results
        if ($this->searchForm && $this->getDisplayResultsOnlyIfSearch()) {
            $this->displayResults = $this->sessionValues->isSearchFormIsSubmittedAndValid();
        }

        $this->createDisplaySettingsForm();

        // Process request (resultsPerPage, sort, sortDirection, change_columns)
        $this->processRequest();

        $this->buildSearchForm();

        // Saves
        $this->save();
    }

    protected function createSearchForm(): void
    {
        if ($this->options['search_form_data']) {
            $this->searchForm = new SearchFormBuilder($this->container, $this, $this->options['search_form_data'], $this->options['search_form_type'], $this->options['search_form_options']);
        }
    }

    protected function buildSearchForm(): void
    {
        $searchFormBuilder = $this->getSearchFormBuilder();
        if (!$searchFormBuilder) {
            return;
        }

        $searchFormBuilder->createForm();

        // Allocates object
        if ($this->container->get('request_stack')->getCurrentRequest()->query->has('reset')) {
            return;
        }
        try {
            $searchFormBuilder->setData($this->sessionValues->getSearchFormData());
        } catch (TransformationFailedException $exception) {
            // Avoid error if data stored in session is invalid
        }
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Returns availabled columns.
     */
    public function getColumns(): array
    {
        return $this->options['columns'];
    }

    public function getColumn(string $columnId): CrudColumn
    {
        if (isset($this->options['columns'][$columnId])) {
            return $this->options['columns'][$columnId];
        }
        throw new \Exception(sprintf('The column "%s" does not exist.', $columnId));
    }

    /**
     * Return default displayed columns.
     */
    public function getDefaultDisplayedColumns(): array
    {
        $columns = [];
        foreach ($this->getColumns() as $column) {
            if ($column->isDisplayedByDefault()) {
                $columns[] = $column->getId();
            }
        }
        if (0 == \count($columns)) {
            throw new \Exception('The CRUD should contain 1 displayed column or more.');
        }

        return $columns;
    }

    /**
     * Returns availabled virtual columns.
     */
    public function getVirtualColumns(): array
    {
        return $this->options['virtual_columns'];
    }

    public function getVirtualColumn(string $columnId): CrudColumn
    {
        if (isset($this->options['virtual_columns'][$columnId])) {
            return $this->options['virtual_columns'][$columnId];
        }
        throw new \Exception(sprintf('The column "%s" does not exist.', $columnId));
    }

    public function getQueryBuilder(): \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder|QueryBuilderInterface
    {
        return $this->options['query_builder'];
    }

    public function getMaxPerPageChoices(): array
    {
        return $this->options['max_per_page_choices'];
    }

    public function getDefaultMaxPerPage(): int
    {
        return $this->options['default_max_per_page'];
    }

    public function getDefaultSort(): string
    {
        return $this->options['default_sort'];
    }

    public function getDefaultSortDirection(): string
    {
        return $this->options['default_sort_direction'];
    }

    public function getDefaultPersonalizedSort(): array
    {
        return $this->options['default_personalized_sort'];
    }

    public function getRouteName(): string
    {
        return $this->options['route_name'];
    }

    public function getRouteParameters(): array
    {
        return $this->options['route_parameters'];
    }

    /**
     * Returns the list url.
     */
    public function getUrl(array $parameters = []): string
    {
        $parameters = array_merge($this->getRouteParameters(), $parameters);

        return $this->container->get('router')->generate($this->getRouteName(), $parameters);
    }

    /**
     * Returns the search url.
     */
    public function getSearchUrl(array $parameters = []): string
    {
        $parameters = array_merge($this->getRouteParameters(), ['search' => 1], $parameters);

        return $this->container->get('router')->generate($this->getRouteName(), $parameters);
    }

    public function getSessionName(): string
    {
        return $this->options['session_name'];
    }

    public function getDisplayResultsOnlyIfSearch(): bool
    {
        return $this->options['display_results_only_if_search'];
    }

    public function getDisplayResults(): bool
    {
        return $this->displayResults;
    }

    public function setDisplayResults(bool $displayResults): self
    {
        $this->displayResults = $displayResults;
        if (false === $displayResults && $this->paginator) {
            $this->paginator = null;
        }

        return $this;
    }

    public function getPaginator(): ?PaginatorInterface
    {
        return $this->paginator;
    }

    public function setPaginator(?PaginatorInterface $value): self
    {
        $this->paginator = $value;

        return $this;
    }

    public function getSessionValues(): CrudSession
    {
        return $this->sessionValues;
    }

    public function getSearchFormBuilder(): ?SearchFormBuilder
    {
        if (null === $this->searchForm) {
            return null;
        } elseif ($this->searchForm instanceof SearchFormBuilder) {
            return $this->searchForm;
        }

        throw new \Exception('The object "SearchFormBuilder" no longer exists since the call of the method "Crud::createView".');
    }

    public function getSearchForm(): ?FormView
    {
        if (null === $this->searchForm) {
            return null;
        } elseif ($this->searchForm instanceof FormView) {
            return $this->searchForm;
        }

        throw new \Exception('The object "FormView" exists only after the call of the method "Crud::createView".');
    }

    public function getDivIdSearch(): string
    {
        return $this->options['div_id_search'];
    }

    public function getDivIdList(): string
    {
        return $this->options['div_id_list'];
    }

    public function processSearchForm(): self
    {
        $searchFormBuilder = $this->getSearchFormBuilder();
        if (!$searchFormBuilder) {
            throw new NotFoundHttpException('Crud: Search form does not exist.');
        }

        $request = $this->container->get('request_stack')->getCurrentRequest();
        if ($request->query->has('reset')) {
            return $this;
        }
        if ('POST' == $request->getMethod()) {
            if ($this->getDisplayResultsOnlyIfSearch()) {
                $this->displayResults = false;
            }
            $searchForm = $searchFormBuilder->getForm();
            $searchForm->handleRequest($request);
            if ($searchForm->isSubmitted() && $searchForm->isValid() && false !== $this->options['build_paginator']) {
                $this->displayResults = true;
                $this->sessionValues = $this->sessionValues->setSearchFormIsSubmittedAndValid(true);
                $this->changeSearchFormData($searchForm->getData());
                $this->changePage(1);
                $this->save();
            }
        }

        return $this;
    }

    public function build(): self
    {
        $this->updateQueryBuilder();
        $this->buildPaginator();

        return $this;
    }

    protected function updateQueryBuilder(): void
    {
        // Builds query
        $columnSortId = $this->sessionValues->getSort();
        if ('defaultPersonalizedSort' == $columnSortId) {
            // Default personalised sort is used
            foreach ($this->getDefaultPersonalizedSort() as $key => $value) {
                if (\is_int($key)) {
                    $sort = $value;
                    $sortDirection = $this->getDefaultSortDirection();
                } else {
                    $sort = $key;
                    $sortDirection = $value;
                }
                $this->getQueryBuilder()->addOrderBy($sort, $sortDirection);
            }
        } else {
            $columnSortAlias = $this->getColumn($columnSortId)->getAliasSort();
            if (\is_array($columnSortAlias)) {
                // Sort alias in many columns
                foreach ($columnSortAlias as $oneColumnSortAlias) {
                    $this->getQueryBuilder()->addOrderBy($oneColumnSortAlias, $this->sessionValues->getSortDirection());
                }
            } else {
                // Sort alias in one column
                $this->getQueryBuilder()->orderBy($columnSortAlias, $this->sessionValues->getSortDirection());
            }
        }

        // Adds form searcher filters
        $searchFormBuilder = $this->getSearchFormBuilder();
        if ($searchFormBuilder) {
            /** @psalm-suppress PossiblyNullArgument */
            $searchFormBuilder->updateQueryBuilder($this->getQueryBuilder(), $this->sessionValues->getSearchFormData());
        }
    }

    protected function buildPaginator(): void
    {
        if (!$this->displayResults || false === $this->options['build_paginator']) {
            return;
        }

        if ($this->options['build_paginator'] instanceof \Closure) {
            // Case: Manual paginator (by closure) is enabled
            $this->paginator = $this->options['build_paginator']->__invoke(
                $this->getQueryBuilder(),
                $this->sessionValues->getPage(),
                $this->sessionValues->getMaxPerPage()
            );

            return;
        }

        $queryBuilder = $this->getQueryBuilder();
        if ($queryBuilder instanceof QueryBuilderInterface) {
            throw new \Exception('Doctrine paginator is not compatible with Ecommit\CrudBundle\Crud\QueryBuilderInterface query builder');
        }

        // Case: Auto paginator is enabled
        $paginatorOptions = [];
        if (\is_array($this->options['build_paginator'])) {
            $paginatorOptions = $this->options['build_paginator'];
        }

        $this->paginator = DoctrinePaginatorBuilder::createDoctrinePaginator(
            $queryBuilder,
            $this->sessionValues->getPage(),
            $this->sessionValues->getMaxPerPage(),
            $paginatorOptions
        );
    }

    /**
     * Reset search form values.
     */
    public function reset(): self
    {
        $searchFormBuilder = $this->getSearchFormBuilder();
        if ($searchFormBuilder) {
            $newValue = clone $searchFormBuilder->getDefaultData();
            $this->changeSearchFormData($newValue);
            $searchFormBuilder->setData(clone $newValue);
            $this->sessionValues = $this->sessionValues->setSearchFormIsSubmittedAndValid(false);
        }
        $this->changePage(1);
        $this->save();

        if ($this->getDisplayResultsOnlyIfSearch()) {
            $this->displayResults = false;
        }

        return $this;
    }

    public function resetSort(): self
    {
        $this->sessionValues = $this->sessionValues->setSortDirection($this->getDefaultSortDirection());
        $this->sessionValues = $this->sessionValues->setSort($this->getDefaultSort());
        $this->save();

        return $this;
    }

    protected function resetDisplaySettings(): void
    {
        $this->sessionValues = $this->sessionValues->setDisplayedColumns($this->getDefaultDisplayedColumns());
        $this->sessionValues = $this->sessionValues->setMaxPerPage($this->getDefaultMaxPerPage());
        $this->sessionValues = $this->sessionValues->setSortDirection($this->getDefaultSortDirection());
        $this->sessionValues = $this->sessionValues->setSort($this->getDefaultSort());

        if ($this->options['persistent_settings']) {
            // Remove settings in database
            $qb = $this->container->get('doctrine')->getManager()->createQueryBuilder();
            $qb->delete('EcommitCrudBundle:UserCrudSettings', 's')
                ->andWhere('s.user = :user AND s.crudName = :crud_name')
                ->setParameters(['user' => $this->container->get('security.token_storage')->getToken()->getUser(), 'crud_name' => $this->getSessionName()])
                ->getQuery()
                ->execute();
        }
    }

    public function createView(): self
    {
        if ($this->viewCreated) {
            throw new \Exception('Crud::createView has already been called.');
        }
        $searchFormBuilder = $this->getSearchFormBuilder();
        if ($searchFormBuilder) {
            $searchFormBuilder->createFormView();
            $this->searchForm = $searchFormBuilder->getFormView();
        }
        /** @var FormInterface $displaySettingsForm */
        $displaySettingsForm = $this->displaySettingsForm;
        $this->displaySettingsForm = $displaySettingsForm->createView();
        $this->viewCreated = true;

        return $this;
    }

    public function getTwigFunctionsConfiguration(): array
    {
        return $this->options['twig_functions_configuration'];
    }

    public function getTwigFunctionConfiguration(string $function): array
    {
        if (isset($this->options['twig_functions_configuration'][$function])) {
            return $this->options['twig_functions_configuration'][$function];
        }

        return [];
    }

    protected function testIfDatabaseMustMeUpdated(mixed $oldValue, mixed $newValue): void
    {
        if ($oldValue != $newValue) {
            $this->updateDatabase = true;
        }
    }

    /**
     * Checks user values.
     */
    protected function checkCrudSession(): void
    {
        // Forces change => checks
        $this->changeMaxPerPage($this->sessionValues->getMaxPerPage());
        $this->changeColumnsDisplayed($this->sessionValues->getDisplayedColumns());
        $this->changeSort($this->sessionValues->getSort());
        $this->changeSortDirection($this->sessionValues->getSortDirection());
        $this->changeSearchFormData($this->sessionValues->getSearchFormData());
        $this->changePage($this->sessionValues->getPage());
    }

    /**
     * User Action: Changes number of displayed results.
     */
    protected function changeMaxPerPage(int $value): void
    {
        $oldValue = $this->sessionValues->getMaxPerPage();
        if (\in_array($value, $this->getMaxPerPageChoices())) {
            $this->sessionValues = $this->sessionValues->setMaxPerPage($value);
        } else {
            $this->sessionValues = $this->sessionValues->setMaxPerPage($this->getDefaultMaxPerPage());
        }
        $this->testIfDatabaseMustMeUpdated($oldValue, $value);
    }

    /**
     * User Action: Changes displayed columns.
     */
    protected function changeColumnsDisplayed(array $value): void
    {
        $oldValue = $this->sessionValues->getDisplayedColumns();
        $newDisplayedColumns = [];
        $columns = $this->getColumns();
        foreach ($value as $columnName) {
            if (\array_key_exists($columnName, $columns)) {
                $newDisplayedColumns[] = $columnName;
            }
        }
        if (0 == \count($newDisplayedColumns)) {
            $newDisplayedColumns = $this->getDefaultDisplayedColumns();
        }
        $this->sessionValues = $this->sessionValues->setDisplayedColumns($newDisplayedColumns);
        $this->testIfDatabaseMustMeUpdated($oldValue, $newDisplayedColumns);
    }

    /**
     * User Action: Changes sort.
     */
    protected function changeSort(mixed $value): void
    {
        $oldValue = $this->sessionValues->getSort();
        $columns = $this->getColumns();
        if ((\is_string($value) && \array_key_exists($value, $columns) && $columns[$value]->isSortable())
            || (\is_string($value) && 'defaultPersonalizedSort' === $value && $this->getDefaultPersonalizedSort())) {
            $this->sessionValues = $this->sessionValues->setSort($value);
            $this->testIfDatabaseMustMeUpdated($oldValue, $value);
        } else {
            $this->sessionValues = $this->sessionValues->setSort($this->getDefaultSort());
            $this->testIfDatabaseMustMeUpdated($oldValue, $this->getDefaultSort());
        }
    }

    /**
     * User action: Changes sort direction.
     */
    protected function changeSortDirection(mixed $value): void
    {
        $oldValue = $this->sessionValues->getSortDirection();
        if (\is_string($value) && (self::ASC === $value || self::DESC === $value)) {
            $this->sessionValues = $this->sessionValues->setSortDirection($value);
            $this->testIfDatabaseMustMeUpdated($oldValue, $value);
        } else {
            $this->sessionValues = $this->sessionValues->setSortDirection($this->getDefaultSortDirection());
            $this->testIfDatabaseMustMeUpdated($oldValue, $this->getDefaultSortDirection());
        }
    }

    /**
     * User action: Changes search form values.
     */
    protected function changeSearchFormData(?SearcherInterface $value): void
    {
        $searchFormBuilder = $this->getSearchFormBuilder();
        if (!$searchFormBuilder) {
            $this->sessionValues = $this->sessionValues->setSearchFormData(null);

            return;
        }
        if (null !== $value && $value::class === \get_class($searchFormBuilder->getDefaultData())) {
            $this->sessionValues = $this->sessionValues->setSearchFormData($value);
        } else {
            $this->sessionValues = $this->sessionValues->setSearchFormData($searchFormBuilder->getDefaultData());
        }
    }

    /**
     * User action: Changes page number.
     */
    protected function changePage(mixed $value): void
    {
        if (!\is_scalar($value)) {
            $value = 1;
        }
        $value = (int) $value;
        if ($value > 1000000000000) {
            $value = 1;
        }
        $this->sessionValues = $this->sessionValues->setPage($value);
    }

    /**
     * Process request.
     */
    protected function processRequest(): void
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        if ($request->query->has('resetsettings')) {
            // Reset display settings
            $this->resetDisplaySettings();

            return;
        }
        if ($request->query->has('reset')) {
            $this->reset();

            return;
        }

        /** @var FormInterface $displaySettingsForm */
        $displaySettingsForm = $this->displaySettingsForm;
        $displaySettingsForm->handleRequest($request);
        if ($displaySettingsForm->isSubmitted() && $displaySettingsForm->isValid()) {
            $displaySettingsData = $displaySettingsForm->getData();
            $this->changeColumnsDisplayed($displaySettingsData['displayedColumns']);
            $this->changeMaxPerPage($displaySettingsData['resultsPerPage']);
        }
        if ($request->query->has('sort')) {
            $this->changeSort($request->query->get('sort'));
        }
        if ($request->query->has('sort-direction')) {
            $this->changeSortDirection($request->query->get('sort-direction'));
        }
        if ($request->query->has('page')) {
            $this->changePage($request->query->get('page'));
        }
    }

    /**
     * Returns the search form (FormInterface object before createView or FormView object after createView).
     */
    public function getDisplaySettingsForm(): FormInterface|FormView|null
    {
        return $this->displaySettingsForm;
    }

    protected function createDisplaySettingsForm(): void
    {
        $resultsPerPageChoices = [];
        foreach ($this->getMaxPerPageChoices() as $number) {
            $resultsPerPageChoices[$number] = $number;
        }
        $columnsChoices = [];
        foreach ($this->getColumns() as $column) {
            $columnsChoices[$column->getId()] = $column->getLabel();
        }
        $data = [
            'resultsPerPage' => $this->getSessionValues()->getMaxPerPage(),
            'displayedColumns' => $this->getSessionValues()->getDisplayedColumns(),
        ];
        $formName = sprintf('crud_display_settings_%s', $this->getSessionName());

        $this->displaySettingsForm = $this->container->get('form.factory')->createNamed($formName, DisplaySettingsType::class, $data, [
            'results_per_page_choices' => $resultsPerPageChoices,
            'columns_choices' => $columnsChoices,
            'action' => $this->getUrl(['display-settings' => 1]),
            'reset_settings_url' => $this->getUrl(['resetsettings' => 1]),
        ]);
    }

    /**
     * Load user values.
     */
    protected function load(): void
    {
        $session = $this->container->get('request_stack')->getCurrentRequest()->getSession();
        $object = $session->get($this->getSessionName()); // Load from session

        if (!empty($object)) {
            $this->sessionValues = $object;
            $this->checkCrudSession();

            return;
        }

        // If session is null => Assign default value
        $searchFormBuilder = $this->getSearchFormBuilder();
        $this->sessionValues = new CrudSession(
            $this->getDefaultMaxPerPage(),
            $this->getDefaultDisplayedColumns(),
            $this->getDefaultSort(),
            $this->getDefaultSortDirection(),
            ($searchFormBuilder) ? $searchFormBuilder->getDefaultData() : null,
        );

        // If persistent settings is enabled -> Retrieve from database
        if ($this->options['persistent_settings']) {
            $objectDatabase = $this->container->get('doctrine')->getRepository(UserCrudSettings::class)->findOneBy(
                [
                    'user' => $this->container->get('security.token_storage')->getToken()->getUser(),
                    'crudName' => $this->getSessionName(),
                ]
            );
            if ($objectDatabase) {
                $this->sessionValues = $this->sessionValues->updateFromUserCrudSettings($objectDatabase);
                $this->checkCrudSession();

                return;
            }
        }
    }

    /**
     * Saves user value.
     */
    protected function save(): void
    {
        // Save in session
        $session = $this->container->get('request_stack')->getCurrentRequest()->getSession();
        $session->set($this->getSessionName(), clone $this->sessionValues);

        // Save in database
        if ($this->options['persistent_settings'] && $this->updateDatabase) {
            $objectDatabase = $this->container->get('doctrine')->getRepository(UserCrudSettings::class)->findOneBy(
                [
                    'user' => $this->container->get('security.token_storage')->getToken()->getUser(),
                    'crudName' => $this->getSessionName(),
                ]
            );
            $em = $this->container->get('doctrine')->getManager();

            if ($objectDatabase) {
                // Update object in database
                $this->sessionValues->updateUserCrudSettings($objectDatabase);
                $em->flush();
            } else {
                // Create object in database only if not default values
                if ($this->sessionValues->getDisplayedColumns() != $this->getDefaultDisplayedColumns() ||
                    $this->sessionValues->getMaxPerPage() != $this->getDefaultMaxPerPage() ||
                    $this->sessionValues->getSortDirection() != $this->getDefaultSortDirection() ||
                    $this->sessionValues->getSort() != $this->getDefaultSort()
                ) {
                    $objectDatabase = new UserCrudSettings(
                        $this->container->get('security.token_storage')->getToken()->getUser(),
                        $this->getSessionName(),
                        $this->sessionValues->getMaxPerPage(),
                        $this->sessionValues->getDisplayedColumns(),
                        $this->sessionValues->getSort(),
                        $this->sessionValues->getSortDirection()
                    );
                    $em->persist($objectDatabase);
                    $em->flush();
                }
            }
        }
    }
}

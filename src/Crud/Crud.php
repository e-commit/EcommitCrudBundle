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
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\User\UserInterface;

final class Crud
{
    public const DESC = 'DESC';
    public const ASC = 'ASC';

    protected CrudSession $sessionValues;
    protected SearchFormBuilder|FormView|null $searchForm = null;
    protected FormInterface|FormView|null $displaySettingsForm = null;
    protected bool $isInitialized = false;
    protected bool $initializationInProgress = false;
    protected array $availableColumns = [];
    protected array $availableVirtualColumns = [];
    protected array $availableResultsPerPage = [];
    protected ?string $defaultSort = null;
    protected array $defaultPersonalizedSort = [];
    protected ?string $defaultSense = null;
    protected ?int $defaultResultsPerPage = null;
    protected \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder|QueryBuilderInterface|null $queryBuilder = null;
    protected bool $persistentSettings = false;
    protected bool $updateDatabase = false;
    protected ?PaginatorInterface $paginator = null;
    protected bool|\Closure|array $buildPaginator = true;
    protected bool $displayResultsOnlyIfSearch = false;
    protected bool $displayResults = true;
    protected array $twigFunctionsConfiguration = [];
    protected ?string $routeName = null;
    protected array $routeParams = [];
    protected string $divIdSearch = 'crud_search';
    protected string $divIdList = 'crud_list';

    public function __construct(protected string $sessionName, protected ContainerInterface $container)
    {
        if (!preg_match('/^[a-zA-Z0-9_]{1,50}$/', $sessionName)) {
            throw new \Exception('The session name format is invalid');
        }

        return $this;
    }

    /**
     * Inits the CRUD.
     */
    public function init(): void
    {
        if ($this->isInitialized) {
            throw new \Exception('CRUD already initialized');
        }
        $this->initializationInProgress = true;

        // Cheks not empty values
        $checkValues = [
            'availableColumns' => 'addColumn',
            'availableResultsPerPage' => 'setAvailableResultsPerPage',
            'defaultResultsPerPage' => 'setAvailableResultsPerPage',
            'defaultSort' => 'setDefaultSort',
            'defaultSense' => 'setDefaultSort',
            'queryBuilder' => 'setQueryBuilder',
            'routeName' => 'setRoute',
        ];
        foreach ($checkValues as $value => $method) {
            if (empty($this->$value)) {
                throw new \Exception(sprintf('The CRUD configuration is not complete. You must call the "%s" method', $method));
            }
        }

        if ($this->searchForm) {
            $this->searchForm->createForm();
        }

        // Loads user values inside this object
        $this->load();

        // Display or not results
        if ($this->searchForm && $this->displayResultsOnlyIfSearch) {
            $this->displayResults = $this->sessionValues->searchFormIsSubmittedAndValid;
        }

        $this->createDisplaySettingsForm();

        // Process request (resultsPerPage, sort, sense, change_columns)
        $this->processRequest();

        // Searcher form: Allocates object
        if ($this->searchForm && !$this->container->get('request_stack')->getCurrentRequest()->query->has('reset')) {
            // IMPORTANT
            // We have not to allocate directelly the "$this->sessionValues->searchFormData" object
            // because otherwise it will be linked to form, and will be updated when the "bind" function will
            // be called (If form is not valid, the session values will still be updated: Undesirable behavior)
            $values = clone $this->sessionValues->searchFormData;
            try {
                $this->searchForm->getForm()->setData($values);
            } catch (TransformationFailedException $exception) {
                // Avoid error if data stored in session is invalid
            }
        }

        // Saves
        $this->save();

        $this->initializationInProgress = false;
        $this->isInitialized = true;
    }

    public function isInitialized(): bool
    {
        return $this->isInitialized;
    }

    public function initIfNecessary(): void
    {
        if (!$this->isInitialized && !$this->initializationInProgress) {
            $this->init();
        }
    }

    protected function crudMustNotBeInitialized(): void
    {
        if (!$this->isInitialized) {
            return;
        }

        $caller = debug_backtrace()[1]['function'];
        throw new \Exception(sprintf('The method "%s" cannot be called after CRUD initialization', $caller));
    }

    protected function crudMustBeInitialized(): void
    {
        if ($this->isInitialized || $this->initializationInProgress) {
            return;
        }

        $caller = debug_backtrace()[1]['function'];
        throw new \Exception(sprintf('The method "%s" cannot be called before CRUD initialization', $caller));
    }

    /**
     * Add a column inside the crud.
     *
     * @param string $id      Column id (used everywhere inside the crud)
     * @param string $alias   Column SQL alias
     * @param string $label   Column label (used in the header table)
     * @param array  $options Options:
     *                        * sortable: If the column is sortable (Default: true)
     *                        * default_displayed: If the column is displayed, by default (Default: true)
     *                        * alias_search: Column SQL alias, used during searchs. If null, $alias is used.
     *                        * alias_sort: Column(s) SQL alias (string or array of strings), used during sorting. If null, $alias is used.
     */
    public function addColumn(string $id, string $alias, string $label, array $options = []): self
    {
        $this->crudMustNotBeInitialized();
        if (mb_strlen($id) > 100) {
            throw new \Exception(sprintf('The column id "%s" is too long', $id));
        }
        if (\array_key_exists($id, $this->availableColumns) || \array_key_exists($id, $this->availableVirtualColumns)) {
            throw new \Exception(sprintf('The column "%s" already exists', $id));
        }

        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            [
                'sortable' => true,
                'default_displayed' => true,
                'alias_search' => null,
                'alias_sort' => null,
            ]
        );
        $resolver->setAllowedTypes('sortable', 'bool');
        $resolver->setAllowedTypes('default_displayed', 'bool');
        $options = $resolver->resolve($options);

        $column = new CrudColumn(
            $id,
            $alias,
            $label,
            $options['sortable'],
            $options['default_displayed'],
            $options['alias_search'],
            $options['alias_sort']
        );
        $this->availableColumns[$id] = $column;

        return $this;
    }

    /**
     * Returns availabled columns.
     */
    public function getColumns(): array
    {
        return $this->availableColumns;
    }

    public function getColumn(string $columnId): CrudColumn
    {
        if (isset($this->availableColumns[$columnId])) {
            return $this->availableColumns[$columnId];
        }
        throw new \Exception(sprintf('The column "%s" does not exist', $columnId));
    }

    /**
     * Return default displayed columns.
     */
    public function getDefaultDisplayedColumns(): array
    {
        $columns = [];
        foreach ($this->availableColumns as $column) {
            if ($column->defaultDisplayed) {
                $columns[] = $column->id;
            }
        }
        if (0 == \count($columns)) {
            throw new \Exception('At least one column is required');
        }

        return $columns;
    }

    /**
     * Add a virtual column inside the crud.
     *
     * @param string $id          Column id (used everywhere inside the crud)
     * @param string $aliasSearch column SQL alias, used during searchs
     */
    public function addVirtualColumn(string $id, string $aliasSearch): self
    {
        $this->crudMustNotBeInitialized();
        if (\array_key_exists($id, $this->availableColumns) || \array_key_exists($id, $this->availableVirtualColumns)) {
            throw new \Exception(sprintf('The column "%s" already exists', $id));
        }
        $column = new CrudColumn($id, $aliasSearch, null, false, false, null, null);
        $this->availableVirtualColumns[$id] = $column;

        return $this;
    }

    /**
     * Returns availabled virtual columns.
     */
    public function getVirtualColumns(): array
    {
        return $this->availableVirtualColumns;
    }

    public function getVirtualColumn(string $columnId): CrudColumn
    {
        if (isset($this->availableVirtualColumns[$columnId])) {
            return $this->availableVirtualColumns[$columnId];
        }
        throw new \Exception(sprintf('The column "%s" does not exist', $columnId));
    }

    public function getQueryBuilder(): \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder|QueryBuilderInterface|null
    {
        return $this->queryBuilder;
    }

    public function setQueryBuilder(\Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder|QueryBuilderInterface $queryBuilder): self
    {
        $this->crudMustNotBeInitialized();
        $this->queryBuilder = $queryBuilder;

        return $this;
    }

    public function getAvailableResultsPerPage(): array
    {
        return $this->availableResultsPerPage;
    }

    /**
     * Return default results per page.
     */
    public function getDefaultResultsPerPage(): ?int
    {
        return $this->defaultResultsPerPage;
    }

    public function setAvailableResultsPerPage(array $availableResultsPerPage, int $defaultValue): self
    {
        $this->crudMustNotBeInitialized();
        $this->availableResultsPerPage = $availableResultsPerPage;
        $this->defaultResultsPerPage = $defaultValue;

        return $this;
    }

    public function getDefaultSort(): ?string
    {
        return $this->defaultSort;
    }

    public function getDefaultSense(): ?string
    {
        return $this->defaultSense;
    }

    /**
     * Set the default sort.
     *
     * @param string $sort  Column id
     * @param const  $sense Sense (Crud::ASC / Crud::DESC)
     */
    public function setDefaultSort(string $sort, string $sense): self
    {
        $this->crudMustNotBeInitialized();
        $this->defaultSort = $sort;
        $this->defaultSense = $sense;

        return $this;
    }

    public function getDefaultPersonalizedSort(): array
    {
        return $this->defaultPersonalizedSort;
    }

    /**
     * Set the default personalized sort.
     *
     * @param array $criterias Criterias :
     *                         If key is defined: Key = Sort  Value = Sense
     *                         If key is not defined: Value = Sort
     */
    public function setDefaultPersonalizedSort(array $criterias): self
    {
        $this->crudMustNotBeInitialized();
        $this->defaultPersonalizedSort = $criterias;

        $this->defaultSort = 'defaultPersonalizedSort';
        $this->defaultSense = self::ASC; // Used if not defined in criterias

        return $this;
    }

    public function setRoute(string $routeName, array $parameters = []): self
    {
        $this->crudMustNotBeInitialized();
        $this->routeName = $routeName;
        $this->routeParams = $parameters;

        return $this;
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    /**
     * Returns the list url.
     */
    public function getUrl(array $parameters = []): string
    {
        $parameters = array_merge($this->routeParams, $parameters);

        return $this->container->get('router')->generate($this->routeName, $parameters);
    }

    /**
     * Returns the search url.
     */
    public function getSearchUrl(array $parameters = []): string
    {
        $parameters = array_merge($this->routeParams, ['search' => 1], $parameters);

        return $this->container->get('router')->generate($this->routeName, $parameters);
    }

    public function getSessionName(): string
    {
        return $this->sessionName;
    }

    public function getDisplayResultsOnlyIfSearch(): bool
    {
        return $this->displayResultsOnlyIfSearch;
    }

    public function setDisplayResultsOnlyIfSearch(bool $displayResultsOnlyIfSearch): self
    {
        $this->crudMustNotBeInitialized();
        $this->displayResultsOnlyIfSearch = $displayResultsOnlyIfSearch;

        return $this;
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

    /**
     * Enables (or not) the auto build paginator.
     */
    public function setBuildPaginator(bool|\Closure|array $value): self
    {
        $this->crudMustNotBeInitialized();
        $this->buildPaginator = $value;

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

    /*
     * Use (or not) persistent settings.
     */
    public function setPersistentSettings(bool $value): self
    {
        $this->crudMustNotBeInitialized();
        if ($value && (null === $this->container->get('security.token_storage')->getToken() || !($this->container->get('security.token_storage')->getToken()->getUser() instanceof UserInterface))) {
            $value = false;
        }
        $this->persistentSettings = $value;

        return $this;
    }

    public function getSessionValues(): CrudSession
    {
        $this->crudMustBeInitialized();

        return $this->sessionValues;
    }

    /**
     * Returns the search form.
     *
     * @return SearchFormBuilder (before createView) or FormView (after createView)
     */
    public function getSearchForm(): SearchFormBuilder|FormView|null
    {
        return $this->searchForm;
    }

    public function createSearchForm(SearcherInterface $defaultData, ?string $type = null, array $options = []): self
    {
        $this->crudMustNotBeInitialized();
        $this->searchForm = new SearchFormBuilder($this->container, $this, $defaultData, $type, $options);

        return $this;
    }

    public function getDivIdSearch(): string
    {
        return $this->divIdSearch;
    }

    public function setDivIdSearch(string $divIdSearch): self
    {
        $this->crudMustNotBeInitialized();
        $this->divIdSearch = $divIdSearch;

        return $this;
    }

    public function getDivIdList(): string
    {
        return $this->divIdList;
    }

    public function setDivIdList(string $divIdList): self
    {
        $this->crudMustNotBeInitialized();
        $this->divIdList = $divIdList;

        return $this;
    }

    public function processSearchForm(): void
    {
        $this->crudMustBeInitialized();
        if (!$this->searchForm) {
            throw new NotFoundHttpException('Crud: Search form does not exist');
        }

        $request = $this->container->get('request_stack')->getCurrentRequest();
        if ($request->query->has('reset')) {
            return;
        }
        if ('POST' == $request->getMethod()) {
            if ($this->displayResultsOnlyIfSearch) {
                $this->displayResults = false;
            }
            $searchForm = $this->searchForm->getForm();
            $searchForm->handleRequest($request);
            if ($searchForm->isSubmitted() && $searchForm->isValid() && false !== $this->buildPaginator) {
                $this->displayResults = true;
                $this->sessionValues->searchFormIsSubmittedAndValid = true;
                $this->changeFilterValues($searchForm->getData());
                $this->changePage(1);
                $this->save();
            }
        }
    }

    public function build(): void
    {
        $this->crudMustBeInitialized();
        $this->updateQueryBuilder();
        $this->buildPaginator();
    }

    protected function updateQueryBuilder(): void
    {
        // Builds query
        $columnSortId = $this->sessionValues->sort;
        if ('defaultPersonalizedSort' == $columnSortId) {
            // Default personalised sort is used
            foreach ($this->defaultPersonalizedSort as $key => $value) {
                if (\is_int($key)) {
                    $sort = $value;
                    $sense = $this->defaultSense;
                } else {
                    $sort = $key;
                    $sense = $value;
                }
                $this->queryBuilder->addOrderBy($sort, $sense);
            }
        } else {
            $columnSortAlias = $this->availableColumns[$columnSortId]->aliasSort;
            if (\is_array($columnSortAlias)) {
                // Sort alias in many columns
                foreach ($columnSortAlias as $oneColumnSortAlias) {
                    $this->queryBuilder->addOrderBy($oneColumnSortAlias, $this->sessionValues->sense);
                }
            } else {
                // Sort alias in one column
                $this->queryBuilder->orderBy($columnSortAlias, $this->sessionValues->sense);
            }
        }

        // Adds form searcher filters
        if ($this->searchForm) {
            $this->searchForm->updateQueryBuilder($this->queryBuilder, $this->sessionValues->searchFormData);
        }
    }

    protected function buildPaginator(): void
    {
        if (!$this->displayResults || false === $this->buildPaginator) {
            return;
        }

        if (\is_object($this->buildPaginator) && $this->buildPaginator instanceof \Closure) {
            // Case: Manual paginator (by closure) is enabled
            $this->paginator = $this->buildPaginator->__invoke(
                $this->queryBuilder,
                $this->sessionValues->page,
                $this->sessionValues->resultsPerPage
            );

            return;
        }

        // Case: Auto paginator is enabled
        $paginatorOptions = [];
        if (\is_array($this->buildPaginator)) {
            $paginatorOptions = $this->buildPaginator;
        }

        $this->paginator = DoctrinePaginatorBuilder::createDoctrinePaginator(
            $this->queryBuilder,
            $this->sessionValues->page,
            $this->sessionValues->resultsPerPage,
            $paginatorOptions
        );
    }

    /**
     * Reset search form values.
     */
    public function reset(): void
    {
        $this->initIfNecessary();
        if ($this->searchForm) {
            $newValue = clone $this->searchForm->getDefaultData();
            $this->changeFilterValues($newValue);
            $this->searchForm->getForm()->setData(clone $newValue);
            $this->sessionValues->searchFormIsSubmittedAndValid = false;
        }
        $this->changePage(1);
        $this->save();

        if ($this->displayResultsOnlyIfSearch) {
            $this->displayResults = false;
        }
    }

    public function resetSort(): void
    {
        $this->initIfNecessary();
        $this->sessionValues->sense = $this->defaultSense;
        $this->sessionValues->sort = $this->defaultSort;
        $this->save();
    }

    protected function resetDisplaySettings(): void
    {
        $this->sessionValues->displayedColumns = $this->getDefaultDisplayedColumns();
        $this->sessionValues->resultsPerPage = $this->defaultResultsPerPage;
        $this->sessionValues->sense = $this->defaultSense;
        $this->sessionValues->sort = $this->defaultSort;

        if ($this->persistentSettings) {
            // Remove settings in database
            $qb = $this->container->get('doctrine')->getManager()->createQueryBuilder();
            $qb->delete('EcommitCrudBundle:UserCrudSettings', 's')
                ->andWhere('s.user = :user AND s.crudName = :crud_name')
                ->setParameters(['user' => $this->container->get('security.token_storage')->getToken()->getUser(), 'crud_name' => $this->sessionName])
                ->getQuery()
                ->execute();
        }
    }

    public function createView(): void
    {
        $this->crudMustBeInitialized();
        if ($this->searchForm) {
            $this->searchForm->createFormView();
            $this->searchForm = $this->searchForm->getForm();
        }
        $this->displaySettingsForm = $this->displaySettingsForm->createView();
    }

    public function getTwigFunctionsConfiguration(): array
    {
        return $this->twigFunctionsConfiguration;
    }

    public function getTwigFunctionConfiguration(string $function): array
    {
        if (isset($this->twigFunctionsConfiguration[$function])) {
            return $this->twigFunctionsConfiguration[$function];
        }

        return [];
    }

    public function setTwigFunctionsConfiguration(array $twigFunctionsConfiguration): self
    {
        $this->twigFunctionsConfiguration = $twigFunctionsConfiguration;

        return $this;
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
        $this->changeNumberResultsDisplayed($this->sessionValues->resultsPerPage);
        $this->changeColumnsDisplayed($this->sessionValues->displayedColumns);
        $this->changeSort($this->sessionValues->sort);
        $this->changeSense($this->sessionValues->sense);
        $this->changeFilterValues($this->sessionValues->searchFormData);
        $this->changePage($this->sessionValues->page);
    }

    /**
     * User Action: Changes number of displayed results.
     */
    protected function changeNumberResultsDisplayed(int $value): void
    {
        $oldValue = $this->sessionValues->resultsPerPage;
        if (\in_array($value, $this->availableResultsPerPage)) {
            $this->sessionValues->resultsPerPage = $value;
        } else {
            $this->sessionValues->resultsPerPage = $this->defaultResultsPerPage;
        }
        $this->testIfDatabaseMustMeUpdated($oldValue, $value);
    }

    /**
     * User Action: Changes displayed columns.
     */
    protected function changeColumnsDisplayed(array $value): void
    {
        $oldValue = $this->sessionValues->displayedColumns;
        $newDisplayedColumns = [];
        $availableColumns = $this->availableColumns;
        foreach ($value as $columnName) {
            if (\array_key_exists($columnName, $availableColumns)) {
                $newDisplayedColumns[] = $columnName;
            }
        }
        if (0 == \count($newDisplayedColumns)) {
            $newDisplayedColumns = $this->getDefaultDisplayedColumns();
        }
        $this->sessionValues->displayedColumns = $newDisplayedColumns;
        $this->testIfDatabaseMustMeUpdated($oldValue, $newDisplayedColumns);
    }

    /**
     * User Action: Changes sort.
     *
     * @param string $value Column id
     */
    protected function changeSort(mixed $value): void
    {
        $oldValue = $this->sessionValues->sort;
        $availableColumns = $this->availableColumns;
        if ((\is_scalar($value) && \array_key_exists($value, $availableColumns) && $availableColumns[$value]->sortable)
            || (\is_scalar($value) && 'defaultPersonalizedSort' === $value && $this->defaultPersonalizedSort)) {
            $this->sessionValues->sort = $value;
            $this->testIfDatabaseMustMeUpdated($oldValue, $value);
        } else {
            $this->sessionValues->sort = $this->defaultSort;
            $this->testIfDatabaseMustMeUpdated($oldValue, $this->defaultSort);
        }
    }

    /**
     * User action: Changes sense.
     */
    protected function changeSense(mixed $value): void
    {
        $oldValue = $this->sessionValues->sense;
        if (\is_scalar($value) && (self::ASC === $value || self::DESC === $value)) {
            $this->sessionValues->sense = $value;
            $this->testIfDatabaseMustMeUpdated($oldValue, $value);
        } else {
            $this->sessionValues->sense = $this->defaultSense;
            $this->testIfDatabaseMustMeUpdated($oldValue, $this->defaultSense);
        }
    }

    /**
     * User action: Changes search form values.
     */
    protected function changeFilterValues(SearcherInterface $value): void
    {
        if (!$this->searchForm) {
            return;
        }
        if (null !== $value && $value::class === \get_class($this->searchForm->getDefaultData())) {
            $this->sessionValues->searchFormData = $value;
        } else {
            $this->sessionValues->searchFormData = clone $this->searchForm->getDefaultData();
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
        $this->sessionValues->page = $value;
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

        $this->displaySettingsForm->handleRequest($request);
        if ($this->displaySettingsForm->isSubmitted() && $this->displaySettingsForm->isValid()) {
            $displaySettingsData = $this->displaySettingsForm->getData();
            $this->changeColumnsDisplayed($displaySettingsData['displayedColumns']);
            $this->changeNumberResultsDisplayed($displaySettingsData['resultsPerPage']);
        }
        if ($request->query->has('sort')) {
            $this->changeSort($request->query->get('sort'));
        }
        if ($request->query->has('sense')) {
            $this->changeSense($request->query->get('sense'));
        }
        if ($request->query->has('page')) {
            $this->changePage($request->query->get('page'));
        }
    }

    /**
     * Returns the search form.
     *
     * @return Form (before createView) or FormView (after createView)
     */
    public function getDisplaySettingsForm(): FormInterface|FormView|null
    {
        return $this->displaySettingsForm;
    }

    protected function createDisplaySettingsForm(): void
    {
        $resultsPerPageChoices = [];
        foreach ($this->getAvailableResultsPerPage() as $number) {
            $resultsPerPageChoices[$number] = $number;
        }
        $columnsChoices = [];
        foreach ($this->getColumns() as $column) {
            $columnsChoices[$column->id] = $column->label;
        }
        $data = [
            'resultsPerPage' => $this->getSessionValues()->resultsPerPage,
            'displayedColumns' => $this->getSessionValues()->displayedColumns,
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
        $object = $session->get($this->sessionName); // Load from session

        if (!empty($object)) {
            $this->sessionValues = $object;
            $this->checkCrudSession();

            return;
        }

        // If session is null => Assign default value
        $this->sessionValues = new CrudSession(
            $this->defaultResultsPerPage,
            $this->getDefaultDisplayedColumns(),
            $this->defaultSort,
            $this->defaultSense,
            ($this->searchForm) ? $this->searchForm->getDefaultData() : null,
        );

        // If persistent settings is enabled -> Retrieve from database
        if ($this->persistentSettings) {
            $objectDatabase = $this->container->get('doctrine')->getRepository('EcommitCrudBundle:UserCrudSettings')->findOneBy(
                [
                    'user' => $this->container->get('security.token_storage')->getToken()->getUser(),
                    'crudName' => $this->sessionName,
                ]
            );
            if ($objectDatabase) {
                $this->sessionValues = $objectDatabase->transformToCrudSession($this->sessionValues);
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
        $sessionValues = clone $this->sessionValues;
        if (\is_object($this->sessionValues->searchFormData)) {
            $sessionValues->searchFormData = clone $this->sessionValues->searchFormData;
        }
        $session->set($this->sessionName, $sessionValues);

        // Save in database
        if ($this->persistentSettings && $this->updateDatabase) {
            $objectDatabase = $this->container->get('doctrine')->getRepository('EcommitCrudBundle:UserCrudSettings')->findOneBy(
                [
                    'user' => $this->container->get('security.token_storage')->getToken()->getUser(),
                    'crudName' => $this->sessionName,
                ]
            );
            $em = $this->container->get('doctrine')->getManager();

            if ($objectDatabase) {
                // Update object in database
                $objectDatabase->updateFromSessionManager($this->sessionValues);
                $em->flush();
            } else {
                // Create object in database only if not default values
                if ($this->sessionValues->displayedColumns != $this->getDefaultDisplayedColumns() ||
                    $this->sessionValues->resultsPerPage != $this->defaultResultsPerPage ||
                    $this->sessionValues->sense != $this->defaultSense ||
                    $this->sessionValues->sort != $this->defaultSort
                ) {
                    $objectDatabase = new UserCrudSettings();
                    $objectDatabase->setUser($this->container->get('security.token_storage')->getToken()->getUser());
                    $objectDatabase->setCrudName($this->sessionName);
                    $objectDatabase->updateFromSessionManager($this->sessionValues);
                    $em->persist($objectDatabase);
                    $em->flush();
                }
            }
        }
    }
}

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
    protected Form|FormView|null $displaySettingsForm = null;
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
            throw new \Exception('Variable sessionName is not given or is invalid');
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
            'availableColumns',
            'availableResultsPerPage',
            'defaultSort',
            'defaultSense',
            'defaultResultsPerPage',
            'queryBuilder',
            'routeName',
        ];
        foreach ($checkValues as $value) {
            if (empty($this->$value)) {
                throw new \Exception('Config Crud: Option '.$value.' is required');
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
        if ($this->searchForm && !$this->container->get('request_stack')->getCurrentRequest()->query->has('raz')) {
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
        if (mb_strlen($id) > 100) {
            throw new \Exception('Column id is too long');
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
     * Add a virtual column inside the crud.
     *
     * @param string $id          Column id (used everywhere inside the crud)
     * @param string $aliasSearch column SQL alias, used during searchs
     */
    public function addVirtualColumn(string $id, string $aliasSearch): self
    {
        $column = new CrudColumn($id, $aliasSearch, null, false, false, null, null);
        $this->availableVirtualColumns[$id] = $column;

        return $this;
    }

    public function getQueryBuilder(): \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder|QueryBuilderInterface|null
    {
        return $this->queryBuilder;
    }

    public function setQueryBuilder(\Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder|QueryBuilderInterface $queryBuilder): self
    {
        $this->queryBuilder = $queryBuilder;

        return $this;
    }

    public function getAvailableResultsPerPage(): array
    {
        return $this->availableResultsPerPage;
    }

    public function setAvailableResultsPerPage(array $availableResultsPerPage, int $defaultValue): self
    {
        $this->availableResultsPerPage = $availableResultsPerPage;
        $this->defaultResultsPerPage = $defaultValue;

        return $this;
    }

    /**
     * Set the default sort.
     *
     * @param string $sort  Column id
     * @param const  $sense Sense (Crud::ASC / Crud::DESC)
     */
    public function setDefaultSort(string $sort, string $sense): self
    {
        $this->defaultSort = $sort;
        $this->defaultSense = $sense;

        return $this;
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
        $this->defaultPersonalizedSort = $criterias;

        $this->defaultSort = 'defaultPersonalizedSort';
        $this->defaultSense = self::ASC; // Used if not defined in criterias

        return $this;
    }

    public function setRoute(string $routeName, array $parameters = []): self
    {
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

    /**
     * Enables (or not) the auto build paginator.
     */
    public function setBuildPaginator(bool|\Closure|array $value): self
    {
        $this->buildPaginator = $value;

        return $this;
    }

    /*
     * Use (or not) persistent settings.
     */
    public function setPersistentSettings(bool $value): self
    {
        if ($value && (null === $this->container->get('security.token_storage')->getToken() || !($this->container->get('security.token_storage')->getToken()->getUser() instanceof UserInterface))) {
            $value = false;
        }
        $this->persistentSettings = $value;

        return $this;
    }

    public function createSearchForm(SearcherInterface $defaultData, ?string $type = null, array $options = []): self
    {
        $this->searchForm = new SearchFormBuilder($this->container, $this, $defaultData, $type, $options);

        return $this;
    }

    /**
     * Process search form.
     */
    public function processForm(): void
    {
        if (!$this->searchForm) {
            throw new NotFoundHttpException('Crud: Search form does not exist');
        }

        $request = $this->container->get('request_stack')->getCurrentRequest();
        if ($request->query->has('raz')) {
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

    protected function testIfDatabaseMustMeUpdated(mixed $oldValue, mixed $new_value): void
    {
        if ($oldValue != $new_value) {
            $this->updateDatabase = true;
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
            throw new \Exception('Config Crud: One column displayed is required');
        }

        return $columns;
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
        foreach ($value as $column_name) {
            if (\array_key_exists($column_name, $availableColumns)) {
                $newDisplayedColumns[] = $column_name;
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
        if ($request->query->has('razsettings')) {
            // Reset display settings
            $this->razDisplaySettings();

            return;
        }
        if ($request->query->has('raz')) {
            $this->raz();

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

    public function createDisplaySettingsForm(): void
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
            'reset_settings_url' => $this->getUrl(['razsettings' => 1]),
        ]);
    }

    /**
     * Reset display settings.
     */
    protected function razDisplaySettings(): void
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

    /**
     * Reset search form values.
     */
    public function raz(): void
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

    /**
     * Reset sort.
     */
    public function razSort(): void
    {
        $this->initIfNecessary();
        $this->sessionValues->sense = $this->defaultSense;
        $this->sessionValues->sort = $this->defaultSort;
        $this->save();
    }

    /**
     * Builds the query.
     */
    public function buildQuery(): void
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
            if (empty($columnSortAlias)) {
                // Sort alias is not defined. Alias is used
                $columnSortAlias = $this->availableColumns[$columnSortId]->alias;
                $this->queryBuilder->orderBy($columnSortAlias, $this->sessionValues->sense);
            } elseif (\is_array($columnSortAlias)) {
                // Sort alias is defined in many columns
                foreach ($columnSortAlias as $oneColumnSortAlias) {
                    $this->queryBuilder->addOrderBy($oneColumnSortAlias, $this->sessionValues->sense);
                }
            } else {
                // Sort alias is defined in one column
                $this->queryBuilder->orderBy($columnSortAlias, $this->sessionValues->sense);
            }
        }

        // Adds form searcher filters
        if ($this->searchForm) {
            $this->searchForm->updateQueryBuilder($this->queryBuilder, $this->sessionValues->searchFormData);
        }

        // Builds paginator
        if ($this->displayResults) {
            if (\is_object($this->buildPaginator) && $this->buildPaginator instanceof \Closure) {
                // Case: Manual paginator (by closure) is enabled
                $this->paginator = $this->buildPaginator->__invoke(
                    $this->queryBuilder,
                    $this->sessionValues->page,
                    $this->sessionValues->resultsPerPage
                );
            } elseif (true === $this->buildPaginator || \is_array($this->buildPaginator)) {
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
        }
    }

    /**
     * Return default results per page.
     */
    public function getDefaultResultsPerPage(): ?int
    {
        return $this->defaultResultsPerPage;
    }

    /**
     * Clears this object, before sending it to template.
     */
    public function clearTemplate(): void
    {
        if ($this->searchForm) {
            $this->searchForm->createFormView();
            $this->searchForm = $this->searchForm->getForm();
        }
        $this->displaySettingsForm = $this->displaySettingsForm->createView();
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
        throw new \Exception('Crud: Column '.$columnId.' does not exist');
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
        throw new \Exception('Crud: Column '.$columnId.' does not exist');
    }

    public function getSessionValues(): CrudSession
    {
        return $this->sessionValues;
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

    /**
     * Returns the search form.
     *
     * @return SearchFormBuilder (before clearTemplate) or FormView (after clearTemplate)
     */
    public function getSearchForm(): SearchFormBuilder|FormView|null
    {
        return $this->searchForm;
    }

    /**
     * Returns the search form.
     *
     * @return Form (before clearTemplate) or FormView (after clearTemplate)
     */
    public function getDisplaySettingsForm(): Form|FormView|null
    {
        return $this->displaySettingsForm;
    }

    public function getDivIdSearch(): string
    {
        return $this->divIdSearch;
    }

    public function setDivIdSearch(string $divIdSearch): self
    {
        $this->divIdSearch = $divIdSearch;

        return $this;
    }

    public function getDivIdList(): string
    {
        return $this->divIdList;
    }

    public function setDivIdList(string $divIdList): self
    {
        $this->divIdList = $divIdList;

        return $this;
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

        return $this;
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
}

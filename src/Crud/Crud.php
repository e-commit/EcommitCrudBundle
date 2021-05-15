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

use Ecommit\CrudBundle\DoctrineExtension\Paginate;
use Ecommit\CrudBundle\Entity\UserCrudSettings;
use Ecommit\CrudBundle\Form\Searcher\SearcherInterface;
use Ecommit\CrudBundle\Form\Type\DisplaySettingsType;
use Psr\Container\ContainerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\User\UserInterface;

class Crud
{
    public const DESC = 'DESC';
    public const ASC = 'ASC';

    protected $sessionName;

    /**
     * @var CrudSession
     */
    protected $sessionValues;

    /**
     * @var SearchFormBuilder|FormView
     */
    protected $searchForm;

    /**
     * @var Form
     */
    protected $formDisplaySettings = null;

    protected $availableColumns = [];
    protected $availableVirtualColumns = [];
    protected $availableResultsPerPage = [];
    protected $defaultSort = null;
    protected $defaultPersonalizedSort = [];
    protected $defaultSense = null;
    protected $defaultResultsPerPage = null;

    protected $queryBuilder = null;
    protected $persistentSettings = false;
    protected $updateDatabase = false;
    protected $paginator = null;
    protected $buildPaginator = true;
    protected $displayResultsOnlyIfSearch = false;
    protected $displayResults = true;

    /*
     * Router
     */
    protected $routeName = null;
    protected $routeParams = [];

    protected $divIdSearch = 'crud_search';
    protected $divIdList = 'crud_list';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Constructor.
     *
     * @param string $sessionName Session name
     *
     * @return Crud
     */
    public function __construct($sessionName, ContainerInterface $container)
    {
        if (!preg_match('/^[a-zA-Z0-9_]{1,50}$/', $sessionName)) {
            throw new \Exception('Variable sessionName is not given or is invalid');
        }
        $this->sessionName = $sessionName;
        $this->container = $container;
        $this->sessionValues = new CrudSession();

        return $this;
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
     *
     * @return Crud
     */
    public function addColumn($id, $alias, $label, $options = [])
    {
        if (mb_strlen($id) > 30) {
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
     *
     * @return Crud
     */
    public function addVirtualColumn($id, $aliasSearch)
    {
        $column = new CrudColumn($id, $aliasSearch, null, false, false, null, null);
        $this->availableVirtualColumns[$id] = $column;

        return $this;
    }

    /**
     * Gets the query builder.
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * Sets the query builder.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return Crud
     */
    public function setQueryBuilder($queryBuilder)
    {
        if (!($queryBuilder instanceof \Doctrine\ORM\QueryBuilder) &&
            !($queryBuilder instanceof \Doctrine\DBAL\Query\QueryBuilder) &&
            !($queryBuilder instanceof QueryBuilderInterface)) {
            throw new \Exception('Bad query builder');
        }
        $this->queryBuilder = $queryBuilder;

        return $this;
    }

    /**
     * Returns available results per page.
     *
     * @return array
     */
    public function getAvailableResultsPerPage()
    {
        return $this->availableResultsPerPage;
    }

    /**
     * Sets available results per page.
     *
     * @param int $defaultValue
     *
     * @return Crud
     */
    public function setAvailableResultsPerPage(array $availableResultsPerPage, $defaultValue)
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
     *
     * @return Crud
     */
    public function setDefaultSort($sort, $sense)
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
     *
     * @return Crud
     */
    public function setDefaultPersonalizedSort(array $criterias)
    {
        $this->defaultPersonalizedSort = $criterias;

        $this->defaultSort = 'defaultPersonalizedSort';
        $this->defaultSense = self::ASC; //Used if not defined in criterias

        return $this;
    }

    /**
     * Sets the list route.
     *
     * @param string $routeName
     * @param array  $parameters
     *
     * @return Crud
     */
    public function setRoute($routeName, $parameters = [])
    {
        $this->routeName = $routeName;
        $this->routeParams = $parameters;

        return $this;
    }

    /**
     * Returns the route name.
     *
     * @return string
     */
    public function getRouteName()
    {
        return $this->routeName;
    }

    /**
     * Returns the route params.
     *
     * @return array
     */
    public function getRouteParams()
    {
        return $this->routeParams;
    }

    /**
     * Returns the list url.
     *
     * @param array $parameters Additional parameters
     *
     * @return string
     */
    public function getUrl($parameters = [])
    {
        $parameters = array_merge($this->routeParams, $parameters);

        return $this->container->get('router')->generate($this->routeName, $parameters);
    }

    /**
     * Returns the search url.
     *
     * @param array $parameters Additional parameters
     *
     * @return string
     */
    public function getSearchUrl($parameters = [])
    {
        $parameters = array_merge($this->routeParams, ['search' => 1], $parameters);

        return $this->container->get('router')->generate($this->routeName, $parameters);
    }

    /**
     * Enables (or not) the auto build paginator.
     *
     * @param bool|closure|array $value
     */
    public function setBuildPaginator($value)
    {
        $this->buildPaginator = $value;

        return $this;
    }

    /*
     * Use (or not) persistent settings
     *
     * @param bool $value
     */
    public function setPersistentSettings($value)
    {
        if ($value && !($this->container->get('security.token_storage')->getToken()->getUser() instanceof UserInterface)) {
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
            if ($searchForm->isSubmitted() && $searchForm->isValid()) {
                $this->displayResults = true;
                $this->sessionValues->searchFormIsSubmitted = true;
                $this->changeFilterValues($searchForm->getData());
                $this->changePage(1);
                $this->save();
            }
        }
    }

    /**
     * User action: Changes search form values.
     *
     * @param object $value
     */
    protected function changeFilterValues($value): void
    {
        if (!$this->searchForm) {
            return;
        }
        if (\get_class($value) === \get_class($this->searchForm->getDefaultData())) {
            $this->sessionValues->searchFormData = $value;
        } else {
            $this->sessionValues->searchFormData = clone $this->searchForm->getDefaultData();
        }
    }

    /**
     * User action: Changes page number.
     *
     * @param string $value Page number
     */
    protected function changePage($value): void
    {
        if (!is_scalar($value)) {
            $value = 1;
        }
        $value = (int) $value;
        if ($value > 1000000000000) {
            $value = 1;
        }
        $this->sessionValues->page = $value;
    }

    /**
     * Saves user value.
     */
    protected function save(): void
    {
        //Save in session
        $session = $this->container->get('request_stack')->getCurrentRequest()->getSession();
        $sessionValues = clone $this->sessionValues;
        if (\is_object($this->sessionValues->searchFormData)) {
            $sessionValues->searchFormData = clone $this->sessionValues->searchFormData;
        }
        $session->set($this->sessionName, $sessionValues);

        //Save in database
        if ($this->persistentSettings && $this->updateDatabase) {
            $objectDatabase = $this->container->get('doctrine')->getRepository('EcommitCrudBundle:UserCrudSettings')->findOneBy(
                [
                    'user' => $this->container->get('security.token_storage')->getToken()->getUser(),
                    'crudName' => $this->sessionName,
                ]
            );
            $em = $this->container->get('doctrine')->getManager();

            if ($objectDatabase) {
                //Update object in database
                $objectDatabase->updateFromSessionManager($this->sessionValues);
                $em->flush();
            } else {
                //Create object in database only if not default values
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
     *
     * @return array
     */
    public function getDefaultDisplayedColumns()
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
     * Inits the CRUD.
     */
    public function init(): void
    {
        //Cheks not empty values
        $check_values = [
            'availableColumns',
            'availableResultsPerPage',
            'defaultSort',
            'defaultSense',
            'defaultResultsPerPage',
            'queryBuilder',
            'routeName',
        ];
        foreach ($check_values as $value) {
            if (empty($this->$value)) {
                throw new \Exception('Config Crud: Option '.$value.' is required');
            }
        }

        if ($this->searchForm) {
            $this->searchForm->createForm();
        }

        //Loads user values inside this object
        $this->load();

        //Display or not results
        if ($this->searchForm && $this->displayResultsOnlyIfSearch) {
            $this->displayResults = $this->sessionValues->searchFormIsSubmitted;
        }

        $this->createDisplaySettingsForm();

        //Process request (resultsPerPage, sort, sense, change_columns)
        $this->processRequest();

        //Searcher form: Allocates object
        if ($this->searchForm && !$this->container->get('request_stack')->getCurrentRequest()->query->has('raz')) {
            //IMPORTANT
            //We have not to allocate directelly the "$this->sessionValues->searchFormData" object
            //because otherwise it will be linked to form, and will be updated when the "bind" function will
            //be called (If form is not valid, the session values will still be updated: Undesirable behavior)
            $values = clone $this->sessionValues->searchFormData;
            try {
                $this->searchForm->getForm()->setData($values);
            } catch (TransformationFailedException $exception) {
                //Avoid error if data stored in session is invalid
            }
        }

        //Saves
        $this->save();
    }

    /**
     * Load user values.
     */
    protected function load(): void
    {
        $session = $this->container->get('request_stack')->getCurrentRequest()->getSession();
        $object = $session->get($this->sessionName); //Load from session

        if (!empty($object)) {
            $this->sessionValues = $object;
            $this->checkCrudSession();

            return;
        }

        //If session is null => Retrieve from database
        //Only if persistent settings is enabled
        if ($this->persistentSettings) {
            $objectDatabase = $this->container->get('doctrine')->getRepository('EcommitCrudBundle:UserCrudSettings')->findOneBy(
                [
                    'user' => $this->container->get('security.token_storage')->getToken()->getUser(),
                    'crudName' => $this->sessionName,
                ]
            );
            if ($objectDatabase) {
                $this->sessionValues = $objectDatabase->transformToCrudSession(new CrudSession());
                if ($this->searchForm) {
                    $this->sessionValues->searchFormData = clone $this->searchForm->getDefaultData();
                }
                $this->checkCrudSession();

                return;
            }
        }

        //Session and database values are null: Default values;
        $this->sessionValues->displayedColumns = $this->getDefaultDisplayedColumns();
        $this->sessionValues->resultsPerPage = $this->defaultResultsPerPage;
        $this->sessionValues->sense = $this->defaultSense;
        $this->sessionValues->sort = $this->defaultSort;
        if ($this->searchForm) {
            $this->sessionValues->searchFormData = clone $this->searchForm->getDefaultData();
        }
    }

    /**
     * Checks user values.
     */
    protected function checkCrudSession(): void
    {
        //Forces change => checks
        $this->changeNumberResultsDisplayed($this->sessionValues->resultsPerPage);
        $this->changeColumnsDisplayed($this->sessionValues->displayedColumns);
        $this->changeSort($this->sessionValues->sort);
        $this->changeSense($this->sessionValues->sense);
        $this->changeFilterValues($this->sessionValues->searchFormData);
        $this->changePage($this->sessionValues->page);
    }

    /**
     * User Action: Changes number of displayed results.
     *
     * @param int $value
     */
    protected function changeNumberResultsDisplayed($value): void
    {
        $oldValue = $this->sessionValues->resultsPerPage;
        if (\in_array($value, $this->availableResultsPerPage)) {
            $this->sessionValues->resultsPerPage = $value;
        } else {
            $this->sessionValues->resultsPerPage = $this->defaultResultsPerPage;
        }
        $this->testIfDatabaseMustMeUpdated($oldValue, $value);
    }

    protected function testIfDatabaseMustMeUpdated($oldValue, $new_value): void
    {
        if ($oldValue != $new_value) {
            $this->updateDatabase = true;
        }
    }

    /**
     * User Action: Changes displayed columns.
     *
     * @param array $value (columns id)
     */
    protected function changeColumnsDisplayed($value): void
    {
        $oldValue = $this->sessionValues->displayedColumns;
        if (!\is_array($value)) {
            $value = $this->getDefaultDisplayedColumns();
        }
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
    protected function changeSort($value): void
    {
        $oldValue = $this->sessionValues->sort;
        $availableColumns = $this->availableColumns;
        if ((is_scalar($value) && \array_key_exists($value, $availableColumns) && $availableColumns[$value]->sortable)
            || (is_scalar($value) && 'defaultPersonalizedSort' == $value && $this->defaultPersonalizedSort)) {
            $this->sessionValues->sort = $value;
            $this->testIfDatabaseMustMeUpdated($oldValue, $value);
        } else {
            $this->sessionValues->sort = $this->defaultSort;
            $this->testIfDatabaseMustMeUpdated($oldValue, $this->defaultSort);
        }
    }

    /**
     * User action: Changes sense.
     *
     * @param const $value Sens (ASC / DESC)
     */
    protected function changeSense($value): void
    {
        $oldValue = $this->sessionValues->sense;
        if (is_scalar($value) && (self::ASC == $value || self::DESC == $value)) {
            $this->sessionValues->sense = $value;
            $this->testIfDatabaseMustMeUpdated($oldValue, $value);
        } else {
            $this->sessionValues->sense = $this->defaultSense;
            $this->testIfDatabaseMustMeUpdated($oldValue, $this->defaultSense);
        }
    }

    /**
     * Process request.
     */
    protected function processRequest(): void
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        if ($request->query->has('razsettings')) {
            //Reset display settings
            $this->razDisplaySettings();

            return;
        }
        if ($request->query->has('raz')) {
            $this->raz();

            return;
        }

        $this->formDisplaySettings->handleRequest($request);
        if ($this->formDisplaySettings->isSubmitted() && $this->formDisplaySettings->isValid()) {
            $displaySettingsData = $this->formDisplaySettings->getData();
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

        $this->formDisplaySettings = $this->container->get('form.factory')->createNamed($formName, DisplaySettingsType::class, $data, [
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
            //Remove settings in database
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
        if ($this->searchForm) {
            $newValue = clone $this->searchForm->getDefaultData();
            $this->changeFilterValues($newValue);
            $this->searchForm->getForm()->setData(clone $newValue);
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
        $this->sessionValues->sense = $this->defaultSense;
        $this->sessionValues->sort = $this->defaultSort;
        $this->save();
    }

    /**
     * Builds the query.
     */
    public function buildQuery(): void
    {
        //Builds query
        $columnSortId = $this->sessionValues->sort;
        if ('defaultPersonalizedSort' == $columnSortId) {
            //Default personalised sort is used
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
                //Sort alias is not defined. Alias is used
                $columnSortAlias = $this->availableColumns[$columnSortId]->alias;
                $this->queryBuilder->orderBy($columnSortAlias, $this->sessionValues->sense);
            } elseif (\is_array($columnSortAlias)) {
                //Sort alias is defined in many columns
                foreach ($columnSortAlias as $oneColumnSortAlias) {
                    $this->queryBuilder->addOrderBy($oneColumnSortAlias, $this->sessionValues->sense);
                }
            } else {
                //Sort alias is defined in one column
                $this->queryBuilder->orderBy($columnSortAlias, $this->sessionValues->sense);
            }
        }

        //Adds form searcher filters
        if ($this->searchForm) {
            $this->searchForm->updateQueryBuilder($this->queryBuilder, $this->sessionValues->searchFormData);
        }

        //Builds paginator
        if ($this->displayResults) {
            if (\is_object($this->buildPaginator) && $this->buildPaginator instanceof \Closure) {
                //Case: Manual paginator (by closure) is enabled
                $this->paginator = $this->buildPaginator->__invoke(
                    $this->queryBuilder,
                    $this->sessionValues->page,
                    $this->sessionValues->resultsPerPage
                );
            } elseif (true === $this->buildPaginator || \is_array($this->buildPaginator)) {
                //Case: Auto paginator is enabled
                $paginatorOptions = [];
                if (\is_array($this->buildPaginator)) {
                    $paginatorOptions = $this->buildPaginator;
                }

                $this->paginator = Paginate::createDoctrinePaginator(
                    $this->queryBuilder,
                    $this->sessionValues->page,
                    $this->sessionValues->resultsPerPage,
                    $paginatorOptions
                );
                $this->paginator->init();
            }
        }
    }

    /**
     * Return default results per page.
     *
     * @return int
     */
    public function getDefaultResultsPerPage()
    {
        return $this->defaultResultsPerPage;
    }

    /**
     * Clears this object, before sending it to template.
     */
    public function clearTemplate(): void
    {
        $this->queryBuilder = null;
        if ($this->searchForm) {
            $this->searchForm->createFormView();
            $this->searchForm = $this->searchForm->getForm();
        }
        $this->formDisplaySettings = $this->formDisplaySettings->createView();
    }

    /**
     * Returns availabled columns.
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->availableColumns;
    }

    /**
     * Returns one column.
     *
     * @return CrudColumn $columnId
     */
    public function getColumn($columnId)
    {
        if (isset($this->availableColumns[$columnId])) {
            return $this->availableColumns[$columnId];
        }
        throw new \Exception('Crud: Column '.$columnId.' does not exist');
    }

    /**
     * Returns availabled virtual columns.
     *
     * @return array
     */
    public function getVirtualColumns()
    {
        return $this->availableVirtualColumns;
    }

    /**
     * Returns one virtual column.
     *
     * @return CrudColumn $columnId
     */
    public function getVirtualColumn($columnId)
    {
        if (isset($this->availableVirtualColumns[$columnId])) {
            return $this->availableVirtualColumns[$columnId];
        }
        throw new \Exception('Crud: Column '.$columnId.' does not exist');
    }

    /**
     * Returns user values.
     *
     * @return CrudSession
     */
    public function getSessionValues()
    {
        return $this->sessionValues;
    }

    /**
     * Returns the paginator.
     *
     * @return object
     */
    public function getPaginator()
    {
        return $this->paginator;
    }

    /**
     * Sets the paginator.
     *
     * @param object $value
     */
    public function setPaginator($value)
    {
        $this->paginator = $value;

        return $this;
    }

    /**
     * Returns the search form.
     *
     * @return SearchFormBuilder (before clearTemplate) or FormView (after clearTemplate)
     */
    public function getSearchForm()
    {
        return $this->searchForm;
    }

    /**
     * Returns the search form.
     *
     * @return Form (before clearTemplate) or FormView (after clearTemplate)
     */
    public function getDisplaySettingsForm()
    {
        return $this->formDisplaySettings;
    }

    /**
     * Returns the div id search.
     *
     * @return string
     */
    public function getDivIdSearch()
    {
        return $this->divIdSearch;
    }

    /**
     * Sets the div id search.
     *
     * @param string
     *
     * @return Crud
     */
    public function setDivIdSearch($divIdSearch)
    {
        $this->divIdSearch = $divIdSearch;

        return $this;
    }

    /**
     * Returns the div id list.
     *
     * @return string
     */
    public function getDivIdList()
    {
        return $this->divIdList;
    }

    /**
     * Sets the div id list.
     *
     * @param string
     *
     * @return Crud
     */
    public function setDivIdList($divIdList)
    {
        $this->divIdList = $divIdList;

        return $this;
    }

    /**
     * Gets session name.
     *
     * @return string
     */
    public function getSessionName()
    {
        return $this->sessionName;
    }

    /**
     * @return bool
     */
    public function getDisplayResultsOnlyIfSearch()
    {
        return $this->displayResultsOnlyIfSearch;
    }

    /**
     * @param bool $displayResultsOnlyIfSearch
     *
     * @return Crud
     */
    public function setDisplayResultsOnlyIfSearch($displayResultsOnlyIfSearch)
    {
        $this->displayResultsOnlyIfSearch = $displayResultsOnlyIfSearch;

        return $this;
    }

    /**
     * @return bool
     */
    public function getDisplayResults()
    {
        return $this->displayResults;
    }

    /**
     * @param bool $displayResults
     *
     * @return Crud
     */
    public function setDisplayResults($displayResults)
    {
        $this->displayResults = $displayResults;

        return $this;
    }
}

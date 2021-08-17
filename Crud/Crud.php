<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Crud;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Ecommit\CrudBundle\DoctrineExtension\Paginate;
use Ecommit\CrudBundle\Entity\UserCrudSettings;
use Ecommit\CrudBundle\Form\Searcher\AbstractFormSearcher;
use Ecommit\CrudBundle\Form\Type\FormSearchType;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\User\UserInterface;

class Crud
{
    const DESC = 'DESC';
    const ASC = 'ASC';

    protected $sessionName;

    /**
     * @var CrudSession
     */
    protected $sessionValues;

    /**
     * @var Form
     */
    protected $formSearcher = null;

    protected $availableColumns = array();
    protected $availableVirtualColumns = array();
    protected $availableResultsPerPage = array();
    protected $defaultSort = null;
    protected $defaultPersonalizedSort = array();
    protected $defaultSense = null;
    protected $defaultResultsPerPage = null;
    protected $defaultFormSearcherData = null;

    protected $queryBuilder = null;
    protected $useDbal = false; //Deprecated. Not used
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
    protected $routeParams = array();
    protected $searchRouteName = null;
    protected $searchRouteParams = array();

    protected $divIdSearch = 'crud_search';
    protected $divIdList = 'crud_list';

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var FormFactory
     */
    protected $formFactory;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var array
     */
    protected $templateConfiguration;

    /**
     * Constructor
     *
     * @param string $sessionName Session name
     * @return Crud
     */
    public function __construct(
        $sessionName,
        Router $router,
        FormFactory $formFactory,
        Request $request,
        Registry $registry,
        $user,
        array $templateConfiguration = array()
    ) {
        if (!\preg_match('/^[a-zA-Z0-9_]{1,50}$/', $sessionName)) {
            throw new \Exception('Variable sessionName is not given or is invalid');
        }
        $this->sessionName = $sessionName;
        $this->router = $router;
        $this->formFactory = $formFactory;
        $this->request = $request;
        $this->registry = $registry;
        $this->user = $user;
        $this->sessionValues = new CrudSession();
        foreach ($templateConfiguration as $functionName => $value) {
            $this->configureTemplate($functionName, $value);
        }

        return $this;
    }

    /**
     * Add a column inside the crud
     *
     * @param string $id Column id (used everywhere inside the crud)
     * @param string $alias Column SQL alias
     * @param string $label Column label (used in the header table)
     * @param array $options Options:
     *      * sortable: If the column is sortable (Default: true)
     *      * default_displayed: If the column is displayed, by default (Default: true)
     *      * alias_search: Column SQL alias, used during searchs. If null, $alias is used.
     *      * alias_sort: Column(s) SQL alias (string or array of strings), used during sorting. If null, $alias is used.
     * @return Crud
     */
    public function addColumn($id, $alias, $label, $options = array())
    {
        if (\strlen($id) > 30) {
            throw new \Exception('Column id is too long');
        }

        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            array(
                'sortable' => true,
                'default_displayed' => true,
                'alias_search' => null,
                'alias_sort' => null,
            )
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
     * Add a virtual column inside the crud
     *
     * @param string $id Column id (used everywhere inside the crud)
     * @param string $aliasSearch Column SQL alias, used during searchs.
     * @return Crud
     */
    public function addVirtualColumn($id, $aliasSearch)
    {
        $column = new CrudColumn($id, $aliasSearch, null, false, false, null, null);
        $this->availableVirtualColumns[$id] = $column;

        return $this;
    }

    /**
     * Gets the query builder
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * Sets the query builder
     *
     * @param QueryBuilder $queryBuilder
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
     * Returns available results per page
     *
     * @return array
     */
    public function getAvailableResultsPerPage()
    {
        return $this->availableResultsPerPage;
    }

    /**
     * Sets available results per page
     *
     * @param array $availableResultsPerPage
     * @param int $defaultValue
     * @return Crud
     */
    public function setAvailableResultsPerPage(Array $availableResultsPerPage, $defaultValue)
    {
        $this->availableResultsPerPage = $availableResultsPerPage;
        $this->defaultResultsPerPage = $defaultValue;

        return $this;
    }

    /**
     * Set the default sort
     *
     * @param string $sort Column id
     * @param const $sense Sense (Crud::ASC / Crud::DESC)
     * @return Crud
     */
    public function setDefaultSort($sort, $sense)
    {
        $this->defaultSort = $sort;
        $this->defaultSense = $sense;

        return $this;
    }

    /**
     * Set the default personalized sort
     *
     * @param array $criterias Criterias :
     *   If key is defined: Key = Sort  Value = Sense
     *   If key is not defined: Value = Sort
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
     * Sets the list route
     *
     * @param string $routeName
     * @param array $parameters
     * @return Crud
     */
    public function setRoute($routeName, $parameters = array())
    {
        $this->routeName = $routeName;
        $this->routeParams = $parameters;

        return $this;
    }

    /**
     * Returns the route name
     *
     * @return string
     */
    public function getRouteName()
    {
        return $this->routeName;
    }

    /**
     * Returns the route params
     *
     * @return array
     */
    public function getRouteParams()
    {
        return $this->routeParams;
    }

    /**
     * Returns the list url
     *
     * @param array $parameters Additional parameters
     * @return string
     */
    public function getUrl($parameters = array())
    {
        $parameters = \array_merge($this->routeParams, $parameters);

        return $this->router->generate($this->routeName, $parameters);
    }

    /**
     * Sets the search route
     *
     * @param string $routeName
     * @param array $parameters
     * @return Crud
     */
    public function setSearchRoute($routeName, $parameters = array())
    {
        $this->searchRouteName = $routeName;
        $this->searchRouteParams = $parameters;

        return $this;
    }

    /**
     * Returns the search url
     *
     * @param array $parameters Additional parameters
     * @return string
     */
    public function getSearchUrl($parameters = array())
    {
        $parameters = \array_merge($this->searchRouteParams, $parameters);

        return $this->router->generate($this->searchRouteName, $parameters);
    }

    /**
     * Enables (or not) the auto build paginator
     *
     * @param bool|closure|array $value
     */
    public function setBuildPaginator($value)
    {
        $this->buildPaginator = $value;

        return $this;
    }

    /*
     * Use (or not) DBAL
     *
     * @param bool $value
     * @deprecated Deprecated since version 2.3.
     */
    public function setUseDbal($value)
    {
        trigger_error('setUseDbal is deprecated since 2.3 version.', E_USER_DEPRECATED);

        $this->useDbal = $value;

        return $this;
    }

    /*
     * Use (or not) persistent settings
     *
     * @param bool $value
     */
    public function setPersistentSettings($value)
    {
        if ($value && !($this->user instanceof UserInterface)) {
            $value = false;
        }
        $this->persistentSettings = $value;

        return $this;
    }

    /**
     * Adds search form
     *
     * @param AbstractFormSearcher $defaultFormSearcherData
     * @param null|string $type The type of the form. If null, FormSearchType is used
     * @param array $options  The options
     * @return Crud
     */
    public function createSearcherForm(AbstractFormSearcher $defaultFormSearcherData, $type = null, $options = array())
    {
        $this->defaultFormSearcherData = $defaultFormSearcherData;
        $this->initializeFieldsFilter($defaultFormSearcherData);

        if ($type) {
            $formBuilder = $this->formFactory->createBuilder($type, null, $options);
        } else {
            $formName = sprintf('crud_search_%s', $this->sessionName);
            $formBuilder = $this->formFactory->createNamedBuilder($formName, FormSearchType::class, null, $options);
        }
        foreach ($defaultFormSearcherData->getFieldsFilter($this->registry) as $field) {
            if (!($field instanceof \Ecommit\CrudBundle\Form\Filter\AbstractFieldFilter)) {
                throw new \Exception(
                    'Crud: AbstractFormSearcher: getFieldsFilter() must only returns AbstractFieldFilter implementations'
                );
            }

            $formBuilder = $field->addField($formBuilder);
        }
        //Global
        $formBuilder = $defaultFormSearcherData->globalBuildForm($formBuilder);
        $this->formSearcher = $formBuilder;

        return $this;
    }

    /**
     * Process search form
     *
     */
    public function processForm()
    {
        if (empty($this->defaultFormSearcherData)) {
            throw new NotFoundHttpException('Crud: Form searcher does not exist');
        }

        if ($this->request->query->has('raz')) {
            return;
        }
        if ($this->request->getMethod() == 'POST') {
            if ($this->displayResultsOnlyIfSearch) {
                $this->displayResults = false;
            }
            $this->formSearcher->handleRequest($this->request);
            if ($this->formSearcher->isValid()) {
                $this->displayResults = true;
                $this->formSearcher->getData()->isSubmitted = true;
                $this->changeFilterValues($this->formSearcher->getData());
                $this->changePage(1);
                $this->save();
            }
        }
    }

    /**
     * User action: Changes search form values
     *
     * @param Object $value
     */
    protected function changeFilterValues($value)
    {
        if (empty($this->defaultFormSearcherData)) {
            return;
        }
        if (\get_class($value) == \get_class($this->defaultFormSearcherData)) {
            $this->sessionValues->formSearcherData = $value;
        } else {
            $this->sessionValues->formSearcherData = clone $this->defaultFormSearcherData;
        }
    }

    /**
     * User action: Changes page number
     *
     * @param string $value Page number
     */
    protected function changePage($value)
    {
        if (!is_scalar($value)) {
            $value = 1;
        }
        $value = \intval($value);
        if ($value > 1000000000000) {
            $value = 1;
        }
        $this->sessionValues->page = $value;
    }

    /**
     * Saves user value
     *
     */
    protected function save()
    {
        //Save in session
        $session = $this->request->getSession();
        $sessionValuesClean = clone $this->sessionValues;
        if (is_object($this->sessionValues->formSearcherData)) {
            $sessionValuesClean->formSearcherData = clone $this->sessionValues->formSearcherData;
            $sessionValuesClean->formSearcherData->clear();
        }
        $session->set($this->sessionName, $sessionValuesClean);

        //Save in database
        if ($this->persistentSettings && $this->updateDatabase) {
            $objectDatabase = $this->registry->getRepository('EcommitCrudBundle:UserCrudSettings')->findOneBy(
                array(
                    'user' => $this->user,
                    'crudName' => $this->sessionName
                )
            );
            $em = $this->registry->getManager();

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
                    $objectDatabase->setUser($this->user);
                    $objectDatabase->setCrudName($this->sessionName);
                    $objectDatabase->updateFromSessionManager($this->sessionValues);
                    $em->persist($objectDatabase);
                    $em->flush();
                }
            }
        }
    }

    /**
     * Return default displayed columns
     *
     * @return array
     */
    public function getDefaultDisplayedColumns()
    {
        $columns = array();
        foreach ($this->availableColumns as $column) {
            if ($column->defaultDisplayed) {
                $columns[] = $column->id;
            }
        }
        if (count($columns) == 0) {
            throw new \Exception('Config Crud: One column displayed is required');
        }

        return $columns;
    }

    /**
     * Inits the CRUD
     *
     */
    public function init()
    {
        //Cheks not empty values
        $check_values = array(
            'availableColumns',
            'availableResultsPerPage',
            'defaultSort',
            'defaultSense',
            'defaultResultsPerPage',
            'queryBuilder',
            'routeName'
        );
        if (!empty($this->defaultFormSearcherData)) {
            $check_values[] = 'searchRouteName';
        }
        foreach ($check_values as $value) {
            if (empty($this->$value)) {
                throw new \Exception('Config Crud: Option ' . $value . ' is required');
            }
        }

        if (!empty($this->defaultFormSearcherData)) {
            //Set url
            $this->formSearcher->setAction($this->getSearchUrl());
            //Transform FormBuilder to Form
            $this->formSearcher = $this->formSearcher->getForm();
        }

        //Loads user values inside this object
        $this->load();

        //Display or not results
        if (!empty($this->defaultFormSearcherData) && $this->displayResultsOnlyIfSearch) {
            $this->displayResults = $this->sessionValues->formSearcherData->isSubmitted;
        }

        //Process request (resultsPerPage, sort, sense, change_columns)
        $this->processRequest();

        //Searcher form: Allocates object (Defaults values and validators)
        if (!empty($this->defaultFormSearcherData) && !$this->request->query->has('raz')) {
            //IMPORTANT
            //We have not to allocate directelly the "$this->sessionValues->formSearcherData" object
            //because otherwise it will be linked to form, and will be updated when the "bind" function will
            //be called (If form is not valid, the session values will still be updated: Undesirable behavior)
            $values = clone $this->sessionValues->formSearcherData;
            $values->setFieldsFilter($this->defaultFormSearcherData->getFieldsFilter()); //Copy fields from defaultFormSearcherData to current data
            $values->setCommonOptions($this->defaultFormSearcherData->getCommonOptions());
            $this->formSearcher->setData($values);
        }

        //Saves
        $this->save();
    }

    /**
     * Init "fieldFilters" property in $formSearcherData object
     * Inject the registry in $formSearcherData objet if implements FieldFilterDoctrineInterface
     * @param AbstractFormSearcher $formSearcherData
     */
    protected function initializeFieldsFilter(AbstractFormSearcher $formSearcherData)
    {
        foreach ($formSearcherData->getFieldsFilter($this->registry) as $field) {
            if (!($field instanceof \Ecommit\CrudBundle\Form\Filter\AbstractFieldFilter)) {
                throw new \Exception(
                    'Crud: AbstractFormSearcher: getFieldsFilter() must only returns AbstractFieldFilter implementations'
                );
            }

            if (isset($this->availableColumns[$field->getColumnId()])) {
                $column = $this->availableColumns[$field->getColumnId()];
            } elseif (isset($this->availableVirtualColumns[$field->getColumnId()])) {
                $column = $this->availableVirtualColumns[$field->getColumnId()];
            } else {
                throw new \Exception(
                    'Crud: AbstractFormSearcher: getFieldsFilter(): Column id does not exit: ' . $field->getColumnId(
                    )
                );
            }

            $field->setLabel($column->label, $formSearcherData->displayLabelInErrors());
        }
    }


    /**
     * Load user values
     *
     */
    protected function load()
    {
        $session = $this->request->getSession();
        $object = $session->get($this->sessionName); //Load from session

        if (!empty($object)) {
            $this->sessionValues = $object;
            $this->checkCrudSession();
            if (!empty($this->defaultFormSearcherData)) {
                $object->formSearcherData->setCommonOptions($this->defaultFormSearcherData->getCommonOptions());
            }

            return;
        }

        //If session is null => Retrieve from database
        //Only if persistent settings is enabled
        if ($this->persistentSettings) {
            $objectDatabase = $this->registry->getRepository('EcommitCrudBundle:UserCrudSettings')->findOneBy(
                array(
                    'user' => $this->user,
                    'crudName' => $this->sessionName
                )
            );
            if ($objectDatabase) {
                $this->sessionValues = $objectDatabase->transformToCrudSession(new CrudSession());
                if (!empty($this->defaultFormSearcherData)) {
                    $this->sessionValues->formSearcherData = clone $this->defaultFormSearcherData;
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
        if (!empty($this->defaultFormSearcherData)) {
            $this->sessionValues->formSearcherData = clone $this->defaultFormSearcherData;
        }
    }

    /**
     * Checks user values
     */
    protected function checkCrudSession()
    {
        //Forces change => checks
        $this->changeNumberResultsDisplayed($this->sessionValues->resultsPerPage);
        $this->changeColumnsDisplayed($this->sessionValues->displayedColumns);
        $this->changeSort($this->sessionValues->sort);
        $this->changeSense($this->sessionValues->sense);
        $this->changeFilterValues($this->sessionValues->formSearcherData);
        $this->changePage($this->sessionValues->page);
    }

    /**
     * User Action: Changes number of displayed results
     *
     * @param int $value
     */
    protected function changeNumberResultsDisplayed($value)
    {
        $oldValue = $this->sessionValues->resultsPerPage;
        if (is_scalar($value) && in_array($value, $this->availableResultsPerPage)) {
            $this->sessionValues->resultsPerPage = $value;
        } else {
            $this->sessionValues->resultsPerPage = $this->defaultResultsPerPage;
        }
        $this->testIfDatabaseMustMeUpdated($oldValue, $value);
    }

    protected function testIfDatabaseMustMeUpdated($oldValue, $new_value)
    {
        if ($oldValue != $new_value) {
            $this->updateDatabase = true;
        }
    }

    /**
     * User Action: Changes displayed columns
     *
     * @param array $value (columns id)
     */
    protected function changeColumnsDisplayed($value)
    {
        $oldValue = $this->sessionValues->displayedColumns;
        if (!is_array($value)) {
            $value = $this->getDefaultDisplayedColumns();
        }
        $newDisplayedColumns = array();
        $availableColumns = $this->availableColumns;
        foreach ($value as $column_name) {
            if (is_scalar($column_name) && array_key_exists($column_name, $availableColumns)) {
                $newDisplayedColumns[] = $column_name;
            }
        }
        if (count($newDisplayedColumns) == 0) {
            $newDisplayedColumns = $this->getDefaultDisplayedColumns();
        }
        $this->sessionValues->displayedColumns = $newDisplayedColumns;
        $this->testIfDatabaseMustMeUpdated($oldValue, $newDisplayedColumns);
    }

    /**
     * User Action: Changes sort
     *
     * @param string $value Column id
     */
    protected function changeSort($value)
    {
        $oldValue = $this->sessionValues->sort;
        $availableColumns = $this->availableColumns;
        if ((is_scalar($value) && array_key_exists($value, $availableColumns) && $availableColumns[$value]->sortable)
            || (is_scalar($value) && $value == 'defaultPersonalizedSort' && $this->defaultPersonalizedSort)) {
            $this->sessionValues->sort = $value;
            $this->testIfDatabaseMustMeUpdated($oldValue, $value);
        } else {
            $this->sessionValues->sort = $this->defaultSort;
            $this->testIfDatabaseMustMeUpdated($oldValue, $this->defaultSort);
        }
    }

    /**
     * User action: Changes sense
     *
     * @param const $value Sens (ASC / DESC)
     */
    protected function changeSense($value)
    {
        $oldValue = $this->sessionValues->sense;
        if (is_scalar($value) && ($value == self::ASC || $value == self::DESC)) {
            $this->sessionValues->sense = $value;
            $this->testIfDatabaseMustMeUpdated($oldValue, $value);
        } else {
            $this->sessionValues->sense = $this->defaultSense;
            $this->testIfDatabaseMustMeUpdated($oldValue, $this->defaultSense);
        }
    }

    /**
     * Process request
     *
     */
    protected function processRequest()
    {
        if ($this->request->query->has('razsettings')) {
            //Reset display settings
            $this->razDisplaySettings();

            return;
        }
        if ($this->request->query->has('raz')) {
            $this->raz();

            return;
        }
        $displaySettingsFormName = sprintf('crud_display_settings_%s', $this->sessionName);
        if ($this->request->request->has($displaySettingsFormName)) {
            $displaySettings = $this->request->request->get($displaySettingsFormName);
            if (is_array($displaySettings) && isset($displaySettings['displayedColumns'])) {
                $this->changeColumnsDisplayed($displaySettings['displayedColumns']);
            }
            if (is_array($displaySettings) && isset($displaySettings['resultsPerPage'])) {
                $this->changeNumberResultsDisplayed($displaySettings['resultsPerPage']);
            }
        }
        if ($this->request->query->has('sort')) {
            $this->changeSort($this->request->query->get('sort'));
        }
        if ($this->request->query->has('sense')) {
            $this->changeSense($this->request->query->get('sense'));
        }
        if ($this->request->query->has('page')) {
            $this->changePage($this->request->query->get('page'));
        }
    }

    /**
     * Reset display settings
     *
     */
    protected function razDisplaySettings()
    {
        $this->sessionValues->displayedColumns = $this->getDefaultDisplayedColumns();
        $this->sessionValues->resultsPerPage = $this->defaultResultsPerPage;
        $this->sessionValues->sense = $this->defaultSense;
        $this->sessionValues->sort = $this->defaultSort;

        if ($this->persistentSettings) {
            //Remove settings in database
            $qb = $this->registry->getManager()->createQueryBuilder();
            $qb->delete('EcommitCrudBundle:UserCrudSettings', 's')
                ->andWhere('s.user = :user AND s.crudName = :crud_name')
                ->setParameters(array('user' => $this->user, 'crud_name' => $this->sessionName))
                ->getQuery()
                ->execute();
        }
    }

    /**
     * Reset search form values
     *
     */
    public function raz()
    {
        if ($this->defaultFormSearcherData) {
            $newValue = clone $this->defaultFormSearcherData;
            $this->changeFilterValues($newValue);
            $this->formSearcher->setData(clone $newValue);
        }
        $this->changePage(1);
        $this->save();

        if ($this->displayResultsOnlyIfSearch) {
            $this->displayResults = false;
        }
    }

    /**
     * Reset sort
     *
     */
    public function razSort()
    {
        $this->sessionValues->sense = $this->defaultSense;
        $this->sessionValues->sort = $this->defaultSort;
        $this->save();
    }

    /**
     * Builds the query
     *
     */
    public function buildQuery()
    {
        //Builds query
        $columnSortId = $this->sessionValues->sort;
        if ($columnSortId == 'defaultPersonalizedSort') {
            //Default personalised sort is used
            foreach ($this->defaultPersonalizedSort as $key => $value) {
                if (is_int($key)) {
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
            } elseif (is_array($columnSortAlias)) {
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
        if (!empty($this->defaultFormSearcherData)) {
            foreach ($this->defaultFormSearcherData->getFieldsFilter() as $field) {
                if (!($this->queryBuilder instanceof \Doctrine\ORM\QueryBuilder) &&
                    !($this->queryBuilder instanceof \Doctrine\DBAL\Query\QueryBuilder)) {
                    throw new \Exception('getFieldsFilter can not be used');
                }

                if (isset($this->availableColumns[$field->getColumnId()])) {
                    $column = $this->availableColumns[$field->getColumnId()];
                } elseif (isset($this->availableVirtualColumns[$field->getColumnId()])) {
                    $column = $this->availableVirtualColumns[$field->getColumnId()];
                } else {
                    throw new \Exception(
                        'Crud: AbstractFormSearcher: getFieldsFilter(): Column id does not exit: ' . $field->getColumnId(
                        )
                    );
                }

                //Get alias search
                if (empty($column->aliasSearch)) {
                    $aliasSearch = $column->alias;
                } else {
                    $aliasSearch = $column->aliasSearch;
                }

                $this->queryBuilder = $field->changeQuery(
                    $this->queryBuilder,
                    $this->sessionValues->formSearcherData,
                    $aliasSearch
                );
            }

            //Global change Query
            $this->queryBuilder = $this->sessionValues->formSearcherData->globalChangeQuery($this->queryBuilder);
        }


        //Builds paginator
        if ($this->displayResults) {
            if (is_object($this->buildPaginator) && $this->buildPaginator instanceof \Closure) {
                //Case: Manual paginator (by closure) is enabled
                $this->paginator = $this->buildPaginator->__invoke(
                    $this->queryBuilder,
                    $this->sessionValues->page,
                    $this->sessionValues->resultsPerPage
                );
            } elseif (true === $this->buildPaginator || is_array($this->buildPaginator)) {
                //Case: Auto paginator is enabled
                $paginatorOptions = array();
                if (is_array($this->buildPaginator)) {
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
     * Return default results per page
     * @return int
     */
    public function getDefaultResultsPerPage()
    {
        return $this->defaultResultsPerPage;
    }

    /**
     * Clears this object, before sending it to template
     *
     */
    public function clearTemplate()
    {
        $this->queryBuilder = null;
        $this->formFactory = null;
        $this->request = null;
        $this->registry = null;
        if (empty($this->defaultFormSearcherData)) {
            $this->formSearcher = null;
        } else {
            $this->formSearcher = $this->formSearcher->createView();
        }
        $this->defaultFormSearcherData = null;
    }

    /**
     * Returns availabled columns
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->availableColumns;
    }

    /**
     * Returns one column
     *
     * @return CrudColumn   $columnId
     */
    public function getColumn($columnId)
    {
        if (isset($this->availableColumns[$columnId])) {
            return $this->availableColumns[$columnId];
        }
        throw new \Exception('Crud: Column ' . $columnId . ' does not exist');
    }

    /**
     * Returns user values
     *
     * @return CrudSession
     */
    public function getSessionValues()
    {
        return $this->sessionValues;
    }

    /**
     * Returns the paginator
     * @return Object
     */
    public function getPaginator()
    {
        return $this->paginator;
    }

    /**
     * Sets the paginator
     *
     * @param Object $value
     */
    public function setPaginator($value)
    {
        $this->paginator = $value;

        return $this;
    }

    /**
     * Returns the search form
     *
     * @return FormBuilder (before init) or Form (before clearTemplate) or FormView (after clearTemplate)
     */
    public function getSearcherForm()
    {
        return $this->formSearcher;
    }

    /**
     * Returns the div id search
     *
     * @return string
     */
    public function getDivIdSearch()
    {
        return $this->divIdSearch;
    }

    /**
     * Sets the div id search
     *
     * @param string
     * @return Crud
     */
    public function setDivIdSearch($divIdSearch)
    {
        $this->divIdSearch = $divIdSearch;

        return $this;
    }

    /**
     * Returns the div id list
     *
     * @return string
     */
    public function getDivIdList()
    {
        return $this->divIdList;
    }

    /**
     * Sets the div id list
     *
     * @param string
     * @return Crud
     */
    public function setDivIdList($divIdList)
    {
        $this->divIdList = $divIdList;

        return $this;
    }

    /**
     * Gets session name
     *
     * @return string
     */
    public function getSessionName()
    {
        return $this->sessionName;
    }

    /**
     * @return boolean
     */
    public function getDisplayResultsOnlyIfSearch()
    {
        return $this->displayResultsOnlyIfSearch;
    }

    /**
     * @param boolean $displayResultsOnlyIfSearch
     * @return Crud
     */
    public function setDisplayResultsOnlyIfSearch($displayResultsOnlyIfSearch)
    {
        $this->displayResultsOnlyIfSearch = $displayResultsOnlyIfSearch;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getDisplayResults()
    {
        return $this->displayResults;
    }

    /**
     * @param bool $displayResults
     * @return Crud
     */
    public function setDisplayResults($displayResults)
    {
        $this->displayResults = $displayResults;

        return $this;
    }

    /**
     * @param string $functionName
     * @param mixed $value
     */
    public function configureTemplate($functionName, $value)
    {
        if (!self::validateConfigureTemplateFunctionName($functionName)) {
            throw new \Exception(\sprintf('%s method is not allowed in configureTemplate', $functionName));
        }

        if (isset($this->templateConfiguration[$functionName])) {
            $this->templateConfiguration[$functionName] = array_merge(
                $this->templateConfiguration[$functionName],
                $value
            );
        } else {
            $this->templateConfiguration[$functionName] = $value;
        }
    }

    /**
     * @param string $functionName
     * @return bool
     */
    public static function validateConfigureTemplateFunctionName($functionName)
    {
        $functionsAllowed = array(
            'paginator_links',
            'crud_paginator_links',
            'crud_th',
            'crud_td',
            'crud_search_reset',
            'crud_remote_modal',
            'crud_display_settings',
        );

        return in_array($functionName, $functionsAllowed);
    }

    /**
     * @param string $functionName
     * @return array
     */
    public function getTemplateConfiguration($functionName)
    {
        if (isset($this->templateConfiguration[$functionName])) {
            return $this->templateConfiguration[$functionName];
        }

        return array();
    }
}

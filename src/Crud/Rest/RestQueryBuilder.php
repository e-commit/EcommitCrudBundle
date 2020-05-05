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

namespace Ecommit\CrudBundle\Crud\Rest;

use Ecommit\CrudBundle\Crud\QueryBuilderInterface;
use Ecommit\CrudBundle\Crud\QueryBuilderParameterInterface;

class RestQueryBuilder implements QueryBuilderInterface
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var array
     */
    protected $queryParameters = [];

    /**
     * @var array
     */
    protected $formParameters = [];

    /**
     * @var string
     */
    protected $bodyParameter;

    /**
     * @var \Closure
     */
    protected $orderBuilder;

    /**
     * @var \Closure
     */
    protected $paginationBuilder;

    /**
     * @var array
     */
    protected $orders = [];

    /**
     * RestQueryBuilder constructor.
     *
     * @param string $url
     * @param string $method
     * @param array  $defaultParameters Array of RestQueryBuilderParameter objects
     */
    public function __construct($url, $method, $defaultParameters = [])
    {
        $this->url = $url;
        $this->method = $method;
        foreach ($defaultParameters as $defaultParameter) {
            $this->addParameter($defaultParameter);
        }
    }

    /**
     * @return $this
     */
    public function setOrderBuilder(\Closure $orderBuilder)
    {
        $this->orderBuilder = $orderBuilder;

        return $this;
    }

    /**
     * @return $this
     */
    public function setPaginationBuilder(\Closure $paginationBuilder)
    {
        $this->paginationBuilder = $paginationBuilder;

        return $this;
    }

    /**
     * @param string $parameter
     * @param string $value
     * @param string $method
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function addParameter(QueryBuilderParameterInterface $parameter)
    {
        if (!($parameter instanceof RestQueryBuilderParameter)) {
            throw new \Exception('Bad class');
        }

        switch ($parameter->method) {
            case 'get':
                $this->queryParameters[$parameter->name] = $parameter->value;
                break;
            case 'post':
                $this->formParameters[$parameter->name] = $parameter->value;
                break;
            case 'body':
                $this->bodyParameter = $parameter->value;
                break;
            default:
                throw new \Exception('Bad parameter method');
        }

        return $this;
    }

    /**
     * @param string $sort
     * @param string $sense
     *
     * @return $this
     */
    public function addOrderBy($sort, $sense)
    {
        $this->orders[$sort] = $sense;

        return $this;
    }

    /**
     * @param string $sort
     * @param string $sense
     *
     * @return $this
     */
    public function orderBy($sort, $sense)
    {
        $this->orders = [];
        $this->addOrderBy($sort, $sense);

        return $this;
    }

    /**
     * @param int   $page
     * @param int   $resultsPerPage
     * @param array $options
     *
     * @throws \Exception
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function getResponse($page, $resultsPerPage, $options = [])
    {
        $client = new \GuzzleHttp\Client();

        //Add paginator parameters
        if ($this->paginationBuilder && $this->paginationBuilder instanceof \Closure) {
            $parameters = $this->paginationBuilder->__invoke($page, $resultsPerPage);
            foreach ($parameters as $parameter) {
                $this->addParameter($parameter);
            }
        }

        //Add sort parameters
        if (\count($this->orders) > 0 && $this->orderBuilder && $this->orderBuilder instanceof \Closure) {
            $parameters = $this->orderBuilder->__invoke($this->orders);
            foreach ($parameters as $parameter) {
                $this->addParameter($parameter);
            }
        }

        //Add parameters to GuzzleHttp options
        foreach ($this->queryParameters as $parameterName => $parameterValue) {
            $options['query'][$parameterName] = $parameterValue;
        }
        foreach ($this->formParameters as $parameterName => $parameterValue) {
            $options['form_params'][$parameterName] = $parameterValue;
        }
        if ($this->bodyParameter) {
            $options['body'] = $this->bodyParameter;
        }

        return $client->request($this->method, $this->url, $options);
    }
}

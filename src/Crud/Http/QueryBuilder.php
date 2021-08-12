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

namespace Ecommit\CrudBundle\Crud\Http;

use Ecommit\CrudBundle\Crud\QueryBuilderInterface;
use Ecommit\CrudBundle\Crud\QueryBuilderParameterInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\ResponseInterface;

class QueryBuilder implements QueryBuilderInterface
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

    public function __construct(string $url, string $method, array $defaultParameters = [])
    {
        $this->url = $url;
        $this->method = $method;
        foreach ($defaultParameters as $defaultParameter) {
            $this->addParameter($defaultParameter);
        }
    }

    public function setOrderBuilder(\Closure $orderBuilder): self
    {
        $this->orderBuilder = $orderBuilder;

        return $this;
    }

    public function setPaginationBuilder(\Closure $paginationBuilder): self
    {
        $this->paginationBuilder = $paginationBuilder;

        return $this;
    }

    public function addParameter(QueryBuilderParameterInterface $parameter): self
    {
        if (!($parameter instanceof QueryBuilderParameter)) {
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

    public function addOrderBy(string $sort, string $sense): self
    {
        $this->orders[$sort] = $sense;

        return $this;
    }

    public function orderBy($sort, $sense): self
    {
        $this->orders = [];
        $this->addOrderBy($sort, $sense);

        return $this;
    }

    public function getResponse(int $page, int $resultsPerPage, array $options = []): ResponseInterface
    {
        $client = HttpClient::create();

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

        //Add parameters to client options
        foreach ($this->queryParameters as $parameterName => $parameterValue) {
            $options['query'][$parameterName] = $parameterValue;
        }
        foreach ($this->formParameters as $parameterName => $parameterValue) {
            $options['body'][$parameterName] = $parameterValue;
        }
        if ($this->bodyParameter) {
            $options['body'] = $this->bodyParameter;
        }

        return $client->request(mb_strtoupper($this->method), $this->url, $options);
    }
}

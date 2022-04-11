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
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class QueryBuilder implements QueryBuilderInterface
{
    protected array $queryParameters = [];
    protected array $bodyParameters = [];
    protected mixed $body = null;
    protected bool $bodyIsJson = false;
    protected array $headerParameters = [];
    protected ?\Closure $orderBuilder = null;
    protected ?\Closure $paginationBuilder = null;
    protected array $orders = [];

    public function __construct(protected string $url, protected string $httpMethod, protected ?HttpClientInterface $client = null)
    {
        if (null === $this->client) {
            $this->client = HttpClient::create();
        }
    }

    /**
     * @param \Closure $orderBuilder Closure aguments: $queryBuilder, $orders
     */
    public function setOrderBuilder(\Closure $orderBuilder): self
    {
        $this->orderBuilder = $orderBuilder;

        return $this;
    }

    /**
     * @param \Closure $orderBuilder Closure aguments: $queryBuilder, $page, $resultsPerPage
     */
    public function setPaginationBuilder(\Closure $paginationBuilder): self
    {
        $this->paginationBuilder = $paginationBuilder;

        return $this;
    }

    public function addParameter(QueryBuilderParameterInterface $parameter): self
    {
        if ($parameter instanceof QueryBuilderQueryParameter) {
            $this->queryParameters[] = $parameter;
        } elseif ($parameter instanceof QueryBuilderBodyParameter) {
            if (null !== $this->body) {
                throw new \Exception('Use QueryBuilderBodyParameter and QueryBuilderBody classes is not supported.');
            }
            $this->bodyParameters[] = $parameter;
        } elseif ($parameter instanceof QueryBuilderBody) {
            if (\count($this->bodyParameters) > 0) {
                throw new \Exception('Use QueryBuilderBodyParameter and QueryBuilderBody classes is not supported.');
            }
            $this->body = $parameter;
        } elseif ($parameter instanceof QueryBuilderHeaderParameter) {
            $this->headerParameters[] = $parameter;
        } else {
            throw new \Exception('Bad class');
        }

        return $this;
    }

    public function setBodyIsJson(bool $bodyIsJson): self
    {
        $this->bodyIsJson = $bodyIsJson;

        return $this;
    }

    public function addOrderBy(string $sort, string $sense): self
    {
        $this->orders[$sort] = $sense;

        return $this;
    }

    public function orderBy(string $sort, string $sense): self
    {
        $this->orders = [];
        $this->addOrderBy($sort, $sense);

        return $this;
    }

    public function getResponse(int $page, int $resultsPerPage, array $options = []): ResponseInterface
    {
        // Add paginator parameters
        if ($this->paginationBuilder) {
            $this->paginationBuilder->__invoke($this, $page, $resultsPerPage);
        }

        // Add sort parameters
        if (\count($this->orders) > 0 && $this->orderBuilder) {
            $this->orderBuilder->__invoke($this, $this->orders);
        }

        // Add parameters to client options
        /** @var QueryBuilderQueryParameter $parameter */
        foreach ($this->queryParameters as $parameter) {
            $options['query'][$parameter->name] = $parameter->value;
        }
        /** @var QueryBuilderBodyParameter $parameter */
        foreach ($this->bodyParameters as $parameter) {
            $bodyType = ($this->bodyIsJson) ? 'json' : 'body';
            $options[$bodyType][$parameter->name] = $parameter->value;
        }
        if ($this->body) {
            $bodyType = ($this->bodyIsJson) ? 'json' : 'body';
            $options[$bodyType] = $this->body->value;
        }
        /** @var QueryBuilderHeaderParameter $parameter */
        foreach ($this->headerParameters as $parameter) {
            $options['headers'][$parameter->name] = $parameter->value;
        }

        return $this->client->request(mb_strtoupper($this->httpMethod), $this->url, $options);
    }
}

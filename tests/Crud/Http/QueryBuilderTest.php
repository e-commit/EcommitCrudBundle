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

namespace Ecommit\CrudBundle\Tests\Crud\Http;

use Ecommit\CrudBundle\Crud\Crud;
use Ecommit\CrudBundle\Crud\Http\QueryBuilder;
use Ecommit\CrudBundle\Crud\Http\QueryBuilderBody;
use Ecommit\CrudBundle\Crud\Http\QueryBuilderBodyParameter;
use Ecommit\CrudBundle\Crud\Http\QueryBuilderHeaderParameter;
use Ecommit\CrudBundle\Crud\Http\QueryBuilderQueryParameter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\HttpClient\ResponseInterface;

class QueryBuilderTest extends TestCase
{
    public function testEmptyQueryBuilder(): void
    {
        $queryBuilder = $this->createQueryBuider();
        $response = $queryBuilder->getResponse(1, 1);

        $this->checkRequest($response, []);
    }

    public function testMethod(): void
    {
        $queryBuilder = $this->createQueryBuider('POST');
        $response = $queryBuilder->getResponse(1, 1);

        $this->checkRequest($response, [
            'method' => 'POST',
        ]);
    }

    public function testMethodQLowerCase(): void
    {
        $queryBuilder = $this->createQueryBuider('post');
        $response = $queryBuilder->getResponse(1, 1);

        $this->checkRequest($response, [
            'method' => 'POST',
        ]);
    }

    public function testAddQueryParameter(): void
    {
        $queryBuilder = $this->createQueryBuider();
        $queryBuilder->addParameter(new QueryBuilderQueryParameter('param1', 'val1'));
        $response = $queryBuilder->getResponse(1, 1);

        $this->checkRequest($response, [
            'url' => 'http://test/url?param1=val1',
        ]);
    }

    public function testAddBodyParameter(): void
    {
        $queryBuilder = $this->createQueryBuider();
        $queryBuilder->addParameter(new QueryBuilderBodyParameter('param1', 'val1'));
        $response = $queryBuilder->getResponse(1, 1);

        $this->checkRequest($response, [
            'body' => 'param1=val1',
        ]);
    }

    public function testAddBodyParameterNotAllowed(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Use QueryBuilderBodyParameter and QueryBuilderBody classes is not supported');

        $queryBuilder = $this->createQueryBuider();
        $queryBuilder->addParameter(new QueryBuilderBody('val1'));
        $queryBuilder->addParameter(new QueryBuilderBodyParameter('param1', 'val1'));
    }

    public function testAddBody(): void
    {
        $queryBuilder = $this->createQueryBuider();
        $queryBuilder->addParameter(new QueryBuilderBody('val1'));
        $response = $queryBuilder->getResponse(1, 1);

        $this->checkRequest($response, [
            'body' => 'val1',
        ]);
    }

    public function testAddBodyNotAllowed(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Use QueryBuilderBodyParameter and QueryBuilderBody classes is not supported');

        $queryBuilder = $this->createQueryBuider();
        $queryBuilder->addParameter(new QueryBuilderBodyParameter('param1', 'val1'));
        $queryBuilder->addParameter(new QueryBuilderBody('val1'));
    }

    public function testAddHeaderParameter(): void
    {
        $queryBuilder = $this->createQueryBuider();
        $queryBuilder->addParameter(new QueryBuilderHeaderParameter('param1', 'val1'));
        $response = $queryBuilder->getResponse(1, 1);

        $this->checkRequest($response, [
            'headers' => ['param1: val1'],
        ]);
    }

    public function testSetBodyIsJson(): void
    {
        $queryBuilder = $this->createQueryBuider();
        $queryBuilder->addParameter(new QueryBuilderBodyParameter('param1', 'val1'));
        $queryBuilder->setBodyIsJson(true);
        $response = $queryBuilder->getResponse(1, 1);

        $this->checkRequest($response, [
            'content_type_json' => true,
            'body' => '{"param1":"val1"}',
        ]);
    }

    public function testOrderBy(): void
    {
        $queryBuilder = $this->createQueryBuider();
        $queryBuilder->setOrderBuilder(function (QueryBuilder $queryBuilder, $orders): void {
            foreach ($orders as $sort => $sortDirection) {
                $queryBuilder->addParameter(new QueryBuilderBodyParameter('sort', $sort));
                $queryBuilder->addParameter(new QueryBuilderBodyParameter('sort-direction', $sortDirection));
            }
        });
        $queryBuilder->orderBy('col1', Crud::ASC);
        $response = $queryBuilder->getResponse(1, 1);

        $this->checkRequest($response, [
            'body' => 'sort=col1&sort-direction=ASC',
        ]);
    }

    public function testAddOrderBy(): void
    {
        $queryBuilder = $this->createQueryBuider();
        $queryBuilder->setOrderBuilder(function (QueryBuilder $queryBuilder, $orders): void {
            $paramSort = [];
            $paramSortDirection = [];
            foreach ($orders as $sort => $sortDirection) {
                $paramSort[] = $sort;
                $paramSortDirection[] = $sortDirection;
            }
            $queryBuilder->addParameter(new QueryBuilderBodyParameter('sort', implode(',', $paramSort)));
            $queryBuilder->addParameter(new QueryBuilderBodyParameter('sort-direction', implode(',', $paramSortDirection)));
        });
        $queryBuilder->addOrderBy('col1', Crud::ASC);
        $queryBuilder->addOrderBy('col2', Crud::DESC);
        $response = $queryBuilder->getResponse(1, 1);

        $this->checkRequest($response, [
            'body' => 'sort=col1%2Ccol2&sort-direction=ASC%2CDESC',
        ]);
    }

    public function testPagination(): void
    {
        $queryBuilder = $this->createQueryBuider();
        $queryBuilder->setPaginationBuilder(function (QueryBuilder $queryBuilder, $page, $resultsPerPage): void {
            $queryBuilder->addParameter(new QueryBuilderBodyParameter('page', $page));
            $queryBuilder->addParameter(new QueryBuilderBodyParameter('per_page', $resultsPerPage));
        });
        $response = $queryBuilder->getResponse(10, 100);

        $this->checkRequest($response, [
            'body' => 'page=10&per_page=100',
        ]);
    }

    public function testFull(): void
    {
        $queryBuilder = $this->createQueryBuider('POST');
        $queryBuilder->addParameter(new QueryBuilderQueryParameter('queryparam1', 'queryparamval1'));
        $queryBuilder->addParameter(new QueryBuilderBodyParameter('bodyparam1', 'bodyparamval1'));
        $queryBuilder->addParameter(new QueryBuilderHeaderParameter('headerparam1', 'headerparamval1'));
        $queryBuilder->setOrderBuilder(function (QueryBuilder $queryBuilder, $orders): void {
            foreach ($orders as $sort => $sortDirection) {
                $queryBuilder->addParameter(new QueryBuilderQueryParameter('sort', $sort));
                $queryBuilder->addParameter(new QueryBuilderQueryParameter('sort-direction', $sortDirection));
            }
        });
        $queryBuilder->setPaginationBuilder(function (QueryBuilder $queryBuilder, $page, $resultsPerPage): void {
            $queryBuilder->addParameter(new QueryBuilderQueryParameter('page', $page));
            $queryBuilder->addParameter(new QueryBuilderQueryParameter('per_page', $resultsPerPage));
        });

        $queryBuilder->orderBy('col1', Crud::ASC);
        $response = $queryBuilder->getResponse(10, 100);

        $this->checkRequest($response, [
            'method' => 'POST',
            'url' => 'http://test/url?queryparam1=queryparamval1&page=10&per_page=100&sort=col1&sort-direction=ASC',
            'body' => 'bodyparam1=bodyparamval1',
            'headers' => ['headerparam1: headerparamval1'],
        ]);
    }

    protected function createQueryBuider(string $method = 'GET'): QueryBuilder
    {
        $callback = function ($method, $url, $options): MockResponse {
            // Result : Returns request options
            $result = [
                'method' => $method,
                'url' => $url,
                'content_type_json' => false,
                'body' => null,
                'headers' => [],
            ];
            if (isset($options['body']) && null !== $options['body']) {
                $result['body'] = $options['body'];
            }
            if (isset($options['headers'])) {
                foreach ($options['headers'] as $header) {
                    if ('Content-Type: application/json' === $header) {
                        $result['content_type_json'] = true;
                    } elseif ('Accept: */*' !== $header) {
                        $result['headers'][] = $header;
                    }
                }
            }

            return new MockResponse(json_encode($result));
        };
        $client = new MockHttpClient($callback);

        return new QueryBuilder('http://test/url', $method, $client);
    }

    protected function checkRequest(ResponseInterface $response, array $options): void
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'method' => 'GET',
            'url' => 'http://test/url',
            'content_type_json' => false,
            'body' => null,
            'headers' => [],
        ]);
        $expectedOptions = $resolver->resolve($options);
        $options = json_decode($response->getContent(), true);

        ksort($expectedOptions);
        ksort($options);

        // Remove Content-Length header
        foreach ($options['headers'] as $index => $header) {
            if (preg_match('/^Content-Length/', $header)) {
                unset($options['headers'][$index]);
            }
        }

        $this->assertSame($expectedOptions, $options);
    }
}

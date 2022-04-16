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

namespace Ecommit\CrudBundle\Tests\Functional\Controller;

use Ecommit\CrudBundle\Crud\Crud;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\DomCrawler\Crawler;

trait TestTrait
{
    protected function countRowsAndColumns(Crawler $crawler): array
    {
        $rows = $crawler->filterXPath('//table[@class="result"]/tbody/tr');
        $countRows = \count($rows);
        $columns = $rows->first()->filterXPath('td');
        $countColumns = \count($columns);

        return [$countRows, $countColumns];
    }

    protected function getPagination(Crawler $crawler): array
    {
        $infos = $crawler->filterXPath('//div[@class="info-pagination"]')->text();

        preg_match('/^Results \d+\-\d+ \- Page (\d+)\/(\d+)$/', $infos, $groups);

        $page = (int) $groups[1];
        $countPages = (int) $groups[2];

        return [$page, $countPages];
    }

    protected function getSort(Crawler $crawler): array
    {
        $iSort = $crawler->filterXPath('//table[@class="result"]/thead/tr/th/a/i');
        if (0 === \count($iSort)) {
            return [];
        }
        $iSort = $iSort->first();

        switch ($iSort->text()) {
            case '^':
                $sortDirection = Crud::ASC;
                break;
            case 'v':
                $sortDirection = Crud::DESC;
                break;
            default:
                throw new \Exception('Bad sort direction');
        }

        $column = $iSort->filterXPath('ancestor::th')->last()->text();
        $column = str_replace(' '.$iSort->text(), '', $column);

        return [$column, $sortDirection];
    }

    protected function getFirstUsername(Crawler $crawler): ?string
    {
        $rows = $crawler->filterXPath('//table[@class="result"]/tbody/tr');
        if (0 === \count($rows)) {
            return null;
        }

        return $rows->first()->getAttribute('data-username');
    }

    protected function waitForAjax(Client $client, int $timeout = 5): void
    {
        $driver = $client->getWebDriver();

        $driver->wait($timeout, 500)->until(static fn ($driver) => !$driver->executeScript('return (typeof jQuery !== "undefined" && jQuery.active);'));

        usleep(500000);
    }
}

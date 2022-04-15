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
use Symfony\Component\Panther\PantherTestCase;

class TestCrudControllerTest extends PantherTestCase
{
    public const URL = '/user/';
    public const SESSION_NAME = 'user';
    public const SEARCH_IN_LIST = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::$defaultOptions['browser'] = static::FIREFOX;
    }

    public function testList(): Client
    {
        $client = static::createPantherClient();
        $client->request('GET', static::URL);

        $this->assertSame([5, 2], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 3], $this->getPagination($client->getCrawler()));
        $this->assertSame(['first_name', Crud::ASC], $this->getSort($client->getCrawler()));
        $this->assertSame('AudeJavel', $this->getFirstUsername($client->getCrawler()));
        $this->checkBeforeAndAfterBuild($client->getCrawler());

        return $client;
    }

    /**
     * @depends testList
     */
    public function testChangeSortSense(Client $client): Client
    {
        $link = $client->getCrawler()->filterXPath('//table[@class="result"]/thead/tr/th/a[contains(text(), "first_name")]');
        $link->click();
        $this->waitForAjax($client);

        $this->assertSame([5, 2], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 3], $this->getPagination($client->getCrawler()));
        $this->assertSame(['first_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('YvonEmbavé', $this->getFirstUsername($client->getCrawler()));
        $this->checkBeforeAndAfterBuild($client->getCrawler());

        return $client;
    }

    /**
     * @depends testChangeSortSense
     */
    public function testChangeSortColumn(Client $client): Client
    {
        $link = $client->getCrawler()->filterXPath('//table[@class="result"]/thead/tr/th/a[text()="last_name"]');
        $link->click();
        $this->waitForAjax($client);

        $this->assertSame([5, 2], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 3], $this->getPagination($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('ClémentTine', $this->getFirstUsername($client->getCrawler()));
        $this->checkBeforeAndAfterBuild($client->getCrawler());

        return $client;
    }

    /**
     * @depends testChangeSortColumn
     */
    public function testChangeDisplayedColumns(Client $client): Client
    {
        $button = $client->getCrawler()->filterXPath('//button[contains(.,"Display Settings")]');
        $button->first()->click();

        $client->getCrawler()->filterXPath('//form[@name="crud_display_settings_'.static::SESSION_NAME.'"]/descendant::input[@value="username"]')->click();
        $client->getCrawler()->filterXPath('//form[@name="crud_display_settings_'.static::SESSION_NAME.'"]/descendant::button[@type="submit"]')->click();
        $this->waitForAjax($client);

        $this->assertSame([5, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 3], $this->getPagination($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('ClémentTine', $this->getFirstUsername($client->getCrawler()));
        $this->checkBeforeAndAfterBuild($client->getCrawler());

        return $client;
    }

    /**
     * @depends testChangeDisplayedColumns
     */
    public function testChangePerPage(Client $client): Client
    {
        $button = $client->getCrawler()->filterXPath('//button[contains(., "Display Settings")]');
        $button->first()->click();

        $form = $client->getCrawler()->filterXPath('//form[@name="crud_display_settings_'.static::SESSION_NAME.'"]/descendant::button[@type="submit"]')->form();
        $form['crud_display_settings_'.static::SESSION_NAME.'[resultsPerPage]'] = 10;
        $client->submit($form);
        $this->waitForAjax($client);

        $this->assertSame([10, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 2], $this->getPagination($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('ClémentTine', $this->getFirstUsername($client->getCrawler()));
        $this->checkBeforeAndAfterBuild($client->getCrawler());

        return $client;
    }

    /**
     * @depends testChangePerPage
     */
    public function testSessionValuesAfterChangeSortAndSettings(Client $client): Client
    {
        $client->request('GET', static::URL);

        $this->assertSame([10, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 2], $this->getPagination($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('ClémentTine', $this->getFirstUsername($client->getCrawler()));
        $this->checkBeforeAndAfterBuild($client->getCrawler());

        return $client;
    }

    /**
     * @depends testSessionValuesAfterChangeSortAndSettings
     */
    public function testChangePage(Client $client): Client
    {
        $page = $client->getCrawler()->filterXPath('//ul[@class="ec-crud-pagination"]/li/a[text()="2"]');
        $page->first()->click();
        $this->waitForAjax($client);

        $this->assertSame([1, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([2, 2], $this->getPagination($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('JudieCieux', $this->getFirstUsername($client->getCrawler()));
        $this->checkBeforeAndAfterBuild($client->getCrawler());

        return $client;
    }

    /**
     * @depends testChangePage
     */
    public function testSessionValuesAfterChangePage(Client $client): Client
    {
        $client->request('GET', static::URL);

        $this->assertSame([1, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([2, 2], $this->getPagination($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('JudieCieux', $this->getFirstUsername($client->getCrawler()));
        $this->checkBeforeAndAfterBuild($client->getCrawler());

        return $client;
    }

    /**
     * @depends testSessionValuesAfterChangePage
     */
    public function testSearch(Client $client): Client
    {
        $form = $client->getCrawler()->filterXPath('//div[@id="crud_search"]/descendant::button[@type="submit" and contains(text(), "Search")]')->form();
        $form['crud_search_'.static::SESSION_NAME.'[firstName]'] = 'Henri';
        $client->submit($form);
        $this->waitForAjax($client);

        $this->assertSame([2, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 1], $this->getPagination($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('HenriPoste', $this->getFirstUsername($client->getCrawler()));
        $this->checkBeforeAndAfterBuild($client->getCrawler());

        return $client;
    }

    /**
     * @depends testSearch
     */
    public function testSessionValuesAfterSearch(Client $client): Client
    {
        $client->request('GET', static::URL);

        $this->assertSame([2, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 1], $this->getPagination($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('HenriPoste', $this->getFirstUsername($client->getCrawler()));
        $this->checkBeforeAndAfterBuild($client->getCrawler());

        return $client;
    }

    /**
     * @depends testSessionValuesAfterSearch
     */
    public function testSearchWithoutFilter(Client $client): Client
    {
        $form = $client->getCrawler()->filterXPath('//div[@id="crud_search"]/descendant::button[@type="submit" and contains(text(), "Search")]')->form();
        $form['crud_search_'.static::SESSION_NAME.'[lastName]'] = 'Plait';
        $client->submit($form);
        $this->waitForAjax($client);

        $this->assertSame([1, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 1], $this->getPagination($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('HenriPlait', $this->getFirstUsername($client->getCrawler()));
        $this->checkBeforeAndAfterBuild($client->getCrawler());

        return $client;
    }

    /**
     * @depends testSearchWithoutFilter
     */
    public function testResetSearch(Client $client): Client
    {
        $button = $client->getCrawler()->filterXPath('//div[@id="crud_search"]/descendant::button[contains(text(), "Reset")]');
        $button->first()->click();
        $this->waitForAjax($client);

        $this->assertSame([10, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 2], $this->getPagination($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('ClémentTine', $this->getFirstUsername($client->getCrawler()));
        $this->checkBeforeAndAfterBuild($client->getCrawler());

        return $client;
    }

    /**
     * @depends testResetSearch
     */
    public function testSessionValuesAfterResetSearch(Client $client): Client
    {
        $client->request('GET', static::URL);

        $this->assertSame([10, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 2], $this->getPagination($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('ClémentTine', $this->getFirstUsername($client->getCrawler()));
        $this->checkBeforeAndAfterBuild($client->getCrawler());

        return $client;
    }

    /**
     * @depends testSessionValuesAfterResetSearch
     */
    public function testResetSettings(Client $client): Client
    {
        $button = $client->getCrawler()->filterXPath('//button[contains(., "Display Settings")]');
        $button->first()->click();

        $button = $client->getCrawler()->filterXPath('//form[@name="crud_display_settings_'.static::SESSION_NAME.'"]/descendant::button[contains(., "Reset display settings")]');
        $button->first()->click();
        $this->waitForAjax($client);

        $this->assertSame([5, 2], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 3], $this->getPagination($client->getCrawler()));
        $this->assertSame(['first_name', Crud::ASC], $this->getSort($client->getCrawler()));
        $this->assertSame('AudeJavel', $this->getFirstUsername($client->getCrawler()));
        $this->checkBeforeAndAfterBuild($client->getCrawler());

        return $client;
    }

    /**
     * @depends testResetSettings
     */
    public function testSessionValuesAfterResetSettings(Client $client): Client
    {
        $client->request('GET', static::URL);

        $this->assertSame([5, 2], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 3], $this->getPagination($client->getCrawler()));
        $this->assertSame(['first_name', Crud::ASC], $this->getSort($client->getCrawler()));
        $this->assertSame('AudeJavel', $this->getFirstUsername($client->getCrawler()));
        $this->checkBeforeAndAfterBuild($client->getCrawler());

        return $client;
    }

    /**
     * @depends testSessionValuesAfterResetSettings
     */
    public function testManualReset(Client $client): Client
    {
        $client->request('GET', static::URL);

        // Display username column
        $button = $client->getCrawler()->filterXPath('//button[contains(.,"Display Settings")]');
        $button->first()->click();
        $client->getCrawler()->filterXPath('//form[@name="crud_display_settings_'.static::SESSION_NAME.'"]/descendant::input[@value="username"]')->click();
        $client->getCrawler()->filterXPath('//form[@name="crud_display_settings_'.static::SESSION_NAME.'"]/descendant::button[@type="submit"]')->click();
        $this->waitForAjax($client);
        $this->assertSame([5, 3], $this->countRowsAndColumns($client->getCrawler()));

        // Search
        $form = $client->getCrawler()->filterXPath('//div[@id="crud_search"]/descendant::button[@type="submit" and contains(text(), "Search")]')->form();
        $form['crud_search_'.static::SESSION_NAME.'[firstName]'] = 'Henri';
        $client->submit($form);
        $this->waitForAjax($client);
        $this->assertSame([2, 3], $this->countRowsAndColumns($client->getCrawler()));

        // Manuel RESET (before CRUD initialization)
        if (parse_url(static::URL, \PHP_URL_QUERY)) {
            $resetUrl = static::URL.'&manual-reset=1';
        } else {
            $resetUrl = static::URL.'?manual-reset=1';
        }
        $client->request('GET', $resetUrl);

        $this->assertSame([5, 3], $this->countRowsAndColumns($client->getCrawler())); // Reset rows but not columns

        return $client;
    }

    /**
     * @depends testManualReset
     */
    public function testManualResetSort(Client $client): Client
    {
        $client->request('GET', static::URL);

        // Search
        $form = $client->getCrawler()->filterXPath('//div[@id="crud_search"]/descendant::button[@type="submit" and contains(text(), "Search")]')->form();
        $form['crud_search_'.static::SESSION_NAME.'[firstName]'] = 'Henri';
        $client->submit($form);
        $this->waitForAjax($client);
        $this->assertSame([2, 3], $this->countRowsAndColumns($client->getCrawler()));

        // Sort
        $link = $client->getCrawler()->filterXPath('//table[@class="result"]/thead/tr/th/a[text()="last_name"]');
        $link->click();
        $this->waitForAjax($client);
        $this->assertSame([2, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame(['last_name', Crud::ASC], $this->getSort($client->getCrawler()));

        // Manuel RESET (before CRUD initialization)
        if (parse_url(static::URL, \PHP_URL_QUERY)) {
            $resetUrl = static::URL.'&manual-reset-sort=1';
        } else {
            $resetUrl = static::URL.'?manual-reset-sort=1';
        }
        $client->request('GET', $resetUrl);

        $this->assertSame([2, 3], $this->countRowsAndColumns($client->getCrawler())); // Not reset display settings and search
        $this->assertSame(['first_name', Crud::ASC], $this->getSort($client->getCrawler()));

        return $client;
    }

    protected function checkBeforeAndAfterBuild(Crawler $crawler): void
    {
        if (null === static::SEARCH_IN_LIST) {
            $this->assertCount(0, $crawler->filterXPath('//div[contains(text(), "TEST BEFORE AFTER BUILD")]'));

            return;
        }

        $this->assertCount(1, $crawler->filterXPath('//div[contains(text(), "TEST BEFORE AFTER BUILD '.static::SEARCH_IN_LIST.'")]'));
    }

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
                $sense = Crud::ASC;
                break;
            case 'v':
                $sense = Crud::DESC;
                break;
            default:
                throw new \Exception('Bad sense');
        }

        $column = $iSort->filterXPath('ancestor::th')->last()->text();
        $column = str_replace(' '.$iSort->text(), '', $column);

        return [$column, $sense];
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

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
use Ecommit\CrudBundle\Entity\UserCrudSettings;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;

class TestCrudControllerWithPersistentSettingsTest extends PantherTestCase
{
    use TestTrait;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::$defaultOptions['browser'] = static::FIREFOX;
    }

    public function testListWithUserWithoutPersistent(): Client
    {
        $client = static::createPantherClient();
        $this->login($client, 'EveReste');
        $client->request('GET', '/user-with-persistent-settings/private/no');

        // Check default values
        $this->assertSame([5, 2], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame(['first_name', Crud::ASC], $this->getSort($client->getCrawler()));

        $this->checkUserCrudSettingsDatabase(
            username: 'EveReste',
            maxPerPage: 50,
            displayedColumns: ['username', 'firstName'],
            sort: 'firstName',
            sortDirection: Crud::DESC
        );

        return $client;
    }

    /**
     * @depends testListWithUserWithoutPersistent
     */
    public function testChangeSettingsWithUserWithoutPersistent($client): Client
    {
        $this->changeSettings(
            client: $client,
            headerColumnClicks: ['last_name', 'last_name'],
            displayColumnClicks: ['username'],
            resultsPerPage: 10
        );

        $this->assertSame([10, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));

        $this->checkUserCrudSettingsDatabase(
            username: 'EveReste',
            maxPerPage: 50,
            displayedColumns: ['username', 'firstName'],
            sort: 'firstName',
            sortDirection: Crud::DESC
        );

        return $client;
    }

    /**
     * @depends testChangeSettingsWithUserWithoutPersistent
     */
    public function testResetSettingsWithUserWithoutPersistent($client): Client
    {
        $this->resetSettings($client);

        $this->checkUserCrudSettingsDatabase(
            username: 'EveReste',
            maxPerPage: 50,
            displayedColumns: ['username', 'firstName'],
            sort: 'firstName',
            sortDirection: Crud::DESC
        );

        $this->logout($client);

        return $client;
    }

    /**
     * @depends testResetSettingsWithUserWithoutPersistent
     */
    public function testListWithUserWithPersistent(Client $client): Client
    {
        $this->login($client, 'EveReste');
        $client->request('GET', '/user-with-persistent-settings/private/yes');

        // Check persistent values
        $this->assertSame([11, 2], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame(['first_name', Crud::DESC], $this->getSort($client->getCrawler()));

        $this->checkUserCrudSettingsDatabase(
            username: 'EveReste',
            maxPerPage: 50,
            displayedColumns: ['username', 'firstName'],
            sort: 'firstName',
            sortDirection: Crud::DESC
        );

        return $client;
    }

    /**
     * @depends testListWithUserWithPersistent
     */
    public function testChangeSettingsWithUserWithPersistent($client): Client
    {
        $this->changeSettings(
            client: $client,
            headerColumnClicks: ['username', 'username'],
            displayColumnClicks: ['lastName'],
            resultsPerPage: 10
        );

        $this->assertSame([10, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame(['username', Crud::ASC], $this->getSort($client->getCrawler()));

        $this->checkUserCrudSettingsDatabase(
            username: 'EveReste',
            maxPerPage: 10,
            displayedColumns: ['username', 'firstName', 'lastName'],
            sort: 'username',
            sortDirection: Crud::ASC
        );

        return $client;
    }

    /**
     * @depends testChangeSettingsWithUserWithPersistent
     */
    public function testResetSettingsWithUserWithPersistent($client): Client
    {
        $this->resetSettings($client);

        // Check default values
        $this->assertSame([5, 2], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame(['first_name', Crud::ASC], $this->getSort($client->getCrawler()));

        $this->assertNull($this->getUserCrudSettings('EveReste'));

        return $client;
    }

    /**
     * @depends testResetSettingsWithUserWithPersistent
     */
    public function testCreateSettingsWithUserWithPersistent($client): Client
    {
        $this->changeSettings(
            client: $client,
            headerColumnClicks: ['last_name', 'last_name'],
            displayColumnClicks: ['username'],
            resultsPerPage: 10
        );

        $this->assertSame([10, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));

        $this->checkUserCrudSettingsDatabase(
            username: 'EveReste',
            maxPerPage: 10,
            displayedColumns: ['firstName', 'lastName', 'username'],
            sort: 'lastName',
            sortDirection: Crud::DESC
        );

        $this->logout($client);

        return $client;
    }

    /**
     * @depends testCreateSettingsWithUserWithPersistent
     */
    public function testListWithoutUserWithPersistent(Client $client): Client
    {
        $client->request('GET', '/user-with-persistent-settings/public/yes');

        // Check default values
        $this->assertSame([5, 2], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame(['first_name', Crud::ASC], $this->getSort($client->getCrawler()));

        $this->checkUserCrudSettingsDatabase(
            username: 'EveReste',
            maxPerPage: 10,
            displayedColumns: ['firstName', 'lastName', 'username'],
            sort: 'lastName',
            sortDirection: Crud::DESC
        );

        return $client;
    }

    /**
     * @depends testListWithoutUserWithPersistent
     */
    public function testChangeSettingsWithoutUserWithPersistent($client): Client
    {
        $this->changeSettings(
            client: $client,
            headerColumnClicks: ['last_name', 'last_name'],
            displayColumnClicks: ['username'],
            resultsPerPage: 10
        );

        $this->assertSame([10, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));

        $this->checkUserCrudSettingsDatabase(
            username: 'EveReste',
            maxPerPage: 10,
            displayedColumns: ['firstName', 'lastName', 'username'],
            sort: 'lastName',
            sortDirection: Crud::DESC
        );

        return $client;
    }

    /**
     * @depends testChangeSettingsWithoutUserWithPersistent
     */
    public function testResetSettingsWithoutUserWithPersistent($client): Client
    {
        $this->resetSettings($client);

        $this->checkUserCrudSettingsDatabase(
            username: 'EveReste',
            maxPerPage: 10,
            displayedColumns: ['firstName', 'lastName', 'username'],
            sort: 'lastName',
            sortDirection: Crud::DESC
        );

        return $client;
    }

    protected function login(Client $client, string $username): void
    {
        $client->request('GET', '/login');

        $form = $client->getCrawler()->filterXPath('//button[@type="submit"]')->form();
        $form['_username'] = $username;
        $form['_password'] = $username;
        $client->submit($form);
    }

    protected function logout(Client $client): void
    {
        $client->request('GET', '/logout');
    }

    protected function changeSettings(Client $client, array $headerColumnClicks = [], array $displayColumnClicks = [], ?int $resultsPerPage = null): void
    {
        foreach ($headerColumnClicks as $column) {
            $link = $client->getCrawler()->filterXPath('//table[@class="result"]/thead/tr/th/a[contains(text(), "'.$column.'")]');
            $link->click();
            $this->waitForAjax($client);
        }

        if (\count($displayColumnClicks) > 0 || null !== $resultsPerPage) {
            $button = $client->getCrawler()->filterXPath('//button[contains(.,"Display Settings")]');
            $button->first()->click();
            $form = $client->getCrawler()->filterXPath('//form[@name="crud_display_settings_crud_persistent_settings"]/descendant::button[@type="submit"]')->form();

            foreach ($displayColumnClicks as $column) {
                $client->getCrawler()->filterXPath('//form[@name="crud_display_settings_crud_persistent_settings"]/descendant::input[@value="'.$column.'"]')->click();
            }

            if (null !== $resultsPerPage) {
                $form['crud_display_settings_crud_persistent_settings[resultsPerPage]'] = $resultsPerPage;
            }

            $client->submit($form);
            $this->waitForAjax($client);
        }
    }

    protected function resetSettings(Client $client): void
    {
        $button = $client->getCrawler()->filterXPath('//button[contains(., "Display Settings")]');
        $button->first()->click();

        $button = $client->getCrawler()->filterXPath('//form[@name="crud_display_settings_crud_persistent_settings"]/descendant::button[contains(., "Reset display settings")]');
        $button->first()->click();
        $this->waitForAjax($client);
    }

    protected function checkUserCrudSettingsDatabase(string $username, int $maxPerPage, array $displayedColumns, string $sort, string $sortDirection): void
    {
        $userCrudSettings = $this->getUserCrudSettings($username);
        $this->assertSame($maxPerPage, $userCrudSettings->getMaxPerPage());
        $this->assertSame($displayedColumns, $userCrudSettings->getDisplayedColumns());
        $this->assertSame($sort, $userCrudSettings->getSort());
        $this->assertSame($sortDirection, $userCrudSettings->getSortDirection());
    }

    protected function getUserCrudSettings(string $username): ?UserCrudSettings
    {
        $queryBuilder = static::getContainer()->get('doctrine')->getRepository(UserCrudSettings::class)->createQueryBuilder('ucs');

        return $queryBuilder->select('ucs')
            ->leftJoin('ucs.user', 'u')
            ->andWhere('u.username = :username')
            ->setParameter('username', $username)
            ->andWhere('ucs.crudName = :crudName')
            ->setParameter('crudName', 'crud_persistent_settings')
            ->getQuery()
            ->getOneOrNullResult();
    }
}

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

namespace Ecommit\CrudBundle\Tests\Crud;

use Ecommit\CrudBundle\Crud\Crud;
use Ecommit\CrudBundle\Crud\CrudSession;
use Ecommit\CrudBundle\Entity\UserCrudInterface;
use Ecommit\CrudBundle\Entity\UserCrudSettings;
use Ecommit\CrudBundle\Tests\Functional\App\Form\Searcher\UserSearcher;
use PHPUnit\Framework\TestCase;

class CrudSessionTest extends TestCase
{
    public function testCloneSearchFormDataInConstructor(): void
    {
        $searcherFormData = $this->createSearcher();
        $crudSession = $this->createCrudSession($searcherFormData);
        $this->assertNotSame($searcherFormData, $this->getPropertyWithoutGetter($crudSession, 'searchFormData'));
    }

    public function testCloneSearchFormDataInConstructorWithNullValue(): void
    {
        $searcherFormData = null;
        $crudSession = $this->createCrudSession($searcherFormData);
        $this->assertSame($searcherFormData, $this->getPropertyWithoutGetter($crudSession, 'searchFormData'));
    }

    public function testClone(): void
    {
        $crudSession1 = $this->createCrudSession($this->createSearcher());
        $crudSession2 = clone $crudSession1;

        $this->assertNotSame($crudSession1, $crudSession2);
        $this->assertEquals($crudSession1, $crudSession2);

        $this->assertNotSame(
            $this->getPropertyWithoutGetter($crudSession1, 'searchFormData'),
            $this->getPropertyWithoutGetter($crudSession2, 'searchFormData')
        );
        $this->assertEquals(
            $this->getPropertyWithoutGetter($crudSession1, 'searchFormData'),
            $this->getPropertyWithoutGetter($crudSession2, 'searchFormData')
        );

        $newSearcherFormData = $this->createSearcher('username2');
        $this->setPropertyWithoutGetter($crudSession1, 'searchFormData', $newSearcherFormData);
        $this->assertSame(
            $newSearcherFormData,
            $this->getPropertyWithoutGetter($crudSession1, 'searchFormData')
        );
        $this->assertNotSame(
            $newSearcherFormData,
            $this->getPropertyWithoutGetter($crudSession2, 'searchFormData')
        );
        $this->assertEquals(
            $newSearcherFormData,
            $this->getPropertyWithoutGetter($crudSession1, 'searchFormData')
        );
        $this->assertNotEquals(
            $newSearcherFormData,
            $this->getPropertyWithoutGetter($crudSession2, 'searchFormData')
        );

        $newDisplayedColumns = ['new'];
        $this->setPropertyWithoutGetter($crudSession1, 'displayedColumns', $newDisplayedColumns);
        $this->assertSame(
            $newDisplayedColumns,
            $this->getPropertyWithoutGetter($crudSession1, 'displayedColumns')
        );
        $this->assertNotSame(
            $newDisplayedColumns,
            $this->getPropertyWithoutGetter($crudSession2, 'displayedColumns')
        );
    }

    public function testUpdateFromUserCrudSettings(): void
    {
        $crudSession = $this->createCrudSession($this->createSearcher());

        $userCrudSettings = new UserCrudSettings(
            $this->createMock(UserCrudInterface::class),
            'crud_name',
            10,
            ['col2'],
            'sort2',
            Crud::DESC,
        );

        $newCrudSession = $crudSession->updateFromUserCrudSettings($userCrudSettings);
        $this->assertNotSame($crudSession, $newCrudSession);

        $this->assertSame(100, $this->getPropertyWithoutGetter($crudSession, 'maxPerPage'));
        $this->assertSame(10, $this->getPropertyWithoutGetter($newCrudSession, 'maxPerPage'));
        $this->assertSame(['col1'], $this->getPropertyWithoutGetter($crudSession, 'displayedColumns'));
        $this->assertSame(['col2'], $this->getPropertyWithoutGetter($newCrudSession, 'displayedColumns'));
        $this->assertSame('sort1', $this->getPropertyWithoutGetter($crudSession, 'sort'));
        $this->assertSame('sort2', $this->getPropertyWithoutGetter($newCrudSession, 'sort'));
        $this->assertSame(Crud::ASC, $this->getPropertyWithoutGetter($crudSession, 'sortDirection'));
        $this->assertSame(Crud::DESC, $this->getPropertyWithoutGetter($newCrudSession, 'sortDirection'));
        $this->assertNotSame(
            $this->getPropertyWithoutGetter($crudSession, 'searchFormData'),
            $this->getPropertyWithoutGetter($newCrudSession, 'searchFormData')
        );
        $this->assertEquals(
            $this->getPropertyWithoutGetter($crudSession, 'searchFormData'),
            $this->getPropertyWithoutGetter($newCrudSession, 'searchFormData')
        );
    }

    public function testUpdateUserCrudSettings(): void
    {
        $crudSession = $this->createCrudSession($this->createSearcher());

        $userCrudSettings = new UserCrudSettings(
            $this->createMock(UserCrudInterface::class),
            'crud_name',
            10,
            ['col2'],
            'sort2',
            Crud::DESC,
        );
        $crudSession->updateUserCrudSettings($userCrudSettings);

        $this->assertSame(100, $userCrudSettings->getResultsDisplayed());
        $this->assertSame(['col1'], $userCrudSettings->getDisplayedColumns());
        $this->assertSame('sort1', $userCrudSettings->getSort());
        $this->assertSame(Crud::ASC, $userCrudSettings->getSortDirection());
    }

    public function testMaxPerPage(): void
    {
        $crudSession = $this->createCrudSession();
        $newCrudSession = $crudSession->setMaxPerPage(1000);

        $this->assertNotSame($crudSession, $newCrudSession);
        $this->assertSame(100, $crudSession->getMaxPerPage());
        $this->assertSame(1000, $newCrudSession->getMaxPerPage());
    }

    public function testDisplayedColumns(): void
    {
        $crudSession = $this->createCrudSession();
        $newCrudSession = $crudSession->setDisplayedColumns(['new_column']);

        $this->assertNotSame($crudSession, $newCrudSession);
        $this->assertSame(['col1'], $crudSession->getDisplayedColumns());
        $this->assertSame(['new_column'], $newCrudSession->getDisplayedColumns());
    }

    public function testSort(): void
    {
        $crudSession = $this->createCrudSession();
        $newCrudSession = $crudSession->setSort('new_sort');

        $this->assertNotSame($crudSession, $newCrudSession);
        $this->assertSame('sort1', $crudSession->getSort());
        $this->assertSame('new_sort', $newCrudSession->getSort());
    }

    public function testSortDirection(): void
    {
        $crudSession = $this->createCrudSession();
        $newCrudSession = $crudSession->setSortDirection(Crud::DESC);

        $this->assertNotSame($crudSession, $newCrudSession);
        $this->assertSame(Crud::ASC, $crudSession->getSortDirection());
        $this->assertSame(Crud::DESC, $newCrudSession->getSortDirection());
    }

    public function testSearchFormData(): void
    {
        $searcherFormData = $this->createSearcher();
        $crudSession = $this->createCrudSession($searcherFormData);

        $this->assertNotSame($searcherFormData, $crudSession->getSearchFormData());
        $this->assertEquals($searcherFormData, $crudSession->getSearchFormData());

        $newSearcherFormData = $this->createSearcher('username2');
        $newCrudSession = $crudSession->setSearchFormData($newSearcherFormData);

        $this->assertNotSame($crudSession, $newCrudSession);
        $this->assertNotSame($searcherFormData, $crudSession->getSearchFormData());
        $this->assertEquals($searcherFormData, $crudSession->getSearchFormData());
        $this->assertNotSame($newSearcherFormData, $newCrudSession->getSearchFormData());
        $this->assertEquals($newSearcherFormData, $newCrudSession->getSearchFormData());
    }

    public function testGetSearchFormDataNull(): void
    {
        $crudSession = $this->createCrudSession();

        $this->assertNull($crudSession->getSearchFormData());
    }

    public function testPage(): void
    {
        $crudSession = $this->createCrudSession();
        $newCrudSession = $crudSession->setPage(2);

        $this->assertNotSame($crudSession, $newCrudSession);
        $this->assertSame(1, $crudSession->getPage());
        $this->assertSame(2, $newCrudSession->getPage());
    }

    public function testSearchFormIsSubmittedAndValid(): void
    {
        $crudSession = $this->createCrudSession();
        $newCrudSession = $crudSession->setSearchFormIsSubmittedAndValid(true);

        $this->assertNotSame($crudSession, $newCrudSession);
        $this->assertFalse($crudSession->isSearchFormIsSubmittedAndValid());
        $this->assertTrue($newCrudSession->isSearchFormIsSubmittedAndValid());
    }

    protected function getPropertyWithoutGetter(CrudSession $crudSession, string $property): mixed
    {
        $reflectionMethod = (new \ReflectionClass(CrudSession::class))->getProperty($property);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->getValue($crudSession);
    }

    protected function setPropertyWithoutGetter(CrudSession $crudSession, string $property, mixed $value): void
    {
        $reflectionMethod = (new \ReflectionClass(CrudSession::class))->getProperty($property);
        $reflectionMethod->setAccessible(true);

        $reflectionMethod->setValue($crudSession, $value);
    }

    protected function createCrudSession(?UserSearcher $searchFormData = null): CrudSession
    {
        return new CrudSession(100, ['col1'], 'sort1', Crud::ASC, $searchFormData);
    }

    protected function createSearcher(?string $username = 'username'): UserSearcher
    {
        $searcher = new UserSearcher();
        $searcher->username = $username;

        return $searcher;
    }
}

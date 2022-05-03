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

use Ecommit\CrudBundle\Crud\CrudColumn;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class CrudColumnTest extends TestCase
{
    public function testMissingIdOption(): void
    {
        $options = $this->createValidConfig();
        unset($options['id']);

        $this->expectException(MissingOptionsException::class);
        new CrudColumn($options);
    }

    public function testIdTooLongOption(): void
    {
        $options = $this->createValidConfig();
        $options['id'] = str_pad('', 101, 'a');

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessageMatches('/The column id ".+" is too long. It should have 100 character or less/');
        new CrudColumn($options);
    }

    public function testMissingAliasOption(): void
    {
        $options = $this->createValidConfig();
        unset($options['id']);

        $this->expectException(MissingOptionsException::class);
        new CrudColumn($options);
    }

    public function testDefaultLabelOption(): void
    {
        $crudColumn = new CrudColumn($this->createValidConfig());
        $this->assertSame('id1', $crudColumn->getLabel());
    }

    public function testDefaultSortableOption(): void
    {
        $crudColumn = new CrudColumn($this->createValidConfig());
        $this->assertTrue($crudColumn->isSortable());
    }

    public function testDefaultDisplayedByDefaultOption(): void
    {
        $crudColumn = new CrudColumn($this->createValidConfig());
        $this->assertTrue($crudColumn->isDisplayedByDefault());
    }

    public function testDefaultAliasSortOption(): void
    {
        $crudColumn = new CrudColumn($this->createValidConfig());
        $this->assertSame('alias1', $crudColumn->getAliasSort());
    }

    public function testDefaultAliasSearchOption(): void
    {
        $crudColumn = new CrudColumn($this->createValidConfig());
        $this->assertSame('alias1', $crudColumn->getAliasSearch());
    }

    public function testGetId(): void
    {
        $options = $this->createValidConfig();
        $options['id'] = 'val';
        $crudColumn = new CrudColumn($options);
        $this->assertSame('val', $crudColumn->getId());
    }

    public function testGetAlias(): void
    {
        $options = $this->createValidConfig();
        $options['alias'] = 'val';
        $crudColumn = new CrudColumn($options);
        $this->assertSame('val', $crudColumn->getAlias());
    }

    public function testGetLabel(): void
    {
        $options = $this->createValidConfig();
        $options['label'] = 'val';
        $crudColumn = new CrudColumn($options);
        $this->assertSame('val', $crudColumn->getLabel());
    }

    /**
     * @dataProvider getBooleanProvier
     */
    public function testIsSortable(bool $value): void
    {
        $options = $this->createValidConfig();
        $options['sortable'] = $value;
        $crudColumn = new CrudColumn($options);
        $this->assertSame($value, $crudColumn->isSortable());
    }

    /**
     * @dataProvider getBooleanProvier
     */
    public function testIsDisplayedByDefault(bool $value): void
    {
        $options = $this->createValidConfig();
        $options['displayed_by_default'] = $value;
        $crudColumn = new CrudColumn($options);
        $this->assertSame($value, $crudColumn->isDisplayedByDefault());
    }

    public function testGetAliasSort(): void
    {
        $options = $this->createValidConfig();
        $options['alias_sort'] = 'val';
        $crudColumn = new CrudColumn($options);
        $this->assertSame('val', $crudColumn->getAliasSort());
    }

    public function testGetAliasSearch(): void
    {
        $options = $this->createValidConfig();
        $options['alias_search'] = 'val';
        $crudColumn = new CrudColumn($options);
        $this->assertSame('val', $crudColumn->getAliasSearch());
    }

    public function getBooleanProvier(): array
    {
        return [[true], [false]];
    }

    protected function createValidConfig(): array
    {
        return [
            'id' => 'id1',
            'alias' => 'alias1',
        ];
    }
}

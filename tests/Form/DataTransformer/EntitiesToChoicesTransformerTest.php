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

namespace Ecommit\CrudBundle\Tests\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Ecommit\CrudBundle\Form\DataTransformer\Entity\EntitiesToChoicesTransformer;
use Ecommit\CrudBundle\Tests\Functional\App\Entity\EntityManyToOne;
use Ecommit\CrudBundle\Tests\Functional\App\Entity\Tag;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class EntitiesToChoicesTransformerTest extends AbstractEntityTransformerTest
{
    protected function createTransformer(...$args): DataTransformerInterface
    {
        return new EntitiesToChoicesTransformer(...$args);
    }

    /**
     * @dataProvider getTestTransformProvider
     */
    public function testTransform($choiceLabel, array $expected): void
    {
        $collection = new ArrayCollection();
        $collection->add($this->em->find(Tag::class, 1));
        $collection->add($this->em->find(Tag::class, 3));

        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'id', $choiceLabel, false, 10);

        $this->assertSame($expected, $transformer->transform($collection));
    }

    public function getTestTransformProvider(): array
    {
        $closure = fn (Tag $tag) => sprintf('name: %s', $tag->getName());

        return [
            [null, ['1' => 'tag1', '3' => '3']], // Choice label: null
            ['name', ['1' => 'tag1', '3' => '3']], // Choice label: property
            [$closure, [1 => 'name: tag1', '3' => 'name: 3']], // Choice label: closure
        ];
    }

    public function testTransformWithChoiceLabelError(): void
    {
        $entity = $this->em->getRepository(EntityManyToOne::class)->find(1);

        $collection = new ArrayCollection();
        $collection->add($entity);

        $queryBuilder = $this->em->getRepository(EntityManyToOne::class)->createQueryBuilder('e')->select('e');
        $transformer = $this->createTransformer($queryBuilder, 'id', null, false, 10);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('"choice_label" option or "__toString" method must be defined"');
        $transformer->transform($collection);
    }

    public function testTransformNullValue(): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'id', null, false, 10);

        $this->assertSame([], $transformer->transform(null));
    }

    public function testTransformNotCollection(): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'id', null, false, 10);

        $this->expectException(UnexpectedTypeException::class);
        $transformer->transform([]);
    }

    public function testReverseTransform(): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'id', null, false, 10);

        $collection = $transformer->reverseTransform(['1', '3']);
        $this->assertEquals(new ArrayCollection([
            $this->em->find(Tag::class, 1),
            $this->em->find(Tag::class, 3),
        ]), $collection);
    }

    /**
     * @dataProvider getTestReverseTransformNullValueProvider
     */
    public function testReverseTransformNullValue($value): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'id', null, false, 10);

        $this->assertEquals(new ArrayCollection(), $transformer->reverseTransform($value));
    }

    public function getTestReverseTransformNullValueProvider(): array
    {
        return [
            [''],
            [null],
            [[]],
        ];
    }

    public function testReverseTransformNotArray(): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'id', null, false, 10);

        $this->expectException(TransformationFailedException::class);
        $this->assertEquals(new ArrayCollection(), $transformer->reverseTransform('string'));
    }

    public function testReverseTransformRemoveNotScalarValues(): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'id', null, false, 10);

        $collection = $transformer->reverseTransform(['1', ['val'], '3']);
        $this->assertEquals(new ArrayCollection([
            $this->em->find(Tag::class, 1),
            $this->em->find(Tag::class, 3),
        ]), $collection);
    }

    public function testReverseTransformWithEntityNotFoundIgnore(): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'id', null, false, 10);

        $collection = $transformer->reverseTransform(['1', '999999', '3']);
        $this->assertEquals(new ArrayCollection([
            $this->em->find(Tag::class, 1),
            $this->em->find(Tag::class, 3),
        ]), $collection);
    }

    public function testReverseTransformWithEntityNotFoundThrowsException(): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'id', null, true, 10);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Entities not found');
        $transformer->reverseTransform(['1', '999999', '3']);
    }

    public function testReverseTransformWithAllEntitiesNotFoundIgnore(): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'id', null, false, 10);

        $collection = $transformer->reverseTransform(['1000', '999999', '3000']);
        $this->assertEquals(new ArrayCollection(), $collection);
    }

    public function testReverseTransformWithAllEntitiesNotFoundThrowsException(): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'id', null, true, 10);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Entities not found');
        $transformer->reverseTransform(['1000', '999999', '3000']);
    }

    public function testReverseTransformWithDuplicates(): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'id', null, false, 10);

        $collection = $transformer->reverseTransform(['1', '1', '3']);
        $this->assertEquals(new ArrayCollection([
            $this->em->find(Tag::class, 1),
            $this->em->find(Tag::class, 3),
        ]), $collection);
    }

    public function testReverseTransformMaxError(): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'id', null, false, 2);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('This collection should contain 2 elements or less.');
        $transformer->reverseTransform(['1', '2', '3']);
    }

    public function testReverseTransformQueryError(): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'badIdentifier', null, false, 10);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Tranformation: Query Error');
        $transformer->reverseTransform(['1', '1', '3']);
    }
}

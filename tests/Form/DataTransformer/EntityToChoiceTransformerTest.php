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
use Ecommit\CrudBundle\Form\DataTransformer\Entity\EntityToChoiceTransformer;
use Ecommit\CrudBundle\Tests\Functional\App\Entity\EntityManyToOne;
use Ecommit\CrudBundle\Tests\Functional\App\Entity\Tag;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class EntityToChoiceTransformerTest extends AbstractEntityTransformerTest
{
    protected function createTransformer(...$args): DataTransformerInterface
    {
        return new EntityToChoiceTransformer(...$args);
    }

    /**
     * @dataProvider getTestTransformProvider
     */
    public function testTransform($choiceLabel, $expected): void
    {
        $entity = $this->em->find(Tag::class, 3);

        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'id', $choiceLabel, false);

        $this->assertSame($expected, $transformer->transform($entity));
    }

    public function getTestTransformProvider(): array
    {
        $closure = fn (Tag $tag) => sprintf('name: %s', $tag->getName());

        return [
            [null, ['3' => '3']], // Choice label: null
            ['name', ['3' => '3']], // Choice label: property
            [$closure, ['3' => 'name: 3']], // Choice label: closure
        ];
    }

    public function testTransformWithChoiceLabelError(): void
    {
        $entity = $this->em->getRepository(EntityManyToOne::class)->find(1);

        $queryBuilder = $this->em->getRepository(EntityManyToOne::class)->createQueryBuilder('e')->select('e');
        $transformer = $this->createTransformer($queryBuilder, 'id', null, false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('"choice_label" option or "__toString" method must be defined"');
        $transformer->transform($entity);
    }

    public function testTransformNullValue(): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'id', null, false);

        $this->assertNull($transformer->transform(null));
    }

    public function testTransformNotObject(): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'id', null, false);

        $this->expectException(UnexpectedTypeException::class);
        $transformer->transform('string');
    }

    public function testReverseTransform(): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'id', null, false);

        $entity = $transformer->reverseTransform('3');
        $this->assertEquals($this->em->find(Tag::class, 3), $entity);
    }

    /**
     * @dataProvider getTestReverseTransformNullValueProvider
     */
    public function testReverseTransformNullValue($value): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'id', null, false);

        $this->assertNull($transformer->reverseTransform($value));
    }

    public function getTestReverseTransformNullValueProvider(): array
    {
        return [
            [''],
            [null],
        ];
    }

    public function testReverseTransformNotScalar(): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'id', null, false);

        $this->expectException(TransformationFailedException::class);
        $this->assertEquals(new ArrayCollection(), $transformer->reverseTransform(['val']));
    }

    public function testReverseTransformWithEntityNotFoundReturnsNull(): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'id', null, false);

        $this->assertNull($transformer->reverseTransform('999999'));
    }

    public function testReverseTransformWithEntityNotFoundThrowsException(): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'id', null, true);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('The entity with key "999999" could not be found or is not unique');
        $transformer->reverseTransform('999999');
    }

    public function testReverseTransformNotUniqueReturnsNull(): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'name', null, false);

        $this->assertNull($transformer->reverseTransform('tag_name'));
    }

    public function testReverseTransformNotUniqueThrowsException(): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'name', null, true);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('The entity with key "tag_name" could not be found or is not unique');
        $transformer->reverseTransform('tag_name');
    }

    public function testReverseTransformQueryError(): void
    {
        $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')->select('t');
        $transformer = $this->createTransformer($queryBuilder, 'badIdentifier', null, false);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Tranformation: Query Error');
        $transformer->reverseTransform('3');
    }
}

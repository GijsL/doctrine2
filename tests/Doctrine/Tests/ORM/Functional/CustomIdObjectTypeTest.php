<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\DbalTypes\CustomIdObject;
use Doctrine\Tests\DbalTypes\CustomIdObjectType;
use Doctrine\Tests\Models\CustomType\CustomIdObjectTypeChild;
use Doctrine\Tests\Models\CustomType\CustomIdObjectTypeParent;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\DBAL\Types\Type as DBALType;

class CustomIdObjectTypeTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        if (DBALType::hasType(CustomIdObjectType::NAME)) {
            DBALType::overrideType(CustomIdObjectType::NAME, CustomIdObjectType::class);
        } else {
            DBALType::addType(CustomIdObjectType::NAME, CustomIdObjectType::class);
        }

        $this->useModelSet('custom_id_object_type');

        parent::setUp();
    }

    public function testFindByCustomIdObject()
    {
        $parent = new CustomIdObjectTypeParent(new CustomIdObject('foo'));

        $this->em->persist($parent);
        $this->em->flush();

        $result = $this->em->find(CustomIdObjectTypeParent::class, $parent->id);

        self::assertSame($parent, $result);
    }

    /**
     * @group DDC-3622
     * @group 1336
     */
    public function testFetchJoinCustomIdObject()
    {
        $parent = new CustomIdObjectTypeParent(new CustomIdObject('foo'));

        $parent->children->add(new CustomIdObjectTypeChild(new CustomIdObject('bar'), $parent));

        $this->em->persist($parent);
        $this->em->flush();

        $result = $this
            ->em
            ->createQuery(
                'SELECT parent, children FROM '
                . CustomIdObjectTypeParent::class
                . ' parent LEFT JOIN parent.children children'
            )
            ->getResult();

        self::assertCount(1, $result);
        self::assertSame($parent, $result[0]);
    }

    /**
     * @group DDC-3622
     * @group 1336
     */
    public function testFetchJoinWhereCustomIdObject()
    {
        $parent = new CustomIdObjectTypeParent(new CustomIdObject('foo'));

        $parent->children->add(new CustomIdObjectTypeChild(new CustomIdObject('bar'), $parent));

        $this->em->persist($parent);
        $this->em->flush();

        // note: hydration is willingly broken in this example:
        $result = $this
            ->em
            ->createQuery(
                'SELECT parent, children FROM '
                . CustomIdObjectTypeParent::class
                . ' parent LEFT JOIN parent.children children '
                . 'WHERE children.id = ?1'
            )
            ->setParameter(1, $parent->children->first()->id)
            ->getResult();

        self::assertCount(1, $result);
        self::assertSame($parent, $result[0]);
    }
}

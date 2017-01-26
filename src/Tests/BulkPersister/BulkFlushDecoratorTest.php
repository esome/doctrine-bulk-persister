<?php

namespace esome\Tests\BulkPersister;

use esome\BulkPersister\BulkFlushDecorator;
use esome\BulkPersister\DoctrineReplaceIntoPersister;
use esome\Tests\BulkPersister\TestEntity;
use PHPUnit\Framework\TestCase;

class BulkFlushDecoratorTest extends TestCase
{

    public function testPersistWithoutFlushAndClear()
    {
        $persister = $this->getMockBuilder(DoctrineReplaceIntoPersister::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entity = new TestEntity(1, new \DateTime(), 'name', 'YES');

        $persister->expects($this->once())
            ->method('persist')
            ->with($entity);

        $decorator = new BulkFlushDecorator($persister, 2);

        $decorator->persist($entity);
    }

    public function testPersistWithFlushAndClear()
    {
        $persister = $this->getMockBuilder(DoctrineReplaceIntoPersister::class)
            ->disableOriginalConstructor()
            ->getMock();

        $persister->expects($this->once())
            ->method('flushAndClear')
            ->with(TestEntity::class);

        $decorator = new BulkFlushDecorator($persister, 2);

        $decorator->persist(new TestEntity(1, new \DateTime(), 'name1', 'YES1'));
        $decorator->persist(new TestEntity(2, new \DateTime(), 'name2', 'YES2'));
    }

    public function testFlushAndClearWithClassFilterWithoutData()
    {
        $persister = $this->getMockBuilder(DoctrineReplaceIntoPersister::class)
            ->disableOriginalConstructor()
            ->getMock();

        $persister->expects($this->never())
            ->method('flushAndClear')
            ->with(TestEntity::class);

        $decorator = new BulkFlushDecorator($persister, 2);

        $decorator->flushAndClear(TestEntity::class);
    }

}

<?php

namespace esome\Tests\BulkPersister;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use esome\BulkPersister\DoctrineReplaceIntoPersister;
use esome\Tests\BulkPersister\TestEntity;
use PHPUnit\Framework\TestCase;

class DoctrineReplaceIntoPersisterTest extends TestCase
{

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $mockConnection;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $mockConfiguration;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $mockEntityManager;

    protected function setUp()
    {
        $platform = new MySqlPlatform();
        $this->mockConnection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockConnection->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->returnValue($platform));

        $this->mockConfiguration = $this->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockConfiguration->expects($this->any())
            ->method('getQuoteStrategy')
            ->will($this->returnValue(new DefaultQuoteStrategy()));

        $this->mockEntityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockEntityManager->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($this->mockConnection));
        $this->mockEntityManager->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($this->mockConfiguration));

        $classMetadata = new ClassMetadata(TestEntity::class);
        $classMetadata->table['name'] = 'test_entities';
        $classMetadata->columnNames = [
            'id' => 'id',
            'dateStart' => 'date_start',
            'name' => 'name',
            'like' => 'like',
        ];
        $classMetadata->fieldMappings = [
            'id' => [
                'fieldName' => 'id',
                'type' => Type::INTEGER,
                'columnName' => 'id',
                'length' => 11,
                'id' => true,
                'nullable' => false,
            ],
            'dateStart' => [
                'fieldName' => 'dateStart',
                'type' => Type::DATE,
                'columnName' => 'date_start',
                'length' => 255,
                'id' => false,
                'nullable' => false,
            ],
            'name' => [
                'fieldName' => 'name',
                'type' => Type::STRING,
                'columnName' => 'name',
                'length' => 255,
                'id' => false,
                'nullable' => false,
            ],
            'like' => [
                'fieldName' => 'like',
                'type' => Type::STRING,
                'columnName' => 'like',
                'length' => 255,
                'id' => false,
                'nullable' => false,
                'quoted' => true,
            ],
        ];

        $this->mockEntityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->will($this->returnValue($classMetadata));

        parent::setUp();
    }

    /** @dataProvider dataProviderFlushAndClear */
    public function testFlushAndClear($expectedParameters, $expectedValues, $entities)
    {
        $mockStatement = $this->getMockBuilder(Statement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStatement->expects($this->once())
            ->method('execute')
            ->with($expectedParameters);

        $this->mockConnection->expects($this->once())
            ->method('prepare')
            ->with("REPLACE INTO test_entities\n(id, date_start, name, `like`)\nVALUES\n" . $expectedValues)
            ->will($this->returnValue($mockStatement));

        $persister = new DoctrineReplaceIntoPersister($this->mockEntityManager);

        foreach ($entities as $entity) {
            $persister->persist($entity);
        }

        $persister->flushAndClear();
    }

    public function dataProviderFlushAndClear()
    {
        return [
            'one entity' => [
                'expectedParameters' => [
                    ':id0' => null,
                    ':dateStart0' => '2016-12-24',
                    ':name0' => 'Test Name',
                    ':like0' => 'YES',
                ],
                'expectedValues' => "(:id0, :dateStart0, :name0, :like0)",
                'entities' => [
                    new TestEntity(null, \DateTime::createFromFormat('Y-m-d', '2016-12-24'), 'Test Name', 'YES'),
                ],
            ],
            'two entities' => [
                'expectedParameters' => [
                    ':id0' => null,
                    ':dateStart0' => '2016-12-24',
                    ':name0' => 'Test Name',
                    ':like0' => 'YES',
                    ':id1' => 12,
                    ':dateStart1' => '2016-10-04',
                    ':name1' => 'Other Name',
                    ':like1' => 'NO',
                ],
                'expectedValues' => "(:id0, :dateStart0, :name0, :like0),\n(:id1, :dateStart1, :name1, :like1)",
                'entities' => [
                    new TestEntity(null, \DateTime::createFromFormat('Y-m-d', '2016-12-24'), 'Test Name', 'YES'),
                    new TestEntity(12, \DateTime::createFromFormat('Y-m-d', '2016-10-04'), 'Other Name', 'NO'),
                ],
            ]
        ];
    }

    public function testFlushAndClearWithoutData()
    {
        $mockStatement = $this->getMockBuilder(Statement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStatement->expects($this->never())
            ->method('execute');

        $this->mockConnection->expects($this->never())
            ->method('prepare');

        $persister = new DoctrineReplaceIntoPersister($this->mockEntityManager);

        $persister->flushAndClear();
    }

    public function testFlushAndClearWithClassFilterWithoutData()
    {
        $mockStatement = $this->getMockBuilder(Statement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStatement->expects($this->never())
            ->method('execute');

        $this->mockConnection->expects($this->never())
            ->method('prepare');

        $persister = new DoctrineReplaceIntoPersister($this->mockEntityManager);

        $persister->flushAndClear(TestEntity::class);
    }

}

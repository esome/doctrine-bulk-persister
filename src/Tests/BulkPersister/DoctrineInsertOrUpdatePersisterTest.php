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
use esome\BulkPersister\DoctrineInsertOrUpdatePersister;
use esome\Tests\BulkPersister\TestEntity;
use PHPUnit\Framework\TestCase;

class DoctrineInsertOrUpdatePersisterTest extends TestCase
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
            'flag' => 'flag',
            'flagged' => 'flagged',
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
            'flag' => [
                'fieldName' => 'flag',
                'type' => Type::BOOLEAN,
                'columnName' => 'flag',
                'id' => false,
                'nullable' => false,
            ],
            'flagged' => [
                'fieldName' => 'flagged',
                'type' => Type::BOOLEAN,
                'columnName' => 'flagged',
                'id' => false,
                'nullable' => false,
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
            ->with(
                "INSERT INTO test_entities\n(date_start, name, `like`, flag, flagged)\nVALUES\n"
                . $expectedValues
                . "\nON DUPLICATE KEY UPDATE\ndate_start = VALUES(date_start),name = VALUES(name),`like` = VALUES(`like`),flag = VALUES(flag),flagged = VALUES(flagged)"
            )
            ->will($this->returnValue($mockStatement));

        $persister = new DoctrineInsertOrUpdatePersister($this->mockEntityManager);

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
                    ':dateStart0' => '2016-12-24',
                    ':name0' => 'Test Name',
                    ':like0' => 'YES',
                    ':flag0' => 1,
                    ':flagged0' => 1,
                ],
                'expectedValues' => "(:dateStart0, :name0, :like0, :flag0, :flagged0)",
                'entities' => [
                    new TestEntity(null, \DateTime::createFromFormat('Y-m-d', '2016-12-24'), 'Test Name', 'YES', true, true),
                ],
            ],
            'two entities' => [
                'expectedParameters' => [
                    ':dateStart0' => '2016-12-24',
                    ':name0' => 'Test Name',
                    ':like0' => 'YES',
                    ':flag0' => 1,
                    ':flagged0' => 1,
                    ':dateStart1' => '2016-10-04',
                    ':name1' => 'Other Name',
                    ':like1' => 'NO',
                    ':flag1' => 1,
                    ':flagged1' => 1,
                ],
                'expectedValues' => "(:dateStart0, :name0, :like0, :flag0, :flagged0),\n(:dateStart1, :name1, :like1, :flag1, :flagged1)",
                'entities' => [
                    new TestEntity(null, \DateTime::createFromFormat('Y-m-d', '2016-12-24'), 'Test Name', 'YES', true, true),
                    new TestEntity(12, \DateTime::createFromFormat('Y-m-d', '2016-10-04'), 'Other Name', 'NO', true, true),
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

        $persister = new DoctrineInsertOrUpdatePersister($this->mockEntityManager);

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

        $persister = new DoctrineInsertOrUpdatePersister($this->mockEntityManager);

        $persister->flushAndClear(TestEntity::class);
    }

}

<?php

namespace esome\BulkPersister;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;

class DoctrineInsertOrUpdatePersister implements BulkPersisterInterface
{

    /** @var EntityManager */
    private $em;

    /** @var array */
    private $entitiesByClass = [];

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param object $entity
     */
    public function persist($entity)
    {
        $this->entitiesByClass[get_class($entity)][] = $entity;
    }

    /**
     *
     */
    public function flushAndClear($classFilter = null)
    {
        $platform = $this->em->getConnection()->getDatabasePlatform();
        $quoteStrategy = $this->em->getConfiguration()->getQuoteStrategy();

        $classes = array_keys($this->entitiesByClass);
        if (!is_null($classFilter)) {
            $classes = [$classFilter];
        }

        foreach ($classes as $class) {
            if (!isset($this->entitiesByClass[$class])) {
                continue;
            }
            $entities = $this->entitiesByClass[$class];
            $metadata = $this->getClassMetadata($class);
            $uniqueColumns = $this->getUniqueColumns($metadata);

            $quotedColumnNames = [];
            $updateColumns = [];
            foreach ($metadata->columnNames as $fieldName => $columnName) {
                if ($columnName === 'id') {
                    continue;
                }

                $quotedColumnName = $quoteStrategy->getColumnName($fieldName, $metadata, $platform);
                $quotedColumnNames[] = $quotedColumnName;

                if (!in_array($columnName, $uniqueColumns)) {
                    if ($columnName === 'meta_updated_at') {
                        $updateColumns[] = sprintf("%s = NOW()", $quotedColumnName);
                    } else {
                        $updateColumns[] = sprintf("%s = VALUES(%s)", $quotedColumnName, $quotedColumnName);
                    }
                }
            };

            $i = 0;
            $parameters = [];
            $placeHolderRows = [];
            foreach ($entities as $entity) {
                $placeholders = [];

                foreach ($metadata->columnNames as $fieldName => $columnName) {
                    if ($columnName === 'id') {
                        continue;
                    }
                    $getter = 'get' . ucfirst($fieldName);
                    $value = $entity->$getter();
                    $type = Type::getType($metadata->getTypeOfField($fieldName));

                    $placeholder = ':' . $fieldName . $i;
                    $parameters[$placeholder] = $type->convertToDatabaseValue($value, $platform);

                    $placeholders[] = $placeholder;
                }
                $placeHolderRows[] = sprintf("(%s)", implode(', ', $placeholders));

                ++$i;
            }

            if (!count($placeHolderRows)) {
                continue;
            }

            $statement = sprintf(
                "INSERT INTO %s\n(%s)\nVALUES\n%s
                 ON DUPLICATE KEY UPDATE %s
                ",
                $metadata->table['name'],
                implode(', ', $quotedColumnNames),
                implode(",\n", $placeHolderRows),
                implode(',', $updateColumns)
            );

            $prepared = $this->em->getConnection()->prepare($statement);
            if ($prepared->execute($parameters)) {
                $this->clear($class);
            }
        }
    }

    /**
     * @param string $class
     */
    public function clear($class = '')
    {
        if (empty($class)) {
            $this->entitiesByClass = [];
        } else {
            $this->entitiesByClass[$class] = [];
        }
    }

    /**
     * @param string $class fully qualified class name of entity
     * @return ClassMetadata
     */
    private function getClassMetadata($class)
    {
        static $metadataByClass = [];

        if (!isset($metadataByClass[$class])) {
            $metadataByClass[$class] = $this->em->getClassMetadata($class);
        }

        return $metadataByClass[$class];
    }

    /**
     * @param object $metaData
     * @return array
     */
    private function getUniqueColumns($metaData)
    {
        if (isset($metaData->table['uniqueConstraints'])) {
            return array_keys($metaData->table['uniqueConstraints']);
        }

        return [];
    }
}

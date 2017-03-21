<?php

namespace esome\BulkPersister;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;

class DoctrineReplaceIntoPersister implements BulkPersisterInterface
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
            $quotedColumnNames = [];
            foreach ($metadata->columnNames as $fieldName => $columnName) {
                $quotedColumnNames[] = $quoteStrategy->getColumnName($fieldName, $metadata, $platform);
            };
            $i = 0;
            $parameters = [];
            $placeHolderRows = [];
            foreach ($entities as $entity) {
                $placeholders = [];
                foreach ($metadata->columnNames as $fieldName => $columnName) {
                    $getter = 'get' . ucfirst($fieldName);
                    if (!method_exists($entity, $getter)) {
                        $getter = 'is' . ucfirst($fieldName);
                    }
                    $value = $entity->$getter();
                    $type = Type::getType($metadata->getTypeOfField($fieldName));

                    $placeholder = ':' . $fieldName . $i;
                    $parameters[$placeholder] = $type->convertToDatabaseValue($value, $platform);

                    $placeholders[] = $placeholder;
                }

                foreach ($metadata->associationMappings as $associationName => $associationMapping) {
                    if ($associationMapping['isOwningSide']) {
                        $getter = 'get' . ucfirst($associationName);
                        $related = $entity->$getter();
                        if (!$related) {
                            continue;
                        }
                        $value = $entity->$getter()->getId();
                        $type = Type::getType('integer');

                        $placeholder = ':' . $associationName . $i;
                        if (!in_array($placeholder, $placeholders)) {
                            $parameters[$placeholder] = $type->convertToDatabaseValue($value, $platform);

                            $placeholders[] = $placeholder;
                        }

                        $coloumnName = $quoteStrategy->getJoinColumnName(
                            $associationMapping['joinColumns'][0],
                            $metadata,
                            $platform
                        );
                        if (!in_array($coloumnName, $quotedColumnNames)) {
                            $quotedColumnNames[] = $coloumnName;
                        }
                    }
                }

                $placeHolderRows[] = sprintf("(%s)", implode(', ', $placeholders));
                ++$i;
            }
            if (!count($placeHolderRows)) {
                continue;
            }
            $statement = sprintf(
                "REPLACE INTO %s\n(%s)\nVALUES\n%s",
                $metadata->table['name'],
                implode(', ', $quotedColumnNames),
                implode(",\n", $placeHolderRows)
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

}

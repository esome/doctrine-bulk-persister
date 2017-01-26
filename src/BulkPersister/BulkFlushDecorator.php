<?php

namespace esome\BulkPersister;

class BulkFlushDecorator implements BulkPersisterInterface
{
    /** @var BulkPersisterInterface */
    private $persister;

    /** @var int[] */
    private $counts = [];

    /** @var int */
    private $batchSize = 100;

    /**
     * @param BulkPersisterInterface $persister
     * @param int $batchSize
     */
    public function __construct(BulkPersisterInterface $persister, $batchSize)
    {
        $this->persister = $persister;
        $this->batchSize = $batchSize;
    }

    /**
     * @param object $entity
     */
    public function persist($entity)
    {
        $this->persister->persist($entity);
        $class = get_class($entity);
        if (!isset($this->counts[$class])) {
            $this->counts[$class] = 0;
        }
        if (++$this->counts[$class] % $this->batchSize === 0) {
            $this->flushAndClear($class);
        }
    }

    /**
     * @param string $class
     */
    public function flushAndClear($class = null)
    {
        $classes = array_keys($this->counts);
        if (!is_null($class)) {
            $classes = [$class];
        }

        foreach ($classes as $class) {
            if (!isset($this->counts[$class]) || $this->counts[$class] === 0) {
                continue;
            }
            $this->persister->flushAndClear($class);
            $this->counts[$class] = 0;
        }
    }

}

<?php

namespace esome\BulkPersister;

interface BulkPersisterInterface
{

    public function persist($entity);

    public function flushAndClear($class = null);

}

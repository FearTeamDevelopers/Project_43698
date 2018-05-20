<?php

namespace Search\Model\Sources;

/**
 *
 * @author Tomy
 */
interface SourceInterface
{

    public function buildIndex($complete, $runByUser);

    public function getAlias();

    public function getTable();
}

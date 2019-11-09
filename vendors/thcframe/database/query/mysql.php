<?php

namespace THCFrame\Database\Query;

use THCFrame\Core\Core;
use THCFrame\Database as Database;
use THCFrame\Database\Exception as Exception;

/**
 * Extension for Query class specificly for Mysql
 */
class Mysql extends Database\Query
{

    /**
     *
     * @return array
     * @throws Exception\Sql
     */
    public function all()
    {
        $sql = $this->buildSelect();
        $result = $this->connector->execute($sql);

        if ($result === false) {
            $err = $this->connector->getLastError();
            $this->_logError($err, $sql);

            if (ENV == Core::ENV_DEV) {
                throw new Exception\Sql(sprintf('There was an error with your SQL query: %s', $err));
            } else {
                throw new Exception\Sql('There was an error');
            }
        }

        $rows = [];

        for ($i = 0; $i < $result->num_rows; $i++) {
            $rows[] = $result->fetch_array(MYSQLI_ASSOC);
        }

        return $rows;
    }

}

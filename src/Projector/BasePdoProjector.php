<?php

namespace UserBase\Server\Projector;

use PDO;

abstract class BasePdoProjector
{
    protected $pdo;
    protected $tableName;
    
    public function __construct(PDO $pdo, $tableName)
    {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
    }
    
    public function insert($values)
    {
        $setters = $this->arrayToSetters($values);
        
        $sql = sprintf(
            "INSERT INTO %s SET %s",
            $this->tableName,
            $setters
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute($values);
    }
    
    public function update($keys, $values)
    {
        $where = $this->arrayToWhere($keys);
        $setters = $this->arrayToSetters($values);
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $this->tableName,
            $setters,
            $where
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute(array_merge($values, $keys));
    }
    
    protected function count($keys)
    {
        $where = $this->arrayToWhere($keys);
        
        $sql = sprintf(
            "SELECT count(*) AS c FROM %s WHERE %s",
            $this->tableName,
            $where
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute($keys);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row['c'];
    }
    
    public function upsert($keys, $values)
    {
        $count = $this->count($keys);
        if ($count>0) {
            $this->update($keys, $values);
        } else {
            $this->insert(array_merge($keys, $values));
        }
    }
    
    public function delete($keys)
    {
        $where = $this->arrayToWhere($keys);

        $sql = sprintf(
            "DELETE FROM %s WHERE %s",
            $this->tableName,
            $where
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute($keys);
    }
    
    protected function arrayToWhere($a) {
        $sql = '';
        foreach ($a as $key => $value) {
            if ($sql != '') {
                $sql .= ' AND ';
            }
            $sql .= sprintf('%s=:%s', $key, $key);
        }
        return $sql;
    }
    
    protected function arrayToSetters($a) {
        $sql = '';
        foreach ($a as $key => $value) {
            if ($sql != '') {
                $sql .= ', ';
            }
            $sql .= sprintf('%s=:%s', $key, $key);
        }
        return $sql;
    }
}

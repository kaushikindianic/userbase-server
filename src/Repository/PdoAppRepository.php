<?php

namespace UserBase\Server\Repository;

use UserBase\Server\Model\App;
use RuntimeException;
use PDO;

final class PdoAppRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    public function getById($id)
    {
        $statement = $this->pdo->prepare(
            "SELECT a.*
            FROM app AS a
            WHERE a.id=:id
            LIMIT 1"
        );
        $statement->execute(array('id' => $id));
        $row = $statement->fetch();

        if (!$row) {
            return null;
        }

        return $this->row2app($row);
    }
    
    public function getByName($name)
    {
        $statement = $this->pdo->prepare(
            "SELECT a.*
            FROM app AS a
            WHERE a.name=:name
            LIMIT 1"
        );
        $statement->execute(array('name' => $name));
        $row = $statement->fetch();

        if (!$row) {
            return null;
        }
        
        return $this->row2app($row);
    }
    
    public function getAll($limit = 10)
    {
        $statement = $this->pdo->prepare(
            "SELECT a.*
            FROM app AS a
            ORDER BY id DESC"
        );
        $statement->execute();
        $apps = array();
        while ($row = $statement->fetch()) {
            $app = $this->row2app($row);
            $apps[] = $app;
        }
        return $apps;
    }
    
    private function row2app($row)
    {
        $app = new App();
        $app->setName($row['name']);
        $app->setDisplayName($row['display_name']);
        $app->setBaseUrl($row['base_url']);
        $app->setPictureUrl($row['picture_url']);
        $app->setCreatedAt($row['created_at']);
        $app->setDeletedAt($row['deleted_at']);
        return $app;
    }
}

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
        $statement = $this->pdo->prepare("SELECT a.*
            FROM app AS a
            WHERE a.id=:id
            LIMIT 1");
        $statement->execute(array(
            'id' => $id
        ));
        $row = $statement->fetch();
        
        if (! $row) {
            return null;
        }
        
        return $this->rowToApp($row);
    }

    public function getByName($name)
    {
        $statement = $this->pdo->prepare("SELECT a.*
            FROM app AS a
            WHERE a.name=:name
            LIMIT 1");
        $statement->execute(array(
            'name' => $name
        ));
        $row = $statement->fetch();
        
        if (! $row) {
            return null;
        }
        
        return $this->rowToApp($row);
    }

    public function getAll($limit = 10)
    {
        $statement = $this->pdo->prepare("SELECT a.*
            FROM app AS a
        	WHERE deleted_at = 0	
            ORDER BY id DESC");
        $statement->execute();
        $apps = array();
        while ($row = $statement->fetch()) {
            $app = $this->rowToApp($row);
            $apps[] = $app;
        }
        return $apps;
    }

    public function add(App $apps)
    {
        $exists = $this->getByName($apps->getName());
        if ($exists === null) {
            $statement = $this->pdo->prepare('INSERT INTO `app` (name, display_name, base_url, about, picture_url, created_at) 
    				 VALUES (:name, :display_name, :base_url, :about, :picture_url, :created_at)');
            
            $statement->execute(array(
                ':name' => $apps->getName(),
                ':display_name' => $apps->getDisplayName(),
                ':base_url' => $apps->getBaseUrl(),
                ':about' => $apps->getAbout(),
                ':picture_url' => $apps->getPictureUrl(),
                ':created_at' => time()
            ));
            
            return true;
        } else {
            return false;
        }
    }

    public function update(App $apps)
    {
        $statement = $this->pdo->prepare('UPDATE `app`
             SET display_name = :display_name, 
                base_url = :base_url,
        		about = :about,
        		picture_url = :picture_url
             WHERE name=:name');
        $statement->execute(array(
            ':name' => $apps->getName(),
            ':display_name' => $apps->getDisplayName(),
            ':base_url' => $apps->getBaseUrl(),
            ':about' => $apps->getAbout(),
            ':picture_url' => $apps->getPictureUrl()
        ));
    }

    public function delete($name)
    {
        if (! $name) {
            throw new RuntimeException("apps not specified");
        }
        
        $statement = $this->pdo->prepare("UPDATE app SET deleted_at = :deleted_at WHERE name=:name");
        
        $statement->execute(array(
            ':deleted_at' => time(),
            ':name' => $name
        ));
    }    

    private function rowToApp($row)
    {
        $app = new App();
        $app->setName($row['name']);
        $app->setDisplayName($row['display_name']);
        $app->setBaseUrl($row['base_url']);
        $app->setPictureUrl($row['picture_url']);
        $app->setCreatedAt($row['created_at']);
        $app->setDeletedAt($row['deleted_at']);
        $app->setAbout($row['about']);
        return $app;
    }
}

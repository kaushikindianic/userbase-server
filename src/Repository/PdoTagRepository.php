<?php
namespace UserBase\Server\Repository;

use UserBase\Server\Model\Tag;
use RuntimeException;
use PDO;

class PdoTagRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function add(Tag $oTagModel)
    {
        $sql = 'INSERT IGNORE INTO tag (name, description) VALUES (:name, :description)';
        $statement = $this->pdo->prepare($sql);
        $row = $statement->execute(array(
            'name' => $oTagModel->getName(),
            'description' => $oTagModel->getDescription()
        ));
        return $row;
    }

    public function update(Tag $oTagModel)
    {
        $statement = $this->pdo->prepare('UPDATE  IGNORE tag SET
            name =:name, description =:description
             WHERE id =:id');

        return $statement->execute(array(
            'name' => $oTagModel->getName(),
            ':description' => $oTagModel->getDescription(),
            ':id' => $oTagModel->getId()
        ));
    }

    public function checkExist($name, $id = 0)
    {
        $aVal = array();
        $sql = 'SELECT * FROM tag WHERE name = :name';
        if ($id) {
            $sql .= ' AND id != :id';
            $aVal[':id'] = (int) $id;
        }
        $aVal[':name'] = $name;
        $statement = $this->pdo->prepare($sql);
        $statement->execute($aVal);
        return $statement->fetch();
    }

    public function getById($id)
    {
        $statement = $this->pdo->prepare('SELECT * FROM tag WHERE id =:id');
        $statement->execute(array('id' => (int) $id));
        return $statement->fetch();
    }
    
    public function getByName($name)
    {
        $statement = $this->pdo->prepare('SELECT * FROM tag WHERE name =:name');
        $statement->execute(array('name' => $name));
        return $statement->fetch();
    }

    public function findAll()
    {
        $statement = $this->pdo->prepare('SELECT * FROM tag');
        $statement->execute(array());
        return $statement->fetchAll();
    }
}

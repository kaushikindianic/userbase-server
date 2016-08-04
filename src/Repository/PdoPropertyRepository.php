<?php
namespace UserBase\Server\Repository;

use UserBase\Server\Model\Property;
use RuntimeException;
use PDO;

class PdoPropertyRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function add(Property $oPropertyModel)
    {
        $sql = 'INSERT IGNORE INTO property(name, description) VALUES (:name, :description)';
        $statement = $this->pdo->prepare($sql);
        $row = $statement->execute(
            [
                'name' => $oPropertyModel->getName(),
                'description' => $oPropertyModel->getDescription()
            ]
        );
        return $row;
    }

    public function update(Property $oPropertyModel)
    {
        $statement = $this->pdo->prepare('UPDATE IGNORE property SET
            name =:name, description =:description
             WHERE id =:id');

        return $statement->execute(array(
            'name' => $oPropertyModel->getName(),
            ':description' => $oPropertyModel->getDescription(),
            ':id' => $oPropertyModel->getId()
        ));
    }

    public function checkExist($name, $id = 0)
    {
        $aVal = array();
        $sql = 'SELECT * FROM property WHERE name = :name';
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
        $statement = $this->pdo->prepare('SELECT * FROM property WHERE id =:id');
        $statement->execute(array('id' => (int) $id));
        return $statement->fetch();
    }
    
    public function getByName($name)
    {
        $statement = $this->pdo->prepare('SELECT * FROM property WHERE name =:name');
        $statement->execute(array('name' => $name));
        return $statement->fetch();
    }

    public function findAll()
    {
        $statement = $this->pdo->prepare('SELECT * FROM property');
        $statement->execute(array());
        return $statement->fetchAll();
    }
}

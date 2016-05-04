<?php
namespace UserBase\Server\Repository;

use UserBase\Server\Model\Space;
use RuntimeException;
use PDO;

class PdoSpaceRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function add(Space $oSpaceModel)
    {
        $sql = 'INSERT INTO space(name, account_name, description) VALUES (:name, :account_name, :description)';
        $statement = $this->pdo->prepare($sql);
        $row = $statement->execute(array(
            'account_name' => $oSpaceModel->getAccountName(),
            'name' => $oSpaceModel->getName(),
            'description' => $oSpaceModel->getDescription()
        ));
        return $row;
    }

    public function update(Space $oSpaceModel)
    {
        $statement = $this->pdo->prepare('UPDATE space SET description =:description WHERE id =:id ');
        return $statement->execute(array(
            ':description' => $oSpaceModel->getDescription(),
            ':id' => $oSpaceModel->getId()
        ));
    }

    public function getAccountSpaces($accountName)
    {
        $statement = $this->pdo->prepare("SELECT * FROM space
            WHERE  account_name = :account_name AND deleted_at IS NULL ORDER BY name ASC");
        $statement->execute(array(':account_name' => $accountName));
        $rows = $statement->fetchAll();
        return $rows;
    }

    public function getSpacesByAccounts($accountName = array())
    {
        $str = implode('","', $accountName);
        $statement = $this->pdo->prepare('SELECT name FROM space
            WHERE  account_name IN ("'.$str.'")  AND deleted_at IS NULL ORDER BY name ASC');
        $statement->execute();
        $rows = $statement->fetchAll();

        $aSpace = array();

        foreach ($rows as $row) {
            $aSpace[]= $row['name'];
        }
        return $aSpace;
    }

    public function checkExist($name, $accountName, $id = 0)
    {
        $aVal = array();
        $sql = 'SELECT * FROM space WHERE name =:name AND account_name = :account_name';
        if ($id) {
            $sql .= ' AND id != :id';
            $aVal[':id'] = (int) $id;
        }
        $aVal[':name'] = $name;
        $aVal[':account_name'] = $accountName;
        $statement = $this->pdo->prepare($sql);
        $statement->execute($aVal);
        return $statement->fetch();
    }

    public function getById($id)
    {
        $statement = $this->pdo->prepare('SELECT * FROM space WHERE id =:id');
        $statement->execute(array('id' => (int) $id ));
        return $statement->fetch();
    }

    public function delete($id)
    {
        $statement = $this->pdo->prepare('UPDATE space SET deleted_at = NOW() WHERE id =:id');
        return $statement->execute(array('id' => (int) $id ));
    }
}

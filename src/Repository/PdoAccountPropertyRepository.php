<?php
namespace UserBase\Server\Repository;

use UserBase\Server\Model\AccountProperty;
use RuntimeException;
use PDO;

class PdoAccountPropertyRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findAll()
    {
        $statement = $this->pdo->prepare('SELECT * FROM account_property ORDER BY account_name ASC');
        $statement->execute(array());
        return $statement->fetchAll();
    }

    public function find($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM account_property WHERE id=:id");
        $statement->execute(array(
            'id' => $id
        ));
        $rows = $statement->fetchAll();
        if (count($rows)!=1) {
            throw new RuntimeException("Not found: $id");
        }
        return $this->rowToAccountProperty($rows[0]);
    }

    public function getByAccountName($accountName)
    {
        $statement = $this->pdo->prepare("SELECT * FROM account_property WHERE account_name=:account_name");
        $statement->execute(array(
            'account_name' => $accountName
        ));
        $rows = $statement->fetchAll();
        $objs = array();
        foreach ($rows as $row) {
            $objs[] = $this->rowToAccountProperty($row);
        }
        return $objs;
    }

    private function rowToAccountProperty($row)
    {
        $obj = new AccountProperty();
        $obj->setId($row['id']);
        $obj->setAccountName($row['account_name']);
        $obj->setName($row['name']);
        $obj->setValue($row['value']);
        return $obj;
    }

    public function add(AccountProperty $property)
    {
        $sql = 'INSERT INTO account_property(account_name, name, value)
                VALUES(:account_name, :name, :value)';

        $statement = $this->pdo->prepare($sql);
        $row = $statement->execute(array(
                'account_name' => $property->getAccountName(),
                'name' => $property->getName(),
                'value' => $property->getValue()
        ));
        return $row;
    }

    public function delete(AccountProperty $property)
    {
        $sql = 'DELETE FROM account_property WHERE id=:id
                AND account_name=:account_name';

        $statement = $this->pdo->prepare($sql);
        $row = $statement->execute(array(
                'account_name' => $property->getAccountName(),
                'id' => $property->getId()
        ));
        return $row;
    }

    public function insertOrUpdate(AccountProperty $property)
    {
        $sql = 'INSERT INTO account_property(account_name, name, value)
                VALUES(:account_name, :name, :value)
                ON DUPLICATE KEY UPDATE `value` = values(value)';

        $statement = $this->pdo->prepare($sql);
        $row = $statement->execute(array(
                'account_name' => $property->getAccountName(),
                'name' => $property->getName(),
                'value' => $property->getValue()
        ));
        return $row;
    }

    public function deleteByAccountNameAndName($accountName, $name)
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM account_property WHERE account_name = :account_name AND name = :name'
        );
        return $statement->execute(array(':account_name' => $accountName, ':name' => $name));
    }
}

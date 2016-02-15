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
        $obj->setAccountName($row['account_name']);
        $obj->setName($row['name']);
        $obj->setValue($row['value']);
        return $obj;
    }
}

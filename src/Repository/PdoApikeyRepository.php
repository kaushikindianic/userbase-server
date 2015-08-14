<?php
namespace UserBase\Server\Repository;

use UserBase\Server\Model\Apikey;
use RuntimeException;
use PDO;

class PdoApikeyRepository
{
    private $pdo;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    public function getAll($accountName = '')
    {
        $aVal = array();
        $sql = 'SELECT * FROM api_key  WHERE 1 ';
        
        if ($accountName) {
            $sql .= ' AND account_name = :account_name ';
            $aVal[':account_name'] = $accountName;
        }
        $sql .= ' ORDER BY id DESC';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($aVal);
        $rows = $statement->fetchAll();
        return $rows;        
    }
    
    public function add(Apikey $oApikey)
    {
        $sql = 'INSERT INTO api_key(account_name, name, username, password, created_at)
                VALUES(:account_name, :name, :username, :password,:created_at)';
        
        $statement = $this->pdo->prepare($sql);
        $row = $statement->execute(array(
                'account_name' => $oApikey->getAccountName() ,
                'name' => $oApikey->getName(),
                'username' => $oApikey->getUserName(),
                'password' => $oApikey->getPassword(),
                'created_at' => $oApikey->getCreatedAt()
        ));
        return $row;
    }    
}
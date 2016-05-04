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
    
    public function getById($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM api_key WHERE id=:id LIMIT 1");
        $statement->execute(array(
            'id' => $id
        ));
        $row = $statement->fetch();
        return $row;
    }
    
    public function getByAccountName($accountName = '')
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
    
    public function getAll()
    {
        $aVal = array();
        $sql = 'SELECT api.*, a.display_name FROM api_key AS api
                LEFT JOIN  account AS a ON  api.account_name = a.name  
                WHERE 1 ';
        
        $sql .= ' ORDER BY api.id DESC';
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
                'account_name' => $oApikey->getAccountName(),
                'name' => $oApikey->getName(),
                'username' => $oApikey->getUserName(),
                'password' => $oApikey->getPassword(),
                'created_at' => $oApikey->getCreatedAt()
        ));
        return $row;
    }
    
    public function update(Apikey $oApikey)
    {
        $sql  = 'UPDATE  api_key SET
                   name =:name,
                   username =:username,
                   password =:password
                 WHERE id = :id
                ';
        $statement = $this->pdo->prepare($sql);
        $row = $statement->execute(array(
            'id' => $oApikey->getId(),
            'name' => $oApikey->getName(),
            'username' => $oApikey->getUserName(),
            'password' => $oApikey->getPassword()
        ));
        return $row;
    }
}

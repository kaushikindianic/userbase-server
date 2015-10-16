<?php

namespace UserBase\Server\Repository;

use UserBase\Server\Model\Account;
use RuntimeException;
use PDO;

class PdoAccountRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getByName($name)
    {
        $statement = $this->pdo->prepare(
            "SELECT * FROM account WHERE name=:name AND (deleted_at IS NULL OR deleted_at=0) LIMIT 1"
        );
        $statement->execute(array('name' => $name));
        $row = $statement->fetch();

        return $row ? $this->rowToAccount($row) : null;
    }

    private function userExistsByName($name)
    {
        $statement = $this->pdo->prepare(
            "SELECT name FROM user WHERE name=:name AND (deleted_at IS NULL OR deleted_at=0) LIMIT 1"
        );
        $statement->execute(array('name' => $name));

        return !!$statement->fetch();
    }

    public function getAll($limit = 10, $search = '')
    {   
        $aVal = array();
        $sql = 'SELECT * FROM account WHERE (deleted_at IS NULL OR deleted_at=0) ';
        
        if ($search) {
            $sql .= ' AND name LIKE  :search  OR  display_name LIKE :search ';
            $aVal[':search'] = "%".$search."%";
        }
        $sql .= '  ORDER BY name DESC';
        
        $statement = $this->pdo->prepare($sql);
        $statement->execute($aVal);
        $rows = $statement->fetchAll();
        $accounts = array();

        foreach ($rows as $row) {
            $accounts []= $this->rowToAccount($row);
        }
        return $accounts;
    }

    private function rowToAccount($row)
    {
        $account = new Account($row['name']);

        return $account->setCreatedAt($row['created_at'])
            ->setDeletedAt($row['deleted_at'])
            ->setAbout($row['about'])
           // ->setPictureUrl($row['picture_url'])
            ->setDisplayName($row['display_name'])
            ->setAccountType($row['account_type'])
        ;
    }

    public function add(account $account)
    {
//      $exists = $this->getByName($account->getName()) || $this->userExistsByName($account->getName());
        $exists = $this->getByName($account->getName());
        
        if ($exists === null) {
            $statement = $this->pdo->prepare(
                'INSERT INTO account (name, display_name, about, created_at, account_type) 
                    VALUES (:name, :display_name, :about, :created_at, :account_type)'
            );
            $statement->execute(
                array(
                    ':name' => $account->getName(),
                    ':display_name' => $account->getDisplayName(),
                    ':about' => $account->getAbout(),                    
                    ':created_at' => time(),
                    ':account_type' => $account->getAccountType(),
                )
            );
            //$this->update($account);

            return true;
        } else {
            return false;
        }
    }

    public function update(account $account)
    {
        $statement = $this->pdo->prepare(
            'UPDATE account
             SET display_name=:display_name, about=:about,
              account_type=:account_type
             WHERE name=:name AND (deleted_at IS NULL OR deleted_at=0)'
        );
        $statement->execute(
            array(
                ':name' => $account->getName(),
                ':display_name' => $account->getDisplayName(),
                ':about' => $account->getAbout(),              
                ':account_type' => $account->getAccountType(),
            )
        );
    }
    
    public function delete($name)
    {
        if (!$name) {
            throw new RuntimeException("account not specified");
        }
    
        $statement = $this->pdo->prepare("UPDATE account SET deleted_at = :deleted_at WHERE name=:name");
    
        $statement->execute(array(
            ':deleted_at' => time(),
            ':name' => $name
        ));
    }
    
    public function getAccountUsers($accountName)
    {
        $statement = $this->pdo->prepare("SELECT * FROM account_user WHERE  account_name = :account_name ORDER BY user_name ASC");
        $statement->execute(array(':account_name' => $accountName));
        $rows = $statement->fetchAll();
        
        $aUsers = array();
        
        foreach ($rows as $row) {
            $aUsers[] = $row['user_name'];
        }
        return $aUsers;
    }
    
    public function delAccUsers($accountName, $userName)
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM account_user
            WHERE account_name = :account_name
            AND user_name = :user_name'
        );
        $statement->execute(
            array(
                ':account_name' => $accountName,
                ':user_name' => $userName
            )
        );
    }
    
    public function addAccUser($accountName, $userName)
    {
        $statement = $this->pdo->prepare(
            'INSERT IGNORE INTO account_user (account_name, user_name)
            VALUES (:account_name, :user_name )'
        );
        $statement->execute(array(':account_name' => $accountName, ':user_name' => $userName));
        return true;
    }
    
    public function getByUserName($userName)
    {  
        $statement = $this->pdo->prepare(
            'SELECT  AU.account_name FROM account_user As AU
            JOIN  account as A ON AU.account_name = A.name  
            WHERE AU.user_name = :user_name 
            AND  A.deleted_at = 0
            ORDER BY AU.account_name ASC'
        );
        $statement->execute(array( ':user_name' => $userName));
        $rows = $statement->fetchAll();
        
        $accounts = array();

        foreach ($rows as $row) {
            $accounts[]= $this->getByName($row['account_name']);
        }
        return $accounts;
    }
    
    public function userAssignToAccount($accountName, $userName)
    {   
        $statement = $this->pdo->prepare('SELECT * FROM account_user WHERE account_name =:account_name AND user_name =:user_name LIMIT 1');
        $statement->execute(array(':account_name' => $accountName, ':user_name' => $userName));
        return $statement->fetch();
    }
    
    public function getByUserNameForApi($userName)
    {
        $statement = $this->pdo->prepare(
            'SELECT  AU.account_name FROM account_user As AU
            JOIN  account as A ON AU.account_name = A.name
            WHERE AU.user_name = :user_name
            AND  A.deleted_at = 0
            ORDER BY AU.account_name ASC'
            );
        $statement->execute(array( ':user_name' => $userName));
        $rows = $statement->fetchAll();
    
        $accounts = array();
    
        foreach ($rows as $row) {
            $accounts[]= $row['account_name'];
        }
        return $accounts;
    }    
    
    
}
<?php

namespace UserBase\Server\Repository;

use UserBase\Server\Model\App;
use UserBase\Server\Model\User;
use UserBase\Server\Model\Account;
use RuntimeException;
use PDO;

final class PdoAccountOldRepository
{
    private $pdo;
    private $appRepository;
    private $userRepository;
    

    public function __construct(PDO $pdo, $appRepository, $userRepository)
    {
        $this->pdo = $pdo;
        $this->appRepository = $appRepository;
        $this->userRepository = $userRepository;
    }
    
    public function getByUsername($username)
    {
        $statement = $this->pdo->prepare(
            "SELECT a.*
            FROM account AS a
            WHERE user_name=:username
            ORDER BY id DESC"
        );
        $statement->execute(
            array('username' => $username)
        );
        
        $accounts = array();
        while ($row = $statement->fetch()) {
            $account = $this->row2account($row);
            $accounts[] = $account;
        }
        return $accounts;
    }
    
    
    public function getByAppNameAndUsername($appname, $username)
    {
        $statement = $this->pdo->prepare(
            "SELECT a.*
            FROM account AS a
            WHERE user_name=:username
            AND app_name=:appname
            ORDER BY id DESC"
        );
        $statement->execute(
            array(
                'username' => $username,
                'appname' => $appname
            )
        );
        
        $row = $statement->fetch();
        if (!$row) {
            return null;
        }
        $account = $this->row2account($row);
        return $account;
    }
    
    private function row2account($row)
    {
        $account = new Account();
        $user = $this->userRepository->getByName($row['user_name']);
        $app = $this->appRepository->getByName($row['app_name']);
        $account->setUser($user);
        $account->setApp($app);
        return $account;
    }
}

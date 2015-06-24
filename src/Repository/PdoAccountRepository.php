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

    public function getById($id)
    {
        $statement = $this->pdo->prepare(
            "SELECT * FROM account WHERE id=:id AND (deleted_at IS NULL OR deleted_at=0) LIMIT 1"
        );
        $statement->execute(array('id' => $id));
        $row = $statement->fetch();

        return $row ? $this->rowToAccount($row) : null;
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

    public function getAll($limit = 10)
    {
        $statement = $this->pdo->prepare(
            "SELECT * FROM account WHERE (deleted_at IS NULL OR deleted_at=0) ORDER BY id DESC"
        );
        $statement->execute();
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

        return $account->setId($row['id'])
                ->setCreatedAt($row['created_at'])
                ->setDeletedAt($row['deleted_at'])
                ->setAbout($row['about'])
                ->setPictureUrl($row['picture_url'])
                ->setDisplayName($row['display_name']);
    }

    public function add(account $account)
    {
//      $exists = $this->getByName($account->getName()) || $this->userExistsByName($account->getName());
        $exists = $this->getByName($account->getName());
        
        if ($exists === null) {
            $statement = $this->pdo->prepare(
                'INSERT INTO account (name, display_name, about, picture_url, created_at) VALUES (:name, :display_name, :about, :picture_url, :created_at)'
            );
            $statement->execute(
                array(
                    ':name' => $account->getName(),
                    ':display_name' => $account->getDisplayName(),
                    ':about' => $account->getAbout(),
                    ':picture_url' => $account->getPictureUrl(),
                    ':created_at' => time(),
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
             SET display_name=:display_name, about=:about, picture_url=:picture_url
             WHERE name=:name AND (deleted_at IS NULL OR deleted_at=0)'
        );
        $statement->execute(
            array(
                ':name' => $account->getName(),
                ':display_name' => $account->getDisplayName(),
                ':about' => $account->getAbout(),
                ':picture_url' => $account->getPictureUrl(),
            )
        );
    }
    
    public function delete($name)
    {
        if (! $name) {
            throw new RuntimeException("account not specified");
        }
    
        $statement = $this->pdo->prepare("UPDATE account SET deleted_at = :deleted_at WHERE name=:name");
    
        $statement->execute(array(
            ':deleted_at' => time(),
            ':name' => $name
        ));
    }    
}

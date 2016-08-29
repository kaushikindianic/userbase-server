<?php
namespace UserBase\Server\Repository;

use UserBase\Server\Model\AccountEmail;
use RuntimeException;
use PDO;

class PdoAccountEmailRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByAccountName($accountName)
    {
        $sql = 'SELECT * FROM account_email WHERE account_name = :account_name ';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(array(':account_name' => $accountName));
        return $statement->fetchAll();
    }

    public function findByEmail($email)
    {
        $sql = 'SELECT * FROM account_email WHERE email = :email ';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(array(':email' => $email));
        return $statement->fetchAll();
    }

    public function getById($id)
    {
        $statement = $this->pdo->prepare('SELECT * FROM account_email WHERE id =:id');
        $statement->execute(array(':id' => (int) $id));
        return $statement->fetch();
    }

    public function add(AccountEmail $oModel)
    {
        $sql = 'INSERT INTO account_email
                (account_name, email, created_at, verified_at)
                VALUES (:account_name, :email, :created_at, :verified_at)';

        $statement = $this->pdo->prepare($sql);
        $row = $statement->execute(array(
            ':account_name' => $oModel->getAccountName(),
            ':email' => $oModel->getEmail(),
            ':created_at' => time(),
            ':verified_at' => $oModel->getVerifiedAt(),
        ));
        return $row;
    }

    public function update(AccountEmail $oModel)
    {
        $statement = $this->pdo->prepare('UPDATE account_email SET
                email = :email,
                verified_at = :verified_at
             WHERE id =:id');

        return   $row = $statement->execute(array(
            ':email' => $oModel->getEmail(),
            ':verified_at' => $oModel->getVerifiedAt(),
            ':id' => $oModel->getId()
        ));
    }


    public function setVerifiedAt($accountName, $email)
    {
        $statement = $this->pdo->prepare(
            'UPDATE account_email SET
            verified_at = :verified_at
            WHERE account_name=:account_name AND email=:email'
        );
        return  $row = $statement->execute(array(
            ':verified_at' => time(),
            ':account_name' => $accountName,
            ':email' => $email
        ));
    }

    public function remove($id)
    {
        $statement = $this->pdo->prepare('DELETE FROM account_email WHERE id =:id');
        return $statement->execute(array(':id' => (int) $id ));
    }
}

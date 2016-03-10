<?php
namespace UserBase\Server\Repository;

use UserBase\Server\Model\AccountTag;
use RuntimeException;
use PDO;

class PdoAccountTagRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findAll()
    {
        $statement = $this->pdo->prepare('SELECT * FROM account_tag ORDER BY name ASC');
        $statement->execute(array());
        return $statement->fetchAll();
    }

    public function removeAccountTag($accountName)
    {
        $statement = $this->pdo->prepare('DELETE FROM account_tag  WHERE account_name =:account_name');
        return $statement->execute(array(':account_name' => $accountName));
    }

    public function add(AccountTag $oAccountTagModel)
    {
        $sql = 'INSERT IGNORE INTO account_tag (account_name, tag_id) VALUES (:account_name, :tag_id)';
        $statement = $this->pdo->prepare($sql);
        $row = $statement->execute(array(
            'account_name' => $oAccountTagModel->getAccountName(),
            'tag_id' => $oAccountTagModel->getTagId()
        ));
        return $row;
    }

    public function findByAccountName($accountName)
    {
        $sql = 'SELECT T.* FROM  account_tag  AS AT
                JOIN tag AS T ON  AT.tag_id = T.id
                WHERE AT.account_name = :account_name';

        $statement = $this->pdo->prepare($sql);
        $statement->execute(array(':account_name' => $accountName));
        return $statement->fetchAll();
    }

    public function getByAccountName($accountName)
    {
        $sql = 'SELECT * FROM  account_tag  WHERE account_name = :account_name';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(array(':account_name' => $accountName));
        return $statement->fetchAll();
    }
}

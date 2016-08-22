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
        $statement = $this->pdo->prepare('SELECT
            at.*, t.name AS tag_name
            FROM account_tag AS at
            LEFT JOIN tag AS t ON t.id = at.tag_id
            ORDER BY at.account_name ASC');
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

    public function deleteById($accountTagId)
    {
        $statement = $this->pdo->prepare('DELETE FROM account_tag WHERE id = :account_tag_id');
        return $statement->execute(array(':account_tag_id' => $accountTagId));
    }

    public function deleteByAccountNameAndTagId($accountName, $tagId)
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM account_tag  WHERE account_name = :account_name AND tag_id = :tag_id '
        );
        return $statement->execute(array(':account_name' => $accountName, ':tag_id' => $tagId));
    }

    public function findByAccountName($accountName)
    {
        $sql = 'SELECT at.*, t.name as tag_name, t.description as tag_description
                FROM account_tag AS at
                JOIN tag AS t ON at.tag_id = t.id
                WHERE at.account_name = :account_name';

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

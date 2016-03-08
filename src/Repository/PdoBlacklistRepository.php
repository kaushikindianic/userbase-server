<?php
namespace UserBase\Server\Repository;

use UserBase\Server\Model\Blacklist;
use RuntimeException;
use PDO;

class PdoBlacklistRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function add(Blacklist $oBlacklistModel)
    {
        $sql = 'INSERT INTO blacklist(account_name, description) VALUES (:account_name, :description)';
        $statement = $this->pdo->prepare($sql);
        $row = $statement->execute(array(
            'account_name' => $oBlacklistModel->getAccountName(),
            'description' => $oBlacklistModel->getDescription()
        ));
        return $row;
    }

    public function update(Blacklist $oBlacklistModel)
    {
        $statement = $this->pdo->prepare('UPDATE blacklist SET
            account_name =:account_name, description =:description
             WHERE id =:id');

        return $statement->execute(array(
            'account_name' => $oBlacklistModel->getAccountName(),
            ':description' => $oBlacklistModel->getDescription(),
            ':id' => $oBlacklistModel->getId()
        ));
    }

    public function checkExist($accountName, $id = 0)
    {
        $aVal = array();
        $sql = 'SELECT * FROM blacklist WHERE account_name = :account_name';
        if ($id) {
            $sql .= ' AND id != :id';
            $aVal[':id'] = (int) $id;
        }
        $aVal[':account_name'] = $accountName;
        $statement = $this->pdo->prepare($sql);
        $statement->execute($aVal);
        return $statement->fetch();
    }

    public function getById($id)
    {
        $statement = $this->pdo->prepare('SELECT * FROM blacklist WHERE id =:id');
        $statement->execute(array('id' => (int) $id));
        return $statement->fetch();
    }

    public function findAll()
    {
        $statement = $this->pdo->prepare('SELECT * FROM blacklist');
        $statement->execute(array());
        return $statement->fetchAll();
    }

    public function remove($id)
    {
        $statement = $this->pdo->prepare('DELETE FROM blacklist  WHERE id =:id');
        return $statement->execute(array('id' => (int) $id ));
    }
}

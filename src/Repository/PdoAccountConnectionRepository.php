<?php
namespace UserBase\Server\Repository;

use UserBase\Server\Model\AccountConnection;
use RuntimeException;
use PDO;

class PdoAccountConnectionRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function add(AccountConnection $oAccountConnectionModel)
    {
        $sql = 'INSERT IGNORE INTO account_connection (account_name, connection_name, connection_type, created_at)
         VALUES (:account_name, :connection_name, :connection_type, :created_at)';

        $statement = $this->pdo->prepare($sql);
        $row = $statement->execute(array(
            ':account_name' => $oAccountConnectionModel->getAccountName(),
            ':connection_name' => $oAccountConnectionModel->getConnectionName(),
            ':connection_type' => $oAccountConnectionModel->getConnectionType(),
            ':created_at' => $oAccountConnectionModel->getCreatedAt()
        ));
        return $row;
    }

    public function getById($id)
    {
        $statement = $this->pdo->prepare('SELECT * FROM account_connection WHERE id =:id');
        $statement->execute(array('id' => (int) $id));
        return $statement->fetch();
    }

    public function totConnection($accountName)
    {
        $sql = 'SELECT count(id) AS tot FROM account_connection WHERE account_name = :account_name';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(array(':account_name' => $accountName));
        $row = $statement->fetch();
        return ($row)?  $row['tot'] : 0;
    }

    public function findByAccountName($accountName)
    {
        $sql = 'SELECT * FROM account_connection WHERE account_name = :account_name';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(array(':account_name' => $accountName));
        return $statement->fetchAll();
    }
}

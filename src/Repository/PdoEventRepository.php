<?php

namespace UserBase\Server\Repository;

use UserBase\Server\Model\Event;
use RuntimeException;
use PDO;

class PdoEventRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function add(event $event)
    {
        $statement = $this->pdo->prepare('INSERT INTO event (account_name, event_name, data, occured_at, admin_name)
                    VALUES (:name, :eventName, :data, :occuredAt, :adminName)');
        $statement->execute(array(
            ':name' => $event->getName(),
            ':eventName' => $event->getEventName(),
            ':data' => $event->getData(),
            ':occuredAt' => $event->getOccuredAt(),
            ':adminName' => $event->getAdminName()
        ));

        return true;
    }

    public function getAll()
    {
        $aVal = array();
        $sql = 'SELECT * FROM event  WHERE 1 ';
        $sql .= ' ORDER BY id DESC';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($aVal);

        $rows = $statement->fetchAll();
        return $rows;
    }

    public function findByAccountName($accountName)
    {
        if (!trim($accountName)) {
            return false;
        }
        $aVal = array();
        $sql = 'SELECT * FROM event  WHERE 1 ';
        $sql .= ' AND account_name = :account_name ';

        $aVal[':account_name'] = $accountName;
        $sql .= ' ORDER BY id DESC';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($aVal);

        $rows = $statement->fetchAll();
        return $rows;
    }
}

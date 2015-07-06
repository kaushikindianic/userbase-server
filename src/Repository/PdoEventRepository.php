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
        $statement = $this->pdo->prepare('INSERT INTO event (user_name, event_name, data, occured_at, admin_name) VALUES (:name, :eventName, :data, :occuredAt, :adminName)');
        $statement->execute(array(
            ':name' => $event->getName(),
            ':eventName' => $event->getEventName(),
            ':data' => $event->getData(),
            ':occuredAt' => $event->getOccuredAt(),
            ':adminName' => $event->getAdminName()
        ));
        
        return true;
    }    
    
    
}

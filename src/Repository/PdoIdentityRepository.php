<?php
namespace UserBase\Server\Repository;

use UserBase\Server\Model\Oauth2Identity;
use RuntimeException;
use PDO;

class PdoIdentityRepository
{

    private $pdo;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    public function getByUserName($userName)
    {
        $statement = $this->pdo->prepare('SELECT * FROM identities WHERE user_name = :user_name');
        $statement->execute(array( ':user_name' => $userName));
        $rows = $statement->fetchAll();
        return $rows;
    }
    
}
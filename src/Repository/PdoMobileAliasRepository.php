<?php
namespace UserBase\Server\Repository;

use UserBase\Server\Model\MobileAlias;
use RuntimeException;
use PDO;

class PdoMobileAliasRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function add(MobileAlias $oMobileAliasModel)
    {
        $sql = 'INSERT INTO mobile_alias(mobile, mobile_alias, description)
                VALUES (:mobile, :mobile_alias, :description)';
        $statement = $this->pdo->prepare($sql);
        $row = $statement->execute(array(
            ':mobile' => $oMobileAliasModel->getMobile(),
            ':mobile_alias' => $oMobileAliasModel->getMobileAlias(),
            ':description' => $oMobileAliasModel->getDescription()
        ));
        return $row;
    }

    public function update(MobileAlias $oMobileAliasModel)
    {
        $statement = $this->pdo->prepare('UPDATE mobile_alias SET
            mobile =:mobile, mobile_alias = :mobile_alias, description =:description
             WHERE id =:id');

        return $statement->execute(array(
            ':mobile' => $oMobileAliasModel->getMobile(),
            ':mobile_alias' => $oMobileAliasModel->getMobileAlias(),
            ':description' => $oMobileAliasModel->getDescription(),
            ':id' => $oMobileAliasModel->getId()
        ));
    }

    public function getById($id)
    {
        $statement = $this->pdo->prepare('SELECT * FROM mobile_alias WHERE id =:id');
        $statement->execute(array('id' => (int) $id));
        return $statement->fetch();
    }
    
    public function resolveAlias($mobile)
    {
        $statement = $this->pdo->prepare('SELECT * FROM mobile_alias WHERE mobile=:mobile');
        $statement->execute(array('mobile' => $mobile));
        $rows = $statement->fetch();
        if (!$rows) {
            return $mobile;
        }
        return trim($rows['mobile_alias']);
    }

    public function findAll()
    {
        $statement = $this->pdo->prepare('SELECT * FROM mobile_alias');
        $statement->execute(array());
        return $statement->fetchAll();
    }

    public function remove($id)
    {
        $statement = $this->pdo->prepare('DELETE FROM mobile_alias  WHERE id =:id');
        return $statement->execute(array(':id' => (int) $id ));
    }
}

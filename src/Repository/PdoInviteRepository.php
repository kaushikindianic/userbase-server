<?php
namespace UserBase\Server\Repository;

use UserBase\Server\Model\Invite;
use RuntimeException;
use PDO;

class PdoInviteRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function add(Invite $oInviteModel)
    {
        $sql = 'INSERT IGNORE INTO
            invite(created_at, inviter, display_name, email, payload, account_name)
            VALUES (:created_at, :inviter, :display_name, :email, :payload, :account_name)';
        $statement = $this->pdo->prepare($sql);
        $row = $statement->execute(array(
            'created_at' => time(),
            'inviter' => $oInviteModel->getInviter(),
            'display_name' => $oInviteModel->getDisplayName(),
            'email' => $oInviteModel->getEmail(),
            'payload' => $oInviteModel->getPayload(),
            'account_name' => $oInviteModel->getAccountName()
        ));
        return $row;
    }

    public function update(Invite $oInviteModel)
    {
        $statement = $this->pdo->prepare('UPDATE IGNORE invite SET
            inviter =:inviter,
            display_name=:display_name,
            email=:email,
            payload=:payload,
            account_name=:account_name
            WHERE id =:id');

        return $statement->execute(array(
            ':inviter' => $oInviteModel->getInviter(),
            ':display_name' => $oInviteModel->getDisplayName(),
            ':email' => $oInviteModel->getEmail(),
            ':payload' => $oInviteModel->getPayload(),
            ':account_name' => $oInviteModel->getAccountName(),
            ':id' => $oInviteModel->getId()
        ));
    }

    public function getById($id)
    {
        $statement = $this->pdo->prepare('SELECT * FROM invite WHERE id =:id');
        $statement->execute(array('id' => (int) $id));
        return $statement->fetch();
    }

    public function findAll()
    {
        $statement = $this->pdo->prepare('SELECT * FROM invite');
        $statement->execute(array());
        return $statement->fetchAll();
    }

    public function remove($id)
    {
        $statement = $this->pdo->prepare('DELETE FROM invite WHERE id =:id');
        return $statement->execute(array('id' => (int) $id ));
    }
}

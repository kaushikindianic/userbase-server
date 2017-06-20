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
            invite(created_at, inviter, inviter_org, display_name, email, payload, account_name)
            VALUES (:created_at, :inviter, :inviter_org, :display_name, :email, :payload, :account_name)';
        $statement = $this->pdo->prepare($sql);
        $row = $statement->execute(array(
            'created_at' => time(),
            'inviter' => $oInviteModel->getInviter(),
            'inviter_org' => $oInviteModel->getInviterOrg(),
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
            inviter_org =:inviter_org,
            display_name=:display_name,
            email=:email,
            payload=:payload,
            account_name=:account_name
            WHERE id =:id');

        return $statement->execute(array(
            ':inviter' => $oInviteModel->getInviter(),
            ':inviter_org' => $oInviteModel->getInviterOrg(),
            ':display_name' => $oInviteModel->getDisplayName(),
            ':email' => $oInviteModel->getEmail(),
            ':payload' => $oInviteModel->getPayload(),
            ':account_name' => $oInviteModel->getAccountName(),
            ':id' => $oInviteModel->getId()
        ));
    }

    public function accept($inviteId, $accountName)
    {
        $statement = $this->pdo->prepare('UPDATE IGNORE invite SET
            account_name=:account_name
            WHERE id =:id');

        return $statement->execute(array(

            ':account_name' => $accountName,
            ':id' => $inviteId
        ));
    }

    public function getById($id)
    {
        $statement = $this->pdo->prepare('SELECT * FROM invite WHERE id =:id');
        $statement->execute(array('id' => (int) $id));
        return $statement->fetch();
    }

    public function findByEmail($email)
    {
        $statement = $this->pdo->prepare('SELECT * FROM invite WHERE email = :email');
        $statement->execute(array('email' => $email));
        return $statement->fetchAll();
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


    public function updateFromArray($data)
    {
        $statement = $this->pdo->prepare('UPDATE invite SET
            inviter =:inviter,
            inviter_org =:inviter_org,
            display_name=:display_name,
            email=:email,
            payload=:payload,
            account_name=:account_name
            WHERE id =:id');

        return $statement->execute(array(
            ':inviter' => $data['inviter'],
            ':inviter_org' => $data['inviter_org'],
            ':display_name' => $data['display_name'],
            ':email' => $data['email'],
            ':payload' => $data['payload'],
            ':account_name' => $data['account_name'],
            ':id' => $data['id']
        ));
    }
}

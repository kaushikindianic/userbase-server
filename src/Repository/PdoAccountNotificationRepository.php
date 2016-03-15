<?php
namespace UserBase\Server\Repository;

use UserBase\Server\Model\AccountNotification;
use RuntimeException;
use PDO;

class PdoAccountNotificationRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByAccountName($accountName)
    {
        $sql = 'SELECT * FROM account_notification WHERE account_name = :account_name ';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(array(':account_name' => $accountName));
        return $statement->fetchAll();
    }

    public function add(AccountNotification $oAccountnotificationModel)
    {
        $sql = 'INSERT INTO account_notification
                (account_name, created_at, source_account_name, notification_type, subject, link, body)
                VALUES (:account_name, :created_at, :source_account_name, :notification_type, :subject, :link, :body)';

        $statement = $this->pdo->prepare($sql);
        $row = $statement->execute(array(
            ':account_name' => $oAccountnotificationModel->getAccountName(),
            ':created_at' => $oAccountnotificationModel->getCreatedAt(),
            ':source_account_name' => $oAccountnotificationModel->getSourceAccountName(),
            ':notification_type' => $oAccountnotificationModel->getNotificationType(),
            ':subject' => $oAccountnotificationModel->getSubject(),
            ':link' => $oAccountnotificationModel->getLink(),
            ':body' => $oAccountnotificationModel->getBody(),
        ));
        return $row;
    }

    public function getById($id)
    {
        $statement = $this->pdo->prepare('SELECT * FROM account_notification WHERE id =:id');
        $statement->execute(array('id' => (int) $id));
        return $statement->fetch();
    }

    public function searchData($accountName, $notificationType = '', $status = '')
    {
        $where = array(':account_name' => $accountName);

        $sql = 'SELECT * FROM account_notification WHERE account_name = :account_name ';

        if ($notificationType) {
            $sql .= ' AND notification_type = :notification_type ';
            $where[':notification_type'] = $notificationType;
        }
        $statement = $this->pdo->prepare($sql);
        $statement->execute($where);
        return $statement->fetchAll();
    }
}

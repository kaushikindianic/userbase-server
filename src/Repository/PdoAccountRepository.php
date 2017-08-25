<?php

namespace UserBase\Server\Repository;

use UserBase\Server\Model\Account;
use RuntimeException;
use PDO;

class PdoAccountRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getByName($name)
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM account WHERE name=:name AND (deleted_at IS NULL OR deleted_at=0) LIMIT 1'
        );
        $statement->execute(array('name' => $name));
        $row = $statement->fetch();

        return $row ? $this->rowToAccount($row) : null;
    }

    public function getByEmailAndMobile($email, $mobile)
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM account
            WHERE email=:email AND mobile=:mobile
            AND (deleted_at IS NULL OR deleted_at=0)
            LIMIT 1'
        );
        $statement->execute(
            [
                'email' => $email,
                'mobile' => $mobile,
            ]
        );
        $row = $statement->fetch();

        return $row ? $this->rowToAccount($row) : null;
    }

    public function getByEmail($email)
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM account WHERE email=:email AND (deleted_at IS NULL OR deleted_at=0) LIMIT 1'
        );
        $statement->execute(array('email' => $email));
        $row = $statement->fetch();
        if (!$row) {
            $statement = $this->pdo->prepare(
                'SELECT a.* FROM account AS a
                JOIN account_email AS ae ON ae.account_name = a.name
                WHERE ae.email=:email AND (a.deleted_at IS NULL OR a.deleted_at=0) LIMIT 1'
            );
            $statement->execute(array('email' => $email));
            $row = $statement->fetch();
        }

        return $row ? $this->rowToAccount($row) : null;
    }

    public function getByMobile($mobile)
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM account WHERE mobile=:mobile AND (deleted_at IS NULL OR deleted_at=0) LIMIT 1'
        );
        $statement->execute(array('mobile' => $mobile));
        $row = $statement->fetch();

        return $row ? $this->rowToAccount($row) : null;
    }

    public function getAccountUsersByType($accountname, $type)
    {
        $statement = $this->pdo->prepare(
            'SELECT account.* FROM account_user
            JOIN account ON account.name=account_user.user_name
            WHERE account_user.account_name=:account_name AND account.account_type=:account_type'
        );
        $statement->execute(
            array(
                'account_name' => $accountname,
                'account_type' => $type,
            )
        );
        $rows = $statement->fetchAll();
        $objs = array();
        foreach ($rows as $row) {
            $objs[] = $this->rowToAccount($row);
        }

        return $objs;
    }

    public function getUserAccountsByType($username, $type)
    {
        $statement = $this->pdo->prepare(
            'SELECT account.* FROM account_user
            JOIN account ON account.name=account_user.account_name
            WHERE account_user.user_name=:user_name AND account.account_type=:account_type'
        );
        $statement->execute(
            array(
                'user_name' => $username,
                'account_type' => $type,
            )
        );
        $rows = $statement->fetchAll();
        $objs = array();
        foreach ($rows as $row) {
            $objs[] = $this->rowToAccount($row);
        }

        return $objs;
    }

    private function userExistsByName($name)
    {
        $statement = $this->pdo->prepare(
            'SELECT name FROM user WHERE name=:name AND (deleted_at IS NULL OR deleted_at=0) LIMIT 1'
        );
        $statement->execute(array('name' => $name));

        return (bool) $statement->fetch();
    }

    public function getAll($limit = 10, $search = '', $accountType = '')
    {
        $aVal = array();

        /*
        $sql = 'SELECT a.*
            FROM account AS a
            WHERE (a.deleted_at IS NULL OR a.deleted_at = 0)';
        */
        $sql = 'SELECT a.* FROM account AS a';

        if ($search) {
            $sql .= ' LEFT JOIN account_email AS ae ON a.name = ae.account_name ';
        }

        $sql .= ' WHERE (a.deleted_at IS NULL OR a.deleted_at = 0) ';

        if ($search) {
            $sql .= ' AND name LIKE  :search  OR  display_name LIKE :search ';
            $sql .= ' OR mobile LIKE  :search ';
            $sql .= 'OR (a.email LIKE :search AND ae.email LIKE :search )';
            $aVal[':search'] = '%'.$search.'%';
        }
        if ($accountType) {
            $sql .= ' AND account_type = :account_type ';
            $aVal[':account_type'] = $accountType;
        }
        $sql .= ' ORDER BY created_at DESC';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($aVal);
        $rows = $statement->fetchAll();
        $accounts = array();

        foreach ($rows as $row) {
            $accounts[$row['name']] = $this->rowToAccount($row);
        }

        return $accounts;
    }

    private function rowToAccount($row)
    {
        $account = new Account($row['name']);

        return $account->setCreatedAt($row['created_at'])
            ->setDeletedAt($row['deleted_at'])
            ->setAbout($row['about'])
           // ->setPictureUrl($row['picture_url'])
            ->setDisplayName($row['display_name'])
            ->setAccountType($row['account_type'])
            ->setEmail($row['email'])
            ->setEmailVerifiedAt($row['email_verified_at'])
            ->setMobile($row['mobile'])
            ->setMobileCode($row['mobile_code'])
            ->setMobileVerifiedAt($row['mobile_verified_at'])
            ->setUrl($row['url'])
            ->setStatus($row['status'])
            ->setMessage($row['message'])
            ->setExpireAt($row['expire_at'])
            ->setApprovedAt($row['approved_at'])
        ;
    }

    public function add(account $account)
    {
        //      $exists = $this->getByName($account->getName()) || $this->userExistsByName($account->getName());
        $exists = $this->getByName($account->getName());

        if ($exists === null) {
            $statement = $this->pdo->prepare(
                'INSERT INTO account (name, display_name, about, created_at, account_type, email, mobile, url, status)
                    VALUES (:name, :display_name, :about, :created_at, :account_type, :email, :mobile, :url, :status)'
            );
            $row = $statement->execute(
                array(
                    ':name' => $account->getName(),
                    ':display_name' => $account->getDisplayName(),
                    ':about' => $account->getAbout(),
                    ':created_at' => time(),
                    ':account_type' => $account->getAccountType(),
                    ':email' => $account->getEmail(),
                    ':mobile' => $account->getMobile(),
                    ':url' => $account->getUrl(),
                    ':status' => $account->getStatus(),
                )
            );

            return $row;
        } else {
            return false;
        }
    }

    public function update(account $account)
    {
        $statement = $this->pdo->prepare(
            'UPDATE account
             SET display_name=:display_name, about=:about,
              account_type=:account_type,
              email=:email, mobile=:mobile, url=:url, status=:status,
              message=:message, approved_at=:approved_at, expire_at=:expire_at
             WHERE name=:name AND (deleted_at IS NULL OR deleted_at=0)'
        );
        //exit(date('Y-m-d H:i:s', $account->getApprovedAt()));
        return $statement->execute(
            array(
                ':name' => $account->getName(),
                ':display_name' => $account->getDisplayName(),
                ':about' => $account->getAbout(),
                ':account_type' => $account->getAccountType(),
                ':email' => $account->getEmail(),
                ':mobile' => $account->getMobile(),
                ':url' => $account->getUrl(),
                ':status' => $account->getStatus(),
                ':message' => $account->getMessage(),
                ':approved_at' => $account->getApprovedAt(),
                ':expire_at' => $account->getExpireAt(),
            )
        );
    }

    public function delete($name)
    {
        if (!$name) {
            throw new RuntimeException('account not specified');
        }

        $statement = $this->pdo->prepare('UPDATE account SET deleted_at = :deleted_at WHERE name=:name');

        $statement->execute(array(
            ':deleted_at' => time(),
            ':name' => $name,
        ));
    }

    public function getAccountUsers($accountName)
    {
        $statement = $this->pdo->
        prepare('SELECT * FROM account_user WHERE account_name = :account_name ORDER BY user_name ASC');
        $statement->execute(array(':account_name' => $accountName));
        $rows = $statement->fetchAll();

        $aUsers = array();

        foreach ($rows as $row) {
            $aUsers[] = $row['user_name'];
        }

        return $aUsers;
    }

    public function getAccountMembers($accountName)
    {
        $statement = $this->pdo->prepare('SELECT au.*, a.email FROM account_user AS au
                JOIN account AS a ON au.user_name = a.name
                WHERE  au.account_name = :account_name
            ORDER BY au.user_name ASC');
        $statement->execute(array(':account_name' => $accountName));
        $rows = $statement->fetchAll();

        return $rows;
    }

    public function delAccUsers($accountName, $userName)
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM account_user
            WHERE account_name = :account_name
            AND user_name = :user_name'
        );
        $statement->execute(
            array(
                ':account_name' => $accountName,
                ':user_name' => $userName,
            )
        );
    }

    public function addAccUser($accountName, $userName, $isOwner = 0)
    {
        $statement = $this->pdo->prepare(
            'INSERT IGNORE INTO account_user (account_name, user_name, is_owner)
            VALUES (:account_name, :user_name, :is_owner )'
        );
        $statement->execute(array(':account_name' => $accountName,
            ':user_name' => $userName,
            ':is_owner' => $isOwner,
        ));

        return true;
    }

    public function getByUserName($userName)
    {
        $statement = $this->pdo->prepare(
            'SELECT  AU.account_name FROM account_user As AU
            JOIN  account as A ON AU.account_name = A.name
            WHERE AU.user_name = :user_name
            AND (deleted_at IS NULL OR deleted_at=0)
            ORDER BY AU.account_name ASC'
        );
        $statement->execute(array(':user_name' => $userName));
        $rows = $statement->fetchAll();

        $accounts = array();

        foreach ($rows as $row) {
            $accounts[] = $this->getByName($row['account_name']);
        }

        return $accounts;
    }

    public function userAssignToAccount($accountName, $userName)
    {
        $statement = $this->pdo->
        prepare('SELECT * FROM account_user WHERE account_name =:account_name AND user_name =:user_name LIMIT 1');
        $statement->execute(array(':account_name' => $accountName, ':user_name' => $userName));

        return $statement->fetch();
    }

    public function updateMemberRole($accountName, $userName, $isOwner = 0)
    {
        $statement = $this->pdo->prepare(
            'UPDATE account_user SET  is_owner = :is_owner
             WHERE  user_name = :user_name  AND  account_name = :account_name'
        );

        $row = $statement->execute(array(':account_name' => $accountName,
            ':user_name' => $userName,
            ':is_owner' => $isOwner,
        ));

        return $row;
    }

    public function setEmailVerifiedStamp(Account $account, $stamp)
    {
        if (!$account) {
            throw new RuntimeException('Account not specified');
        }

        $statement = $this->pdo->prepare(
            'UPDATE account SET
            email_verified_at = :stamp
            WHERE name=:name'
        );

        $statement->execute(
            array(
                ':stamp' => $stamp,
                ':name' => $account->getName(),
            )
        );
    }

    public function setMobileVerifiedStamp(Account $account, $stamp)
    {
        if (!$account) {
            throw new RuntimeException('Account not specified');
        }

        $statement = $this->pdo->prepare(
            'UPDATE account SET
            mobile_verified_at = :stamp
            WHERE name=:name'
        );

        $statement->execute(
            array(
                ':stamp' => $stamp,
                ':name' => $account->getName(),
            )
        );
    }

    public function setMobileCode(Account $account)
    {
        if (!$account) {
            throw new RuntimeException('Account not specified');
        }
        $code = rand(10000000, 99999999);
        $statement = $this->pdo->prepare(
            'UPDATE account SET
            mobile_code = :code
            WHERE name=:name'
        );

        $statement->execute(
            array(
                ':code' => $code,
                ':name' => $account->getName(),
            )
        );

        return $code;
    }

    public function countBy($accountType = '')
    {
        $aVal = array();
        $sql = 'SELECT COUNT(*) AS totRec FROM account WHERE (deleted_at IS NULL OR deleted_at=0) ';

        if ($accountType) {
            $sql .= ' AND account_type = :account_type ';
            $aVal[':account_type'] = $accountType;
        }
        $statement = $this->pdo->prepare($sql);
        $statement->execute($aVal);
        $rows = $statement->fetch();

        return $rows ? $rows['totRec'] : 0;
    }

    public function getUsersByAcount($accountName)
    {
        $statement = $this->pdo->
        prepare('SELECT * FROM account_user WHERE account_name = :account_name ORDER BY user_name ASC');
        $statement->execute(array(':account_name' => $accountName));
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    public function updateAccUser($accountName, $username, $isOwner)
    {
        $statement = $this->pdo->prepare('UPDATE account_user SET is_owner = :is_owner WHERE account_name = :account_name AND user_name =:username');

        return $statement->execute([
            ':is_owner' => $isOwner,
            ':account_name' => $accountName,
            ':username' => $username,
        ]);
    }
}

<?php

namespace UserBase\Server\Repository;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Silex\Application;
use UserBase\Server\Model\User;
use Boost\Populator\ProtectedPopulator;
use RuntimeException;
use PDO;
use DateTime;

final class PdoUserRepository implements UserProviderInterface
{
    private $pdo;
    private $encoderFactory;
    private $oauth;
    private $accountRepo;
    private $enableMobile;

    public function __construct(PDO $pdo, $oauth, $encoderFactory, $accountRepo, $enableMobile)
    {
        $this->pdo = $pdo;
        $this->oauth = $oauth;
        $this->encoderFactory = $encoderFactory;
        $this->accountRepo = $accountRepo;
        $this->enableMobile = $enableMobile;
    }

    public function getByName($name)
    {
        if (!$name) {
            throw new RuntimeException('Missing argument: name');
        }
        $statement = $this->pdo->prepare(
            "SELECT u.password, u.password_updated_at, u.last_seen_at, u.is_admin, a.*
            FROM user AS u
            JOIN account AS a ON a.name=u.name
            LEFT JOIN account_email AS ae ON ae.account_name = u.name
            WHERE (
                (a.name=:name) OR
                (a.email=:email) OR
                (ae.email=:email AND !isnull(ae.verified_at))
            )
            LIMIT 1"
        );

        $statement->execute(array('name' => $name, 'email' => $name));
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        
        
        if (!$row) {
            return null;
        }

        $user = $this->row2user($row);
        if ($row['expire_at'] && ($row['expire_at']<time())) {
            return null;
            $user->setAccountExpired();
        }
        return $user;
    }

    public function getAll($limit = 10, $search = '')
    {
        $aVal = array();
        $sql = 'SELECT u.password, u.password_updated_at, u.is_admin, u.last_seen_at, a.* FROM user AS u
            JOIN account AS a ON a.name=u.name
            WHERE 1 ';

        if ($search) {
            $sql .= ' AND u.name LIKE :search OR a.email LIKE :search ';
            $aVal[':search'] = "%".$search."%";
        }
        $sql .= ' ORDER BY u.name DESC';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($aVal);

        $users = array();
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $user = $this->row2user($row);
            $users[] = $user;
        }
        return $users;
    }

    private function row2user($row)
    {
        $account = $this->accountRepo->getByName($row['name']);
        if (!$account) {
            throw new RuntimeException("No user account: " . $row['user']);
        }
        $user = new User($row['name']);
        $populator = new ProtectedPopulator();
        $populator->populate($user, $row);
        
        /*
        $user->setEmail($row['email']);
        $user->setCreatedAt($row['created_at']);
        $user->setDeletedAt($row['deleted_at']);
        $user->setLastSeenAt($row['last_seen_at']);
        $user->setPassword($row['password']);
        $user->setDisplayName($row['display_name']);
        print_r($row);exit();
        */
        
        $enabled = true;
        if (!$account->isEmailVerified()) {
            $enabled = false;
        }
        if ($this->enableMobile) {
            if (!$account->isMobileVerified()) {
                $enabled = false;
            }
        }

        $user->setEnabled($enabled);
//      $user->setPictureUrl($row['picture_url']);
        if ($row['is_admin']>0) {
            $user->setAdmin(true);
        }

        return $user;
    }

    public function register(Application $app, $name, $email)
    {
        $user = $this->getByName($name);
        if ($user) {
            throw new RuntimeException("Name already taken: " . $name);
        }

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $statement = $this->pdo->prepare(
            "INSERT INTO user(name) VALUES (:name)"
        );
        $statement->execute(
            array(
                ':name' => $name
            )
        );

        $userObj = $this->getByName($name);

        //$this->oauth->registerUser($app, $userObj);

        return $userObj;
    }


    public function setPassword(User $user, $password)
    {
        if (!$user) {
            throw new RuntimeException("User not specified");
        }

        $encoder = $this->encoderFactory->getEncoder($user);
        $hash = $encoder->encodePassword($password, $user->getSalt());

        $statement = $this->pdo->prepare(
            "UPDATE user SET
            password = :password, password_updated_at = :stamp
            WHERE name=:name"
        );

        return  $statement->execute(
            array(
                ':password' => $hash,
                ':stamp' => time(),
                ':name' => $user->getUsername()
            )
        );
    }

    // Needed for symfony user provider interface
    public function loadUserByUsername($username)
    {
        $user = $this->getByName($username);
        if (!$user) {
            throw new UsernameNotFoundException(sprintf('User %s is not found.', $username));
        }
        return $user;
    }


    // Needed for symfony user provider interface
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    // Needed for symfony user provider interface

    public function supportsClass($class)
    {
        return $class === 'Symfony\Component\Security\Core\User\User';
    }

    /*
    public function update($username, $data)
    {
        
    }
    */

    public function getSearchUsers($search = null)
    {
        $statement = $this->pdo->prepare("SELECT u.password, u.password_updated_at, u.last_seen_at, u.is_admin,
            a.*
            FROM user AS u
            JOIN account AS a ON a.name = u.name
                ".(($search)? ' WHERE u.name LIKE "%'.$search.'%"'  : '')." ORDER BY u.name DESC");

        $statement->execute();
        $users = array();
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $user = $this->row2user($row);
            $users[] = $user;
        }
        return $users;
    }

    public function encodePassword(User $user, $password)
    {
        $encoder = $this->encoderFactory->getEncoder($user);
        $hash = $encoder->encodePassword($password, $user->getSalt());
        return $hash;
    }
}

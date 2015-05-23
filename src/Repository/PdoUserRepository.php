<?php

namespace UserBase\Server\Repository;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use UserBase\Server\Model\User;
use RuntimeException;
use PDO;

final class PdoUserRepository implements UserProviderInterface
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    public function getById($id)
    {
        $statement = $this->pdo->prepare(
            "SELECT u.*
            FROM user AS u
            WHERE u.id=:id
            LIMIT 1"
        );
        $statement->execute(array('id' => $id));
        $row = $statement->fetch();

        if (!$row) {
            return null;
        }

        return $this->row2user($row);
    }
    
    public function getByName($name)
    {
        $statement = $this->pdo->prepare(
            "SELECT u.*
            FROM user AS u
            WHERE u.name=:name
            LIMIT 1"
        );
        $statement->execute(array('name' => $name));
        $row = $statement->fetch();

        if (!$row) {
            return null;
        }
        
        return $this->row2user($row);
    }
    
    public function getAll($limit = 10)
    {
        $statement = $this->pdo->prepare(
            "SELECT u.*
            FROM user AS u
            ORDER BY id DESC"
        );
        $statement->execute();
        $users = array();
        while ($row = $statement->fetch()) {
            $user = $this->row2user($row);
            $users[] = $user;
        }
        return $users;
    }
    
    private function row2user($row)
    {
        $user = new User($row['name']);
        $user->setEmail($row['email']);
        $user->setPassword($row['password']);
        
        return $user;
    }
    
    public function register($name, $email)
    {
        $user = $this->getByName($name);
        if ($user) {
            throw new RuntimeException("Name already taken: " . $name);
        }
        
        //$nodeId = $this->pdo->lastInsertId();
        
        $statement = $this->pdo->prepare(
            "INSERT INTO user(name, email) VALUES (:name, :email)"
        );
        $statement->execute(
            array(
                ':name' => $name,
                ':email' => $email
            )
        );
        
        return $this->getByName($name);
    }

    
    public function setPassword($name, $password)
    {
        $account = $this->getByName($name);
        if (!$account) {
            throw new RuntimeException("Username does not exist");
        }
        
        $hash = $this->getPasswordHash($password);
    
        $statement = $this->pdo->prepare(
            "UPDATE user SET password = :password WHERE name=:name"
        );
        
        $statement->execute(
            array(
                ':password' => $hash,
                ':name' => $name
            )
        );
    }
    
    
    public function getPasswordHash($password)
    {
        // Initialize sufficiently randomized salt
        $salt = strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');
        // Create $2a$ (bcrypt) password, switch to 2y once prod runs 5.3.7
        // http://php.net/manual/en/function.crypt.php
        $hash = crypt($password, '$2a$10$' . $salt . '$');
        return $hash;
    }



    // Needed for symfony user provider interface

    public function loadUserByUsername($username)
    {
        $account = $this->getByName($username);
        if (!$account) {
            throw new UsernameNotFoundException(sprintf('User %s is not found.', $username));
        }
        return $account;
    }


    // Needed for symfony user provider interface

    public function refreshUser(UserInterface $account)
    {
        if (!$account instanceof Account) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($account)));
        }

        return $this->loadUserByUsername($account->getUsername());
    }

    // Needed for symfony user provider interface

    public function supportsClass($class)
    {
        return $class === 'Symfony\Component\Security\Core\User\User';
    }
    
    public function update($username, $data)
    {
        if (isset($data['displayname'])) {
            $statement = $this->pdo->prepare(
                "UPDATE user SET displayname = :displayname WHERE name=:name"
            );
            
            $statement->execute(
                array(
                    ':displayname' => $data['displayname'],
                    ':name' => $username
                )
            );
        }

        if (isset($data['bio'])) {
            $statement = $this->pdo->prepare(
                "UPDATE user SET bio = :bio WHERE name=:name"
            );
            
            $statement->execute(
                array(
                    ':bio' => $data['bio'],
                    ':name' => $username
                )
            );
        }

        if (isset($data['pictureurl'])) {
            $statement = $this->pdo->prepare(
                "UPDATE user SET pictureurl = :pictureurl WHERE name=:name"
            );
            
            $statement->execute(
                array(
                    ':pictureurl' => $data['pictureurl'],
                    ':name' => $username
                )
            );
        }
    }
}

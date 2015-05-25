<?php

namespace UserBase\Server\Repository;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use UserBase\Server\Model\User;
use RuntimeException;
use PDO;

final class PdoAdminRepository implements UserProviderInterface
{
    private $pdo;
    private $encoderFactory;

    public function __construct(PDO $pdo, $encoderFactory)
    {
        $this->pdo = $pdo;
        $this->encoderFactory = $encoderFactory;
    }
    
    private function row2user($row)
    {
        $user = new User($row['name']);
        $user->setEmail($row['email']);
        $user->setCreatedAt($row['created_at']);
        $user->setDeletedAt($row['deleted_at']);
        $user->setLastSeenAt($row['last_seen_at']);
        $user->setPassword($row['password']);
        $user->setDisplayName($row['display_name']);
        
        return $user;
    }
    
    // Needed for symfony user provider interface
    public function loadUserByUsername($username)
    {
        $statement = $this->pdo->prepare(
            "SELECT u.*
            FROM user AS u
            WHERE u.name=:name
            LIMIT 1"
        );
        $statement->execute(array('name' => $username));
        $row = $statement->fetch();

        if (!$row) {
            throw new UsernameNotFoundException(sprintf('User %s is not found.', $username));
        }
        
        $user = $this->row2user($row);
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
}

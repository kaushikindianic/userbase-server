<?php

namespace UserBase\Server\Repository;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Silex\Application;
use UserBase\Server\Model\User;
use RuntimeException;
use PDO;

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
        $statement = $this->pdo->prepare(
            "SELECT u.*
            FROM user AS u
            JOIN account AS a ON a.name=u.name
            WHERE (u.name=:name OR a.email=:email)
            LIMIT 1"
        );

        $statement->execute(array('name' => $name, 'email' => $name));
        $row = $statement->fetch();

        if (!$row) {
            return null;
        }

        return $this->row2user($row);
    }

    public function getAll($limit = 10, $search = '')
    {
        $aVal = array();
        $sql = 'SELECT * FROM user WHERE 1 ';

        if ($search) {
            $sql .= ' AND name LIKE  :search  OR  email LIKE :search ';
            $aVal[':search'] = "%".$search."%";
        }
        $sql .= ' ORDER BY name DESC';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($aVal);

        $users = array();
        while ($row = $statement->fetch()) {
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
        $user->setEmail($row['email']);
        $user->setCreatedAt($row['created_at']);
        $user->setDeletedAt($row['deleted_at']);
        $user->setLastSeenAt($row['last_seen_at']);
        $user->setPassword($row['password']);
        $user->setDisplayName($row['display_name']);
        $user->setAlias($row['alias']);
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
            "INSERT INTO user(name, email, created_at) VALUES (:name, :email, :stamp)"
        );
        $statement->execute(
            array(
                ':name' => $name,
                ':email' => $email,
                ':stamp' => time()
            )
        );

        $userObj = $this->getByName($name);

        $this->oauth->registerUser($app, $userObj);

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

    public function setEmail(User $user, $email)
    {
        if (!$user) {
            throw new RuntimeException("User not specified");
        }


        $statement = $this->pdo->prepare(
            "UPDATE user SET
            email = :email
            WHERE name=:name"
        );

        $statement->execute(
            array(
                ':email' => $email,
                ':name' => $user->getUsername()
            )
        );
    }

    public function setDisplayName(User $user, $displayname)
    {
        if (!$user) {
            throw new RuntimeException("User not specified");
        }


        $statement = $this->pdo->prepare(
            "UPDATE user SET
            display_name = :displayname
            WHERE name=:name"
        );

        $statement->execute(
            array(
                ':displayname' => $displayname,
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
                "UPDATE user SET picture_url = :pictureurl WHERE name=:name"
            );

            $statement->execute(
                array(
                    ':pictureurl' => $data['pictureurl'],
                    ':name' => $username
                )
            );
        }
    }

    public function getSearchUsers($search = null)
    {
        $statement = $this->pdo->prepare("SELECT u.* FROM user AS u
                ".(($search)? ' WHERE name LIKE "%'.$search.'%"'  : '')." ORDER BY name DESC");

        $statement->execute();
        $users = array();
        while ($row = $statement->fetch()) {
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

<?php
namespace UserBase\Server\Repository;

use UserBase\Server\Model\App;
use RuntimeException;
use PDO;

final class PdoAppRepository
{

    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getById($id)
    {
        $statement = $this->pdo->prepare("SELECT a.*
            FROM app AS a
            WHERE a.id=:id
            LIMIT 1");
        $statement->execute(array(
            'id' => $id
        ));
        $row = $statement->fetch();

        if (! $row) {
            return null;
        }

        return $this->rowToApp($row);
    }

    public function getByName($name)
    {
        $statement = $this->pdo->prepare("SELECT a.*
            FROM app AS a
            WHERE a.name=:name
            LIMIT 1");
        $statement->execute(array(
            'name' => $name
        ));
        $row = $statement->fetch();

        if (! $row) {
            return null;
        }

        return $this->rowToApp($row);
    }

    public function getAll($limit = 10, $search = '')
    {
        $aVal = array();
        $sql = 'SELECT * FROM app WHERE deleted_at = 0 ';

        if ($search) {
            $sql .= ' AND name LIKE  :search  OR  display_name LIKE :search ';
            $aVal[':search'] = "%".$search."%";
        }
        $sql .= '  ORDER BY id DESC';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($aVal);

        $apps = array();
        while ($row = $statement->fetch()) {
            $app = $this->rowToApp($row);
            $apps[] = $app;
        }
        return $apps;
    }

    public function add(App $apps)
    {
        $exists = $this->getByName($apps->getName());

        if (is_null($exists)) {
            $statement = $this->pdo->prepare('INSERT INTO app
                (name, display_name, base_url, about, picture_url, created_at)
                VALUES (:name, :display_name, :base_url, :about, :picture_url, :created_at)');

            $statement->execute(array(
                ':name' => $apps->getName(),
                ':display_name' => $apps->getDisplayName(),
                ':base_url' => $apps->getBaseUrl(),
                ':about' => $apps->getAbout(),
                ':picture_url' => $apps->getPictureUrl(),
                ':created_at' => time(),
            ));

            return true;
        } else {
            return false;
        }
    }

    public function update(App $apps)
    {
        $statement = $this->pdo->prepare('UPDATE `app`
             SET display_name = :display_name,
                base_url = :base_url,
        		about = :about,
        		picture_url = :picture_url
             WHERE name=:name');
        $statement->execute(array(
            ':name' => $apps->getName(),
            ':display_name' => $apps->getDisplayName(),
            ':base_url' => $apps->getBaseUrl(),
            ':about' => $apps->getAbout(),
            ':picture_url' => $apps->getPictureUrl()
        ));
    }

    public function delete($name)
    {
        if (! $name) {
            throw new RuntimeException("apps not specified");
        }

        $statement = $this->pdo->prepare("UPDATE app SET deleted_at = :deleted_at WHERE name=:name");

        $statement->execute(array(
            ':deleted_at' => time(),
            ':name' => $name
        ));
    }

    private function rowToApp($row)
    {
        $app = new App();
        $app->setName($row['name']);
        $app->setDisplayName($row['display_name']);
        $app->setBaseUrl($row['base_url']);
        $app->setPictureUrl($row['picture_url']);
        $app->setCreatedAt($row['created_at']);
        $app->setDeletedAt($row['deleted_at']);
        $app->setAbout($row['about']);
        return $app;
    }

    public function getAppUsers($appname)
    {
        $statement = $this->pdo->prepare("SELECT * FROM app_user WHERE app_name = :app_name ORDER BY user_name ASC");
        $statement->execute(array(':app_name' => $appname));
        $rows = $statement->fetchAll();

        $aUsers = array();

        foreach ($rows as $row) {
            $aUsers[] = $row['user_name'];
        }
        return $aUsers;
    }

    public function delAppUser($appname, $userName)
    {
        $statement = $this->pdo->prepare('Delete From app_user WHERE app_name = :app_name AND user_name = :user_name');
        $statement->execute(array(':app_name' => $appname, ':user_name' => $userName));
    }

    public function addAppUser($appname, $userName)
    {
        $statement = $this->pdo->prepare(
            'INSERT IGNORE INTO app_user (app_name, user_name) VALUES (:app_name, :user_name)'
        );
        $statement->execute(array(':app_name' => $appname, ':user_name' => $userName));
        return true;
    }

    public function getByUserName($userName)
    {
        $statement = $this->pdo->prepare(
            'SELECT AU.app_name FROM app_user AS AU
                JOIN  app AS A ON AU.app_name = A.name
                WHERE user_name = :user_name
                AND  A.deleted_at = 0
                ORDER BY AU.app_name ASC'
        );

        $statement->execute(array( ':user_name' => $userName));
        $rows = $statement->fetchAll();

        return $rows;
    }
}

<?php

namespace UserBase\Server\Repository;

use UserBase\Server\Model\Group;
use RuntimeException;
use PDO;

class PdoGroupRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getById($id)
    {
        $statement = $this->pdo->prepare(
            "SELECT * FROM `group` WHERE id=:id AND (deleted_at IS NULL OR deleted_at=0) LIMIT 1"
        );
        $statement->execute(array('id' => $id));
        $row = $statement->fetch();

        return $row ? $this->row2group($row) : null;
    }

    public function getByName($name)
    {
        $statement = $this->pdo->prepare(
            "SELECT * FROM `group` WHERE name=:name AND (deleted_at IS NULL OR deleted_at=0) LIMIT 1"
        );
        $statement->execute(array('name' => $name));
        $row = $statement->fetch();

        return $row ? $this->row2group($row) : null;
    }

    private function userExistsByName($name)
    {
        $statement = $this->pdo->prepare(
            "SELECT name FROM user WHERE name=:name AND (deleted_at IS NULL OR deleted_at=0) LIMIT 1"
        );
        $statement->execute(array('name' => $name));

        return !!$statement->fetch();
    }

    public function getAll($limit = 10)
    {
        $statement = $this->pdo->prepare(
            "SELECT * FROM `group` WHERE (deleted_at IS NULL OR deleted_at=0) ORDER BY id DESC"
        );
        $statement->execute();
        $rows = $statement->fetchAll();

        $groups = array();

        foreach ($rows as $row) {
            $groups []= $this->row2group($row);
        }

        return $groups;
    }

    private function row2group($row)
    {
        $group = new Group($row['name']);

        return $group->setId($row['id'])
            ->setCreatedAt($row['created_at'])
            ->setDeletedAt($row['deleted_at'])
            ->setAbout($row['about'])
            ->setPictureUrl($row['picture_url'])
            ->setDisplayName($row['display_name']);
    }

    public function add(Group $group)
    {
        $exists = $this->getByName($group->getName()) || $this->userExistsByName($group->getName());
        if ($exists === null) {
            $statement = $this->pdo->prepare(
                'INSERT INTO `group` (name, created_at) VALUES (:name, :created_at)'
            );
            $statement->execute(
                array(
                    'name' => $group->getName(),
                    'created_at' => time(),
                )
            );
            $this->update($group);

            return true;
        } else {
            return false;
        }
    }

    public function update(Group $group)
    {
        $statement = $this->pdo->prepare(
            'UPDATE `group`
             SET display_name=:display_name, about=:about, picture_url=:picture_url
             WHERE name=:name AND (deleted_at IS NULL OR deleted_at=0)'
        );
        $statement->execute(
            array(
                'name' => $group->getName(),
                'display_name' => $group->getDisplayName(),
                'about' => $group->getAbout(),
                'picture_url' => $group->getPictureUrl(),
            )
        );
    }
}

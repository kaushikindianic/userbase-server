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
            "SELECT * FROM group WHERE id=:id AND deleted_at IS NULL LIMIT 1"
        );
        $statement->execute(array('id' => $id));
        $row = $statement->fetch();

        return $row ? $this->row2group($row) : null;
    }

    public function getByName($name)
    {
        $statement = $this->pdo->prepare(
            "SELECT * FROM group WHERE name=:name AND deleted_at IS NULL LIMIT 1"
        );
        $statement->execute(array('name' => $name));
        $row = $statement->fetch();

        return $row ? $this->row2user($row) : null;
    }

    public function getAll($limit = 10)
    {
        $statement = $this->pdo->prepare(
            "SELECT * FROM group WHERE deleted_at IS NULL ORDER BY id DESC"
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

        return $group->setCreatedAt($row['created_at'])
            ->setDeletedAt($row['deleted_at'])
            ->setAboutl($row['about'])
            ->setPicturUrl($row['picture_url'])
            ->setDisplayName($row['display_name']);
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

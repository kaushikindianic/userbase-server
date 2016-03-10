<?php
namespace UserBase\Server\Model;

class AccountConnection
{
    private $id;
    private $account_name;
    private $connection_name;
    private $connection_type;
    private $created_at;
    private $deleted_at;


    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setAccountName($account_name)
    {
        $this->account_name = $account_name;
        return $this;
    }

    public function getAccountName()
    {
        return $this->account_name;
    }

    public function setConnectionName($connection_name)
    {
        $this->connection_name = $connection_name;
        return $this;
    }

    public function getConnectionName()
    {
        return $this->connection_name;
    }

    public function setConnectionType($connection_type)
    {
        $this->connection_type = $connection_type;
        return $this;
    }

    public function getConnectionType()
    {
        return $this->connection_type;
    }

    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    public function setDeletedAt($deletedAt)
    {
        $this->deleted_at= $deletedAt;
        return $this;
    }

    public function getDeletedAt()
    {
        return $this->deleted_at;
    }
}

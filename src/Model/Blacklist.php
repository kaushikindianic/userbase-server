<?php
namespace UserBase\Server\Model;

class Blacklist
{
    private $id;
    private $accountName;
    private $description;

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setAccountName($accountName)
    {
        $this->accountName = $accountName;
        return $this;
    }

    public function getAccountName()
    {
        return $this->accountName;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }
}

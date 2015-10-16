<?php
namespace UserBase\Server\Model;

class Apikey
{
    private $id;
    private $name;
    private $accountName;
    private $username;
    private $password;
    private $createdAt;
    private $deletedAt;
    
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function setName($name)
    {
        $this->name = $name;
        return $this;
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
    
    public function getName()
    {
        return $this->name;
    }    
    
    public function setUserName($username)
    {
        $this->username = $username;
        return $this;
    }
    
    public function getUserName()
    {
        return $this->username;
    }
    
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }
    
    public function getPassword()
    {
        return $this->password;
    }
    
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    
        return $this;
    }
    
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }        
}
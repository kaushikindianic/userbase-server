<?php
namespace UserBase\Server\Model;

class Space
{
    private $id;
    private $name;
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
    
    public function getName()
    {
        return $this->name;
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

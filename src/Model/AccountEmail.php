<?php
namespace UserBase\Server\Model;

class AccountEmail
{
    private $id;
    private $account_name;
    private $email;
    private $verified_at;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
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

    public function getEmail()
    {
        return $this->email;
    }
    
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }
    
    public function getVerifiedAt()
    {
        return $this->verified_at;
    }
    
    public function setVerifiedAt($verified_at)
    {
        $this->verified_at = $verified_at;
        return $this;
    }
}

<?php
namespace UserBase\Server\Model;

class Invite
{
    private $id;
    private $inviter;
    private $inviter_org;
    private $display_name;
    private $email;
    private $created_at;
    private $payload;
    private $account_name;

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getInviter()
    {
        return $this->inviter;
    }

    public function setInviter($inviter)
    {
        $this->inviter = $inviter;
        return $this;
    }

    public function getInviterOrg()
    {
        return $this->inviter_org;
    }

    public function setInviterOrg($inviter_org)
    {
        $this->inviter_org = $inviter_org;
        return $this;
    }


    public function getDisplayName()
    {
        return $this->displayName;
    }

    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
        return $this;
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

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function setPayload($payload)
    {
        $this->payload = $payload;
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
}

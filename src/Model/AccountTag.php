<?php
namespace UserBase\Server\Model;

class AccountTag
{
    private $id;
    private $account_name;
    private $tag_id;

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

    public function setTagId($tag_id)
    {
        $this->tag_id = $tag_id;
        return $this;
    }

    public function getTagId()
    {
        return $this->tag_id;
    }
}

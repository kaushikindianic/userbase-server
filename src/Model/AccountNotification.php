<?php
namespace UserBase\Server\Model;

class AccountNotification
{
    private $id;
    private $account_name;
    private $created_at;
    private $seen_at;
    private $source_account_name;
    private $notification_type;
    private $subject;
    private $link;
    private $body;

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

    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    public function setSeenAt($seen_at)
    {
        $this->seen_at = $seen_at;
        return $this;
    }

    public function getSeenAt()
    {
        return $this->seen_at;
    }

    public function setSourceAccountName($source_account_name)
    {
        $this->source_account_name = $source_account_name;
        return $this;
    }

    public function getSourceAccountName()
    {
        return $this->source_account_name;
    }

    public function setNotificationType($notification_type)
    {
        $this->notification_type = $notification_type;
        return $this;
    }

    public function getNotificationType()
    {
        return $this->notification_type;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setLink($link)
    {
        $this->link = $link;
        return $this;
    }

    public function getLink()
    {
        return $this->link;
    }

    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    public function getBody()
    {
        return $this->body;
    }
}

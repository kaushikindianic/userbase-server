<?php

namespace UserBase\Server\Model;

class Account
{
    private $name;
    private $about;
    private $displayName;
    private $pictureUrl;
    private $createdAt;
    private $deletedAt;

    public function __construct($name)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('The name cannot be empty.');
        }

        $this->setCreatedAt(time())->name = $name;
    }
    
    public function getName()
    {
        return $this->name;
    }

    public function getAbout()
    {
        return $this->about;
    }

    public function setAbout($about)
    {
        $this->about = $about;

        return $this;
    }

    public function getDisplayName()
    {
        return $this->displayName ? $this->displayName : $this->name;
    }

    public function setDisplayName($name)
    {
        $this->displayName = $name;

        return $this;
    }

    public function getRawDisplayName()
    {
        return $this->displayName;
    }

    public function getPictureUrl($size = null)
    {
        return $this->pictureUrl;
    }

    public function setPictureUrl($url)
    {
        $this->pictureUrl = $url;

        return $this;
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

    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }
}

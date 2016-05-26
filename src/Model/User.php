<?php

namespace UserBase\Server\Model;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;

final class User implements AdvancedUserInterface
{
    private $password;
    private $enabled;
    private $accountNonExpired;
    private $credentialsNonExpired;
    private $accountNonLocked;
    private $roles;
    //private $displayName;
    
    //private $createdAt;
    private $passwordUpdatedAt;
    private $lastSeenAt;
    //private $deletedAt;
    private $isAdmin = false;
    //private $alias;

    public function __construct($name)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('The name cannot be empty.');
        }

        $this->name = $name;
        $this->password = null;
        $this->enabled = false;
        $this->accountNonExpired = true;
        $this->credentialsNonExpired = true;
        $this->accountNonLocked = true;
        $this->roles = array();
        $this->salt = "KJH6212kjwek_fj23D01-239.1023fkjdsj^k2hdfssfjk!h234uiy4324";
    }
    
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }
    
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }
    
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }
    
    public function getPasswordUpdatedAt()
    {
        return $this->passwordUpdatedAt;
    }
    
    public function setPasswordUpdatedAt($passwordUpdatedAt)
    {
        $this->passwordUpdatedAt = $passwordUpdatedAt;
    }
    
    public function getLastSeenAt()
    {
        return $this->lastSeenAt;
    }
    
    public function setLastSeenAt($lastSeenAt)
    {
        if ($this->lastSeenAt>0) {
            $this->lastSeenAt = $lastSeenAt;
        }
        return null;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return $this->password;
    }
    
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->getName();
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getDisplayName()
    {
        if ($this->displayName) {
            return $this->displayName;
        }
        return $this->name;
    }
    
    public function setDisplayName($name)
    {
        $this->displayName = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function isAccountNonExpired()
    {
        return $this->accountNonExpired;
    }
    
    public function setAccountExpired()
    {
        $this->accountNonExpired = false;
    }

    /**
     * {@inheritdoc}
     */
    public function isAccountNonLocked()
    {
        return $this->accountNonLocked;
    }

    /**
     * {@inheritdoc}
     */
    public function isCredentialsNonExpired()
    {
        return $this->credentialsNonExpired;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->enabled;
    }
    
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
    }
    
    public function setEmail($email)
    {
        $this->email = $email;
        
        return $this;
    }
    
    public function getEmail()
    {
        return $this->email;
    }
    
    private $pictureUrl;
    
    public function getPictureUrl($size = null)
    {
        if ($this->pictureUrl) {
            return $this->pictureUrl;
        }
        $url = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($this->email))) . "?d=retro";
        return $url;
    }
    
    public function setPictureUrl($url)
    {
        $this->pictureUrl = $url;
        return $his;
    }

    public function setAdmin($bool)
    {
        $this->isAdmin = $bool;
    }
    
    public function isAdmin()
    {
        if ($this->isAdmin) {
            return true;
        }
        return false;
    }
    
    /*
    
    public function getAlias()
    {
        return $this->alias;
    }
    
    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }
    */
}

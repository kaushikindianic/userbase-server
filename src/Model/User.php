<?php

namespace UserBase\Server\Model;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;

final class User implements AdvancedUserInterface
{
    protected $name;
    protected $password;
    protected $email;
    protected $enabled;
    protected $accountNonExpired;
    protected $credentialsNonExpired;
    protected $accountNonLocked;
    protected $roles;
    protected $display_name;
    
    protected $created_at;
    protected $password_updated_at;
    protected $last_seen_at;
    protected $deleted_at;
    protected $isAdmin = false;
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
        return $this->created_at;
    }
    
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
    }
    
    public function getDeletedAt()
    {
        return $this->deleted_at;
    }
    
    public function setDeletedAt($deleted_at)
    {
        $this->deleted_at = $deleted_at;
    }
    
    public function getPasswordUpdatedAt()
    {
        return $this->password_updated_at;
    }
    
    public function setPasswordUpdatedAt($password_updated_at)
    {
        $this->password_updated_at = $password_updated_at;
    }
    
    public function getLastSeenAt()
    {
        return $this->last_seen_at;
    }
    
    public function setLastSeenAt($last_seen_at)
    {
        if ($this->last_seen_at>0) {
            $this->last_seen_at = $last_seen_at;
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
        if ($this->display_name) {
            return $this->display_name;
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

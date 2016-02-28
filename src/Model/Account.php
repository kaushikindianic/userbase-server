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
    private $accountType;
    private $email;
    private $emailVerifiedAt;
    private $mobile;
    private $mobileVerifiedAt;
    private $mobileCode;
    private $url;

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
    
    public function getEmail()
    {
        return $this->email;
    }
    
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;        
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
        if ($this->pictureUrl) {
            return $this->pictureUrl;
        }
        $url = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($this->email))) . "?d=retro";
        return $url;
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
    public function setAccountType($accountType)
    {
        $this->accountType = $accountType;
    
        return $this;
    }
    
    public function getAccountType()
    {
        return $this->accountType;
    }
    
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }
    
    public function getUrl()
    {
        return $this->url;
    }
    
    public function getInitials()
    {
        $parts = explode(' ', $this->getDisplayName());
        if (count($parts)>1) {
            $initials = $parts[0][0];
            $initials .= $parts[count($parts)-1][0];
        } else {
            $initials = substr($this->getDisplayName(), 0, 2);
        }
        if ($this->getAccountType()=='apikey') {
            $initials = '#';
        }
        return strtoupper($initials);
    }
    
    public function getMobile()
    {
        return $this->mobile;
    }
    
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;
        return $this;
    }
    
    public function getEmailVerifiedAt()
    {
        return $this->emailVerifiedAt;
    }
    
    public function isEmailVerified()
    {
        return $this->emailVerifiedAt > 0;
    }
    
    public function setEmailVerifiedAt($emailVerifiedAt)
    {
        $this->emailVerifiedAt = $emailVerifiedAt;
        return $this;
    }


    public function getMobileVerifiedAt()
    {
        return $this->mobileVerifiedAt;
    }

    public function isMobileVerified()
    {
        if (!$this->hasValidMobile()) {
            return false;
        }
        return $this->mobileVerifiedAt > 0;
    }

    public function setMobileVerifiedAt($mobileVerifiedAt)
    {
        $this->mobileVerifiedAt = $mobileVerifiedAt;
        return $this;
    }
    
    public function getMobileCode()
    {
        return $this->mobileCode;
    }
    
    public function setMobileCode($mobileCode)
    {
        $this->mobileCode = $mobileCode;
        return $this;
    }
    
    public function hasValidMobile()
    {
        $mobile = trim($this->getMobile());
        if (strlen($mobile)!=13) {
            return false;
        }
        return true;
    }
}

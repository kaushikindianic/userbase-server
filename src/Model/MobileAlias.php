<?php
namespace Userbase\Server\Model;

class MobileAlias
{
    private $id;
    private $mobile;
    private $mobile_alias;
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

    public function setMobile($mobile)
    {
        $this->mobile = $mobile;
        return $this;
    }

    public function getMobile()
    {
        return $this->mobile;
    }

    public function setMobileAlias($mobile_alias)
    {
        $this->mobile_alias = $mobile_alias;
        return $this;
    }

    public function getMobileAlias()
    {
        return $this->mobile_alias;
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

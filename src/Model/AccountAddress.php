<?php
namespace UserBase\Server\Model;

class AccountAddress
{
    private $id;
    private $account_name;
    private $addressline1;
    private $addressline2;
    private $postalcode;
    private $city;
    private $country;


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

    public function setAddressline1($addressline1)
    {
        $this->addressline1 = $addressline1;
        return $this;
    }

    public function getAddressline1()
    {
        return $this->addressline1;
    }

    public function setAddressline2($addressline2)
    {
        $this->addressline2 = $addressline2;
        return $this;
    }

    public function getAddressline2()
    {
        return $this->addressline2;
    }

    public function setPostalcode($postalcode)
    {
        $this->postalcode = $postalcode;
        return $this;
    }

    public function getPostalcode()
    {
        return $this->postalcode;
    }

    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    public function getCountry()
    {
        return $this->country;
    }
}

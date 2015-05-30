<?php

namespace UserBase\Server\Model;

class Account
{
    private $user;
    private $app;
    
    public function getApp()
    {
        return $this->app;
    }
    
    public function setApp($app)
    {
        $this->app = $app;
    }
    
    public function getUser()
    {
        return $this->user;
    }
    
    public function setUser($user)
    {
        $this->user = $user;
    }
}

<?php

namespace UserBase\Server\Model;

class Event
{
    private $id;
    private $name;
    private $eventName;
    private $data;
    private $occuredAt;
    private $adminName;
    
    public function getName()
    {
        return $this->name;
    }
    
    public function setName($name)
    {
        $this->name = $name;
    }
    
    public function setEventName($eventName)
    {
        $this->eventName = $eventName;
    }
    
    public function getEventName()
    {
        return $this->eventName;
    }
    
    public function setData($data)
    {
        $this->data = $data;
    }
    
    public function getData()
    {
        return $this->data;
    }
    
    public function setOccuredAt($occuredAt)
    {
        $this->occuredAt = $occuredAt;
    }
    
    public function getOccuredAt()
    {
        return $this->occuredAt;
    }
    
    public function setAdminName($adminName)
    {
        $this->adminName = $adminName;
    }
    
    public function getAdminName()
    {
        return $this->adminName;
    }
}

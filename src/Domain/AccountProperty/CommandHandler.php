<?php

namespace UserBase\Server\Domain\AccountProperty;

use UserBase\Server\Application;

class CommandHandler
{
    protected $dispatcher;
    
    public function __construct(Application $app)
    {
        $this->dispatcher = $app['dispatcher'];
    }
    
    public function subscribe()
    {
        return [
            SetCommand::class,
            UnsetCommand::class
        ];
    }
    
    public function handleSet(SetCommand $command)
    {
        $event = new WasSetEvent($command->getAccountName(), $command->getName(), $command->getValue());
        $this->dispatcher->dispatch(WasSetEvent::class, $event);
    }
    
    public function handleUnset(UnsetCommand $command)
    {
        $event = new WasUnsetEvent(
            $command->getAccountName(),
            $command->getName()
        );
        $this->dispatcher->dispatch(WasUnsetEvent::class, $event);
    }
}

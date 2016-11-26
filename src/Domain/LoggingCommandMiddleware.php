<?php

namespace UserBase\Server\Domain;

use Psr\Log\LoggerInterface;
use League\Tactician\Middleware;

class LoggingCommandMiddleware implements Middleware
{
    protected $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    public function execute($command, callable $next)
    {
        $commandClass = get_class($command);
        $args = [];
        foreach ((array)$command as $key => $value) {
            $key = trim($key, "\0*");
            $args[$key] = $value;
        }
        
        $this->logger->info("START: $commandClass", ['arguments' => $args]);
        try {
            $returnValue = $next($command);
            $this->logger->info("SUCCESS: $commandClass", ['arguments' => $args]);
            return $returnValue;
        } catch (\Exception $e) {
            $this->logger->error("EXCEPTION: $commandClass " . $e->getMessage(), ['arguments' => $args]);
            throw $e; // rethrow
        }
        return null;
    }
}

<?php
namespace Queue\Model\Consumer;


class Notification implements ConsumerInterface
{

    private $payload;

    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    public function execute()
    {
        
    }
    
    private function validatePayload()
    {
        
    }
}

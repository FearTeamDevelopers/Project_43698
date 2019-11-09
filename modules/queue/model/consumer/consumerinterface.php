<?php
namespace Queue\Model\Consumer;

interface ConsumerInterface
{

    public function __construct($payload);

    public function execute();
}

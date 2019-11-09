<?php
namespace Queue\Model\Publisher;

interface PublisherInterface
{

    public function __construct($consumer, $payload, $delay);

    public function enqueue();
}

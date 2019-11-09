<?php
namespace Queue\Model\Publisher;

use Queue\Model\QueueModel;
use THCFrame\Events\Events as Event;

/**
 * 
 */
abstract class PublisherAbstract implements PublisherInterface
{

    private $delay;
    private $consumer;
    private $payload;

    /**
     * 
     * @param string $consumer
     * @param string $payload
     * @param int $delay
     */
    public function __construct($consumer, $payload, $delay)
    {
        $this->payload = $payload;
        $this->consumer = $consumer;
        $this->delay = $delay;
    }

    /**
     * 
     */
    public function enqueue()
    {
        $data = [
            'consumer' => $this->consumer,
            'payload' => $this->payload,
            'runAt' => date('Y-m-d H:i:s'),
            'delay' => $this->delay,
            'attempts' => 0
        ];

        $queue = new QueueModel($data);

        if ($queue->validate()) {
            $queue->save();
        } else {
            Event::fire('admin.log', ['fail', 'Errors:' . json_encode($queue->getErrors())]);
        }
    }
}

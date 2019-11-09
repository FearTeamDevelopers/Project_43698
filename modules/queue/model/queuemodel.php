<?php
namespace Queue\Model;

use Queue\Model\Basic\BasicQueueModel;

class QueueModel extends BasicQueueModel
{

    public const STATUS_READY = 1;
    public const STATUS_RUNNING = 2;
    public const STATUS_ERROR = 3;
    public const STATUS_FINISHED = 4;
    public const RESULT_NONE = 0;
    public const RESULT_OK = 1;
    public const RESULT_ERROR = 2;

    /**
     * @param int $limit
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchToProcess(int $limit = 5)
    {
        return static::all([
                'unix_timestamp(runAt) + attempts*delay*60 <= ?' => 'unix_timestamp(now())',
                'status = ?' => static::STATUS_READY,
                ], ['*'], [], $limit);
    }

    /**
     *
     */
    public function preSave()
    {
        $primary = $this->getPrimaryColumn();
        $raw = $primary['raw'];

        if (empty($this->$raw)) {
            $this->setStatus(self::STATUS_READY);
            $this->setResult(self::RESULT_NONE);
            $this->setResponse('');
            $this->setLastRun(null);
            $this->setCreated(date('Y-m-d H:i:s'));
        }

        $this->setModified(date('Y-m-d H:i:s'));
    }

    public function process()
    {
        $class = '\\Queue\\Model\\Consumer\\' . $this->getConsumer();

        if (class_exists($class, true)) {
            $this->setStatusRunning();

            try {
                $payload = json_decode($this->getPayload(), true);
                
                /** var \Queue\Model\Consumer\ConsumerInterface $object */
                $object = new $class($payload);
                $object->execute();

                $this->setResponse();
                $this->setStatusFinished();
            } catch (Exception $ex) {
                $this->setStatusError();
                $this->setResponse($ex);
            }
        } else {
            $this->setStatusError();
            $this->setResponse(sprintf("Consumer '%s' does not exists", str_replace('\\', '_', $class)));
        }
    }

    public function setStatusRunning()
    {
        $this->setStatus(self::STATUS_RUNNING);
        $this->update();
    }

    public function setStatusFinished()
    {
        $this->setStatus(self::STATUS_FINISHED);
        $this->update();
    }

    public function setStatusError()
    {
        $this->setStatus(self::STATUS_ERROR);
        $this->update();
    }
}

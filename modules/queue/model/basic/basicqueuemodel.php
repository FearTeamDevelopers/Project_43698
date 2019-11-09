<?php
namespace Queue\Model\Basic;

use THCFrame\Model\Model;

/**
 * 
 */
class BasicQueueModel extends Model
{

    /**
     * @column
     * @readwrite
     * @primary
     * @type auto_increment
     * @unsigned
     */
    protected $_id;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 50
     * @validate alpha,max(50)
     * @label consumer
     */
    protected $_consumer;
    
    /**
     * @column
     * @readwrite
     * @type tinyint
     * @length 1
     * @validate max(1)
     * @label status
     */
    protected $_status;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * @length 1
     * @validate max(1)
     * @label result
     */
    protected $_result;

    /**
     * @column
     * @readwrite
     * @type text
     * @validate alphanumeric
     * @label payload
     */
    protected $_payload;

    /**
     * @column
     * @readwrite
     * @type text
     * @validate alphanumeric
     * @label response
     */
    protected $_response;

    /**
     * @column
     * @readwrite
     * @type char
     * @length 19
     * @validate datetime,max(19)
     * @label run at
     */
    protected $_runAt;

    /**
     * @column
     * @readwrite
     * @type char
     * @length 19
     * @validate datetime,max(19)
     * @label last run
     */
    protected $_lastRun;
    
    /**
     * @column
     * @readwrite
     * @type int
     * @length 10
     * @validate numeric,max(10)
     * @label delay
     * @unsigned
     */
    protected $_delay;

    /**
     * @column
     * @readwrite
     * @type int
     * @length 10
     * @validate numeric,max(10)
     * @label attempts
     * @unsigned
     */
    protected $_attempts;
    
    /**
     * @column
     * @readwrite
     * @type char
     * @length 19
     * @validate datetime,max(19)
     */
    protected $_created;

    /**
     * @column
     * @readwrite
     * @type char
     * @length 19
     * @validate datetime,max(19)
     */
    protected $_modified;

    public function getId()
    {
        return $this->_id;
    }

    public function getConsumer()
    {
        return $this->_consumer;
    }

    public function getStatus()
    {
        return $this->_status;
    }

    public function getResult()
    {
        return $this->_result;
    }

    public function getPayload()
    {
        return $this->_payload;
    }

    public function getResponse()
    {
        return $this->_response;
    }

    public function getRunAt()
    {
        return $this->_runAt;
    }

    public function getLastRun()
    {
        return $this->_lastRun;
    }

    public function getDelay()
    {
        return $this->_delay;
    }

    public function getAttempts()
    {
        return $this->_attempts;
    }

    public function getCreated()
    {
        return $this->_created;
    }

    public function getModified()
    {
        return $this->_modified;
    }

    public function setId($id)
    {
        $this->_id = $id;
        return $this;
    }

    public function setConsumer($consumer)
    {
        $this->_consumer = $consumer;
        return $this;
    }

    public function setStatus($status)
    {
        $this->_status = $status;
        return $this;
    }

    public function setResult($result)
    {
        $this->_result = $result;
        return $this;
    }

    public function setPayload($payload)
    {
        $this->_payload = $payload;
        return $this;
    }

    public function setResponse($response)
    {
        $this->_response = $response;
        return $this;
    }

    public function setRunAt($runAt)
    {
        $this->_runAt = $runAt;
        return $this;
    }

    public function setLastRun($lastRun)
    {
        $this->_lastRun = $lastRun;
        return $this;
    }

    public function setDelay($delay)
    {
        $this->_delay = $delay;
        return $this;
    }

    public function setAttempts($attempts)
    {
        $this->_attempts = $attempts;
        return $this;
    }

    public function setCreated($created)
    {
        $this->_created = $created;
        return $this;
    }

    public function setModified($modified)
    {
        $this->_modified = $modified;
        return $this;
    }


}

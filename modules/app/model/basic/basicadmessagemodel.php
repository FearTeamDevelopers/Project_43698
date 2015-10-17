<?php

namespace App\Model\Basic;

use THCFrame\Model\Model;
                
class BasicAdmessageModel extends Model 
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
     * @foreign fk_admessage_advertisement REFERENCES tb_advertisement (id) ON DELETE CASCADE ON UPDATE NO ACTION
     * @type int
     * @length 11
     * @validate required,numeric,max(11)
     * @unsigned
     */
    protected $_adId;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 80
     * @validate required,alphanumeric,max(80)
     * @label od
     */
    protected $_msAuthor;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 60
     * @validate required,email,max(60)
     * @label email
     */
    protected $_msEmail;

    /**
     * @column
     * @readwrite
     * @type text
     * @validate required,html,max(2048)
     * @label zpráva
     * @null
     */
    protected $_message;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * @length 1
     * @validate max(1)
     * @label zaslat kopii emailu
     * @default 0
     */
    protected $_sendEmailCopy;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * @length 1
     * @validate max(1)
     * @default 0
     */
    protected $_messageSent;

    /**
     * @column
     * @readwrite
     * @type char
     * @length 19
     * @validate datetime,max(19)
     * @null
     */
    protected $_created;

    /**
     * @column
     * @readwrite
     * @type char
     * @length 19
     * @validate datetime,max(19)
     * @null
     */
    protected $_modified;

}
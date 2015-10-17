<?php

namespace App\Model\Basic;

use THCFrame\Model\Model;
                
class BasicPartnerModel extends Model 
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
     * @type tinyint
     * @length 1
     * @validate max(1)
     * @default 1
     */
    protected $_active;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 150
     * @validate required,alphanumeric,max(150)
     * @label title
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 300
     * @validate required,url,max(300)
     * @label web
     */
    protected $_web;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 350
     * @validate path,max(350)
     * @label logo
     */
    protected $_logo;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 30
     * @validate alpha,max(30)
     * @label sekce
     */
    protected $_section;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * @length 3
     * @validate numeric,max(3)
     * @label rank
     * @default 1
     */
    protected $_rank;

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
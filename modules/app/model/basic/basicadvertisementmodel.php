<?php

namespace App\Model\Basic;

use THCFrame\Model\Model;
                
class BasicAdvertisementModel extends Model 
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
     * @foreign fk_advertisement_user REFERENCES tb_user (id) ON DELETE SET NULL ON UPDATE NO ACTION
     * @type int
     * @length 11
     * @validate numeric,max(11)
     * @label id autora
     * @unsigned
     * @null
     */
    protected $_userId;

    /**
     * @column
     * @readwrite
     * @foreign fk_advertisement_adsection REFERENCES tb_adsection (id) ON DELETE SET NULL ON UPDATE NO ACTION
     * @type int
     * @length 10
     * @validate numeric,max(10)
     * @label sekce
     * @unsigned
     * @null
     */
    protected $_sectionId;

    /**
     * @column
     * @readwrite
     * @type int
     * @length 10
     * @validate numeric,max(10)
     * @label photo
     * @unsigned
     * @default 0
     */
    protected $_mainPhotoId;

    /**
     * @column
     * @readwrite
     * @index
     * @type tinyint
     * @length 1
     * @validate max(1)
     * @default 1
     */
    protected $_active;

    /**
     * @column
     * @readwrite
     * @unique
     * @type varchar
     * @length 200
     * @validate required,alphanumeric,max(50)
     * @label jedinečný identifikátor
     */
    protected $_uniqueKey;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 15
     * @validate required,alpha,max(15)
     * @label typ
     */
    protected $_adType;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 80
     * @validate alphanumeric,max(80)
     * @label alias autora
     */
    protected $_userAlias;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 150
     * @validate required,alphanumeric,max(150)
     * @label název
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type text
     * @validate required,html
     * @label obsah
     * @null
     */
    protected $_content;

    /**
     * @column
     * @readwrite
     * @type decimal
     * @length 10
     * @validate required,numeric
     * @label cena
     * @default 0.0
     */
    protected $_price;

    /**
     * @column
     * @readwrite
     * @type char
     * @length 10
     * @validate date,max(10)
     * @label zobrazovat do
     */
    protected $_expirationDate;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 350
     * @validate alphanumeric,max(350)
     * @label keywords
     */
    protected $_keywords;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * @length 1
     * @validate max(1)
     * @default 0
     */
    protected $_hasAvailabilityRequest;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 130
     * @validate max(130)
     */
    protected $_availabilityRequestToken;

    /**
     * @column
     * @readwrite
     * @type char
     * @length 19
     * @validate datetime,max(19)
     */
    protected $_availabilityRequestTokenExpiration;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * @length 1
     * @validate max(1)
     * @default 0
     */
    protected $_state;

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
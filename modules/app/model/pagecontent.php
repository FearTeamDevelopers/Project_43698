<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_PageContent
 *
 * @author Tomy
 */
class App_Model_PageContent extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'co';

    /**
     * @column
     * @readwrite
     * @primary
     * @type auto_increment
     */
    protected $_id;

    /**
     * @column
     * @readwrite
     * @type boolean
     * @index
     * 
     * @validate max(3)
     */
    protected $_active;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 150
     * 
     * @validate required, alpha, max(150)
     * @label název
     */
    protected $_pageName;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 200
     * @unique
     * 
     * @validate required, alpha, max(200)
     * @label url key
     */
    protected $_urlKey;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate required, html, max(80000)
     * @label text
     */
    protected $_body;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate html, max(80000)
     * @label text en
     */
    protected $_bodyEn;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate alphanumeric, max(250)
     * @label keywords
     */
    protected $_keywords;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate alphanumeric, max(250)
     * @label meta-název
     */
    protected $_metaTitle;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate alphanumeric, max(500)
     * @label meta-popis
     */
    protected $_metaDescription;

    /**
     * @column
     * @readwrite
     * @type datetime
     */
    protected $_created;

    /**
     * @column
     * @readwrite
     * @type datetime
     */
    protected $_modified;

    /**
     * Method replace specific strings whit their equivalent html tags
     * 
     * @param type $parsedField
     * @return \Model
     */
    private function _parseContentBody($parsedField = 'body')
    {
        preg_match_all('/\(\!(photo|gallery|document)_[0-9a-z]+[a-z_]*\!\)/', $this->$parsedField, $matches);
        $m = array_shift($matches);

        foreach ($m as $match) {
            $match = str_replace(array('(!', '!)'), '', $match);

            if ($match == 'read_more' || $match == 'gallery' || $match == 'document') {
                $float = '';
                list($type, $id) = explode('_', $match);
            } else {
                list($type, $id, $float) = explode('_', $match);
            }

            $body = $this->$parsedField;
            if ($type == 'photo') {
                $photo = App_Model_Photo::first(
                                array(
                            'id = ?' => $id,
                            'active = ?' => true
                                ), array('photoName', 'imgMain', 'imgThumb')
                );

                if ($float == 'left') {
                    $floatClass = 'class="left-10"';
                } elseif ($float == 'right') {
                    $floatClass = 'class="right-10"';
                } else {
                    $floatClass = '';
                }

                $tag = "<a data-lightbox=\"img\" data-title=\"{$photo->photoName}\" "
                        . "href=\"{$photo->imgMain}\" title=\"{$photo->photoName}\">"
                        . "<img src=\"{$photo->imgThumb}\" {$floatClass} height=\"200px\" alt=\"Hastrman\"/></a>";

                $body = str_replace("(!photo_{$id}_{$float}!)", $tag, $body);

                $this->$parsedField = $body;
            }

            if ($type == 'document') {
                $doc = App_Model_Document::first(
                                array(
                            'id = ?' => $id,
                            'active = ?' => true
                                ), array('filename', 'filepath', 'description')
                );

                $tag = "<a href=\"{$doc->filepath}\" target=_blank>{$doc->description}</a>";

                $body = str_replace("(!document_{$id}!)", $tag, $body);
                $this->$parsedField = $body;
            }

            if ($type == 'gallery') {
                $gallery = App_Model_Gallery::first(array('isPublic = ?' => true, 'id = ?' => $id));
                $tag = "<a href=\"/galerie/r/{$gallery->getUrlKey()}\">{$gallery->getTitle()}</a>";
                $body = str_replace("(!gallery_{$id}!)", $tag, $body);
                $this->$parsedField = $body;
            }
            
        }

        return $this;
    }
    
    /**
     * 
     */
    public function preSave()
    {
        $primary = $this->getPrimaryColumn();
        $raw = $primary['raw'];

        if (empty($this->$raw)) {
            $this->setCreated(date('Y-m-d H:i:s'));
            $this->setActive(true);
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

    /**
     * 
     * @param type $urlKey
     */
    public static function fetchByUrlKeyParsed($urlKey)
    {
        $content = self::first(array('urlKey = ?' => $urlKey, 'active = ?' => true));
        
        if($content !== null){
            $co = $content->_parseContentBody('body');
            return $co;
        }else{
            return null;
        }
    }
}

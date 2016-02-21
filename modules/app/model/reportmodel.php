<?php

namespace App\Model;

use App\Model\Basic\BasicReportModel;
use THCFrame\Core\StringMethods;
use THCFrame\Filesystem\FileManager;
use THCFrame\Core\Lang;

/**
 *
 */
class ReportModel extends BasicReportModel
{

    const STATE_WAITING = 0;
    const STATE_APPROVED = 1;
    const STATE_REJECTED = 2;

    /**
     * @var type
     */
    private static $_statesConv = array(
        self::STATE_WAITING => 'Čeká na shválení',
        self::STATE_APPROVED => 'Schváleno',
        self::STATE_REJECTED => 'Zamítnuto',
    );

    /**
     * @readwrite
     */
    protected $_alias = 'rp';

    /**
     * @readwrite
     */
    protected $_fbLikeUrl;

    /**
     * Check whether action unique identifier already exist or not.
     *
     * @param string $key
     *
     * @return bool
     */
    private static function checkUrlKey($key)
    {
        $status = self::first(array('urlKey = ?' => $key));

        if (null === $status) {
            return true;
        } else {
            return false;
        }
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

        $shortText = preg_replace('/https:/i', 'http:', $this->getShortBody());
        $text = preg_replace('/https:/i', 'http:', $this->getBody());
        $this->setShortBody($shortText);
        $this->setBody($text);
        $this->setModified(date('Y-m-d H:i:s'));
    }

    /**
     * Delete report and image
     *
     * @return type
     */
    public function delete()
    {
        $imgMain = $this->getUnlinkPath();
        $imgThumb = $this->getUnlinkThumbPath();

        $state = parent::delete();

        if ($state != -1) {
            @unlink($imgMain);
            @unlink($imgThumb);
        }

        return $state;
    }

    /**
     * @return array
     */
    public static function fetchAll()
    {
        $query = self::getQuery(array('rp.*'))
                ->join('tb_user', 'rp.userId = us.id', 'us', array('us.firstname', 'us.lastname'));

        return self::initialize($query);
    }

    /**
     * Called from admin module.
     *
     * @return array
     */
    public static function fetchWithLimit($limit = 10)
    {
        $query = self::getQuery(array('rp.*'))
                ->join('tb_user', 'rp.userId = us.id', 'us', array('us.firstname', 'us.lastname'))
                ->order('rp.created', 'desc')
                ->limit((int) $limit);

        return self::initialize($query);
    }

    /**
     * Called from app module.
     *
     * @param type $limit
     *
     * @return type
     */
    public static function fetchActiveWithLimit($limit = 10, $page = 1)
    {
        $reports = self::all(array('active = ?' => true, 'approved = ?' => 1, 'archive = ?' => false), array('urlKey', 'userAlias', 'title', 'shortBody', 'created',
                    'imgMain', 'imgThumb', 'photoName',), array('rank' => 'desc', 'created' => 'desc'), $limit, $page
        );

        return $reports;
    }

    /**
     * Called from app module.
     *
     * @param type $limit
     *
     * @return type
     */
    public static function fetchArchivatedWithLimit($limit = 10, $page = 1)
    {
        $reports = self::all(array('active = ?' => true, 'approved = ?' => 1, 'archive = ?' => true), array('urlKey', 'userAlias', 'title', 'shortBody', 'created',
                    'imgMain', 'imgThumb', 'photoName',), array('rank' => 'desc', 'created' => 'desc'), $limit, $page
        );

        return $reports;
    }

    /**
     * Called from app module.
     *
     * @param type $urlKey
     *
     * @return type
     */
    public static function fetchByUrlKey($urlKey)
    {
        return self::first(array('active = ?' => true, 'approved' => 1, 'urlKey = ?' => $urlKey));
    }

    /**
     * @return type
     */
    public function getUnlinkPath($type = true)
    {
        if ($type) {
            if (file_exists(APP_PATH . $this->_imgMain)) {
                return APP_PATH . $this->_imgMain;
            } elseif (file_exists('.' . $this->_imgMain)) {
                return '.' . $this->_imgMain;
            } elseif (file_exists('./' . $this->_imgMain)) {
                return './' . $this->_imgMain;
            }
        } else {
            return $this->_imgMain;
        }
    }

    /**
     * @return type
     */
    public function getUnlinkThumbPath($type = true)
    {
        if ($type) {
            if (file_exists(APP_PATH . $this->_imgThumb)) {
                return APP_PATH . $this->_imgThumb;
            } elseif (file_exists('.' . $this->_imgThumb)) {
                return '.' . $this->_imgThumb;
            } elseif (file_exists('./' . $this->_imgThumb)) {
                return './' . $this->_imgThumb;
            }
        } else {
            return $this->_imgThumb;
        }
    }

    /**
     * Return action states.
     *
     * @return array
     */
    public static function getStates()
    {
        return self::$_statesConv;
    }

    public static function createFromPost(\THCFrame\Bag\BagInterface $post,
            array $options = array())
    {
        $urlKey = $urlKeyCh = StringMethods::createUrlKey($post->get('title'));
        $errors = array();
        $user = $options['user'];
        $config = $options['config'];

        for ($i = 1; $i <= 100; $i+=1) {
            if (self::checkUrlKey($urlKeyCh)) {
                break;
            } else {
                $urlKeyCh = $urlKey . '-' . $i;
            }

            if ($i == 100) {
                $errors['title'] = array(Lang::get('ARTICLE_UNIQUE_ID'));
                break;
            }
        }

        $imgMain = $imgThumb = '';

        if (!empty($post->get('croppedimage'))) {
            $fileManager = new FileManager(array(
                'thumbWidth' => $config->thumb_width,
                'thumbHeight' => $config->thumb_height,
                'thumbResizeBy' => $config->thumb_resizeby,
                'maxImageWidth' => $config->photo_maxwidth,
                'maxImageHeight' => $config->photo_maxheight,
            ));

            $fileErrors = $fileManager->uploadBase64Image($post->get('croppedimage'), $urlKeyCh, 'report', time() . '_')
                    ->getUploadErrors();
            $files = $fileManager->getUploadedFiles();

            if (!empty($fileErrors)) {
                $errors['croppedimage'] = $fileErrors;
            }

            if (!empty($files)) {
                foreach ($files as $i => $file) {
                    if ($file instanceof \THCFrame\Filesystem\Image) {
                        $imgMain = trim($file->getFilename(), '.');
                        $imgThumb = trim($file->getThumbname(), '.');
                        break;
                    }
                }
            }
        } else {
            $errors['croppedimage'] = array(Lang::get('FIELD_REQUIRED'));
        }

        $shortText = str_replace(
                array('(!read_more_link!)', '(!read_more_title!)'), array('/reportaz/r/' . $urlKeyCh, '[Celý článek]'), $post->get('shorttext')
        );

        $keywords = strtolower(StringMethods::removeDiacriticalMarks($post->get('keywords')));

        $report = new static(array(
            'title' => $post->get('title'),
            'userId' => $user()->getId(),
            'userAlias' => $user()->getWholeName(),
            'urlKey' => $urlKeyCh,
            'approved' => $config()->report_autopublish,
            'archive' => 0,
            'shortBody' => $shortText,
            'body' => $post->get('text'),
            'rank' => $post->get('rank', 1),
            'keywords' => $keywords,
            'metaTitle' => $post->get('metatitle', $post->get('title')),
            'metaDescription' => strip_tags($post->get('metadescription', $shortText)),
            'metaImage' => $imgMain,
            'photoName' => $urlKey,
            'imgMain' => $imgMain,
            'imgThumb' => $imgThumb,
        ));

        return array($report, $errors);
    }

    public static function editFromPost(\THCFrame\Bag\BagInterface $post,
            \App\Model\ReportModel $report, array $options = array())
    {
        $urlKey = $urlKeyCh = StringMethods::createUrlKey($post->get('title'));
        $errors = array();
        $user = $options['user'];
        $config = $options['config'];

        if ($report->urlKey != $urlKey && !self::checkUrlKey($urlKey)) {
            for ($i = 1; $i <= 100; $i+=1) {
                if (self::checkUrlKey($urlKeyCh)) {
                    break;
                } else {
                    $urlKeyCh = $urlKey . '-' . $i;
                }

                if ($i == 100) {
                    $errors['title'] = array(Lang::get('ARTICLE_TITLE_IS_USED'));
                    break;
                }
            }
        }

        $fileManager = new FileManager(array(
            'thumbWidth' => $config->thumb_width,
            'thumbHeight' => $config->thumb_height,
            'thumbResizeBy' => $config->thumb_resizeby,
            'maxImageWidth' => $config->photo_maxwidth,
            'maxImageHeight' => $config->photo_maxheight,
        ));

        $imgMain = $imgThumb = '';
        if ($report->imgMain == '' && !empty($post->get('croppedimage'))) {
            $fileErrors = $fileManager->uploadBase64Image($post->get('croppedimage'), $urlKeyCh, 'report', time() . '_')
                    ->getUploadErrors();
            $files = $fileManager->getUploadedFiles();

            if (!empty($fileErrors)) {
                $errors['croppedimage'] = $fileErrors;
            }

            if (!empty($files)) {
                foreach ($files as $i => $file) {
                    if ($file instanceof \THCFrame\Filesystem\Image) {
                        $imgMain = trim($file->getFilename(), '.');
                        $imgThumb = trim($file->getThumbname(), '.');
                        break;
                    }
                }
            } else {
                $errors['croppedimage'] = array(Lang::get('FIELD_REQUIRED'));
            }
        } else {
            $imgMain = $report->imgMain;
            $imgThumb = $report->imgThumb;
        }

        if (null === $report->userId) {
            $report->userId = $user->getId();
            $report->userAlias = $user->getWholeName();
        }

        $shortText = str_replace(
                array('(!read_more_link!)', '(!read_more_title!)'), array('/reportaz/r/' . $urlKeyCh, '[Celý článek]'), $post->get('shorttext')
        );

        $keywords = strtolower(StringMethods::removeDiacriticalMarks($post->get('keywords')));

        if (!$this->isAdmin()) {
            $report->approved = $config->report_autopublish;
        } else {
            $report->approved = $post->get('approve');
        }

        $report->title = $post->get('title');
        $report->urlKey = $urlKeyCh;
        $report->body = $post->get('text');
        $report->shortBody = $shortText;
        $report->rank = $post->get('rank', 1);
        $report->active = $post->get('active');
        $report->archive = $post->get('archive');
        $report->keywords = $keywords;
        $report->metaTitle = $post->get('metatitle', $post->get('title'));
        $report->metaDescription = strip_tags($post->get('metadescription', $shortText));
        $report->metaImage = $imgMain;
        $report->photoName = $urlKey;
        $report->imgMain = $imgMain;
        $report->imgThumb = $imgThumb;

        return array($report, $errors);
    }

}

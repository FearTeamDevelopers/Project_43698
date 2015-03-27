<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use THCFrame\Core\StringMethods;

/**
 * 
 */
class DevController extends Controller
{

    /**
     * Fill database tables tb_action, tb_news and tb_report with testing data
     * For database filling use these urls:
     *      /admin/system/filldatabase/1    - for tb_news
     *      /admin/system/filldatabase/2    - for tb_action
     *      /admin/system/filldatabase/3    - for tb_report
     * 
     * @before _secured, _superadmin
     */
    public function fillDatabase($type)
    {
        if (ENV !== 'dev') {
            exit;
        }

        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        ini_set('max_execution_time', 1800);
        ini_set('memory_limit', '256M');

        $ROW_COUNT = 50;

        $content = \App\Model\PageContentModel::first(array('urlKey = ?' => 'kurzy-sdi'), array('body'));

        $SHORT_TEXT = 'Vedle používání zdravého rozumu, dostatečné kvalifikace i praxe je kvalitní a spolehlivá 
            potápěčská technika jednou z podmínek dosažení nejvyšší míry bezpečnosti vašich ponorů. 
            Kupujte jen takovou výstroj, která tato kriteria splňuje! Pamatujte, že cena je až 
            druhotným ukazatelem ... nebo váš život stojí za pár ušetřených stokorun?';

        $LARGE_TEXT = str_replace('h1', 'h2', $content->getBody());
        unset($content);

        $META_DESC = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse efficitur viverra libero, at dapibus sapien placerat a. '
                . 'In efficitur tortor in nulla auctor tristique. Pellentesque non nisi mollis, tincidunt purus rutrum, ornare sem.';

        if ((int) $type == 1) {
            for ($i = 0; $i < $ROW_COUNT; $i++) {
                $news = new \App\Model\NewsModel(array(
                    'title' => 'News-' . $i . '-' . time(),
                    'userId' => 1,
                    'userAlias' => 'System',
                    'urlKey' => 'news-' . $i . '-' . time(),
                    'approved' => 1,
                    'archive' => 0,
                    'shortBody' => $SHORT_TEXT,
                    'body' => $LARGE_TEXT,
                    'rank' => 1,
                    'keywords' => 'news',
                    'metaTitle' => 'News-' . $i . '-' . time(),
                    'metaDescription' => $META_DESC
                ));

                $news->save();
                unset($news);
            }
            self::redirect('/admin/system/');
        }

        if ((int) $type == 2) {
            for ($i = 0; $i < $ROW_COUNT; $i++) {
                $date = new \DateTime();
                $date->add(new \DateInterval('P' . (int) $i . 'D'));
                $startDate = $date->format('Y-m-d');

                $action = new \App\Model\ActionModel(array(
                    'title' => 'Action-' . $i . '-' . time(),
                    'userId' => 1,
                    'userAlias' => 'System',
                    'urlKey' => 'action-' . $i . '-' . time(),
                    'approved' => 1,
                    'archive' => 0,
                    'shortBody' => $SHORT_TEXT,
                    'body' => $LARGE_TEXT,
                    'rank' => 1,
                    'startDate' => $startDate,
                    'endDate' => $startDate,
                    'startTime' => '',
                    'endTime' => '',
                    'keywords' => 'action',
                    'metaTitle' => 'Action-' . $i . '-' . time(),
                    'metaDescription' => $META_DESC
                ));

                $action->save();
                unset($action);
            }
            self::redirect('/admin/system/');
        }

        if ((int) $type == 3) {
            for ($i = 0; $i < $ROW_COUNT; $i++) {
                $report = new \App\Model\ReportModel(array(
                    'title' => 'Report-' . $i . '-' . time(),
                    'userId' => 1,
                    'userAlias' => 'System',
                    'urlKey' => 'report-' . $i . '-' . time(),
                    'approved' => 1,
                    'archive' => 0,
                    'shortBody' => $SHORT_TEXT,
                    'body' => $LARGE_TEXT,
                    'rank' => 1,
                    'keywords' => 'report',
                    'metaTitle' => 'Report-' . $i . '-' . time(),
                    'metaDescription' => $META_DESC,
                    'metaImage' => '',
                    'photoName' => '',
                    'imgMain' => '',
                    'imgThumb' => ''
                ));

                $report->save();
                unset($report);
            }
            self::redirect('/admin/system/');
        }
    }

    /**
     * 
     * @param type $string
     * @return type
     */
    protected function _createUrlKey($string)
    {
        $neutralChars = array('.', ',', '_', '(', ')', '[', ']', '|', ' ');
        $preCleaned = StringMethods::fastClean($string, $neutralChars, '-');
        $cleaned = StringMethods::fastClean($preCleaned);
        $return = mb_ereg_replace('[\-]+', '-', trim(trim($cleaned), '-'));
        return strtolower($return);
    }

    protected function _checkUrlKey($key, $keys, $type)
    {
        if (in_array($key, $keys)) {
            return true;
        } else {
            if ($type == 'action') {
                $action = \App\Model\ActionModel::first(array('urlKey = ?' => $key));

                if (null !== $action) {
                    return 5;
                }
            } elseif ($type == 'report') {
                $report = \App\Model\ReportModel::first(array('urlKey = ?' => $key));

                if (null !== $report) {
                    return 5;
                }
            } elseif ($type == 'news') {
                $news = \App\Model\NewsModel::first(array('urlKey = ?' => $key));

                if (null !== $news) {
                    return 5;
                }
            }
            return false;
        }
    }

    /**
     * @before _secured, _superadmin
     */
    public function migrateOldData()
    {
        if (ENV !== 'dev') {
            exit;
        }

        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $oldDb = new \THCFrame\Database\Database();
        $db = $oldDb->initializeDirectly(array(
            'type' => 'mysql',
            'host' => 'localhost',
            'username' => 'root',
            'password' => '',
            'schema' => 'hastrman_old'
        ));

        $insertReportSql = "INSERT INTO `tb_report` VALUES (default, 2, 1, 1, 1, '%s', 'Bohumír Kuhn', '%s', '%s', '%s', default, default, default, default, default, '%s', '%s', default, '%s', '%s');";
        $insertActionSql = "INSERT INTO `tb_action` VALUES (default, 2, 1, 1, 1, '%s', 'Bohumír Kuhn', '%s', '%s', '%s', default, default, default, default, default, default, '%s', '%s', '%s', '%s');";
        $insertNewsSql = "INSERT INTO `tb_news` VALUES (default, 2, 1, 1, 1, '%s', 'Bohumír Kuhn', '%s', '%s', '%s', default, default, '%s', '%s', '%s', '%s');";

        $reports = $db->execute('SELECT * FROM `jos_content` where catid IN (37,46) and state = 1');
        $actions = $db->execute('SELECT * FROM `jos_content` where catid IN (41,42) and state = 1');
        $news = $db->execute('SELECT * FROM `jos_content` where catid IN (36) and state = 1');

        $patterns = array('/https:/i', '/images\/stories\//i', '/\'/');
        $replaces = array('http:', '/public/uploads/images/stories/', "\'");
        $contentCount = 0;

        if (!empty($reports)) {
            $urlKeys = array();
            foreach ($reports as $obj) {
                $shortText = trim(preg_replace($patterns, $replaces, $obj['introtext']));
                $shortText = trim(substr(strip_tags($shortText, '<br><br/>'), 0, 1500));
                $text = trim(preg_replace($patterns, $replaces, $obj['introtext']));
                $metaDesc = trim(substr(strip_tags($shortText), 0, 1000)) . '...';
                $title = trim(StringMethods::fastClean($obj['title'], array(), '', true));
                $urlKey = $this->_createUrlKey($title);

                $urlKeyCheck = $this->_checkUrlKey($urlKey, $urlKeys, 'report');
                if ($urlKeyCheck === true) {
                    for ($i = 1; $i <= 50; $i++) {
                        if (!$this->_checkUrlKey($urlKey, $urlKeys, 'report')) {
                            break;
                        } else {
                            $urlKey = $urlKey . '-' . $i;
                        }
                    }
                } elseif ($urlKeyCheck === 5) {
                    continue;
                }

                $urlKeys[] = $urlKey;

                $sql = sprintf($insertReportSql, $urlKey, $title, $shortText, $text, $title, $metaDesc, $obj['created'], $obj['modified']);
                \THCFrame\Core\Core::getLogger()->log($sql, 'system', FILE_APPEND, false);
                $contentCount++;
            }
        }

        if (!empty($actions)) {
            $urlKeys = array();
            foreach ($actions as $obj) {
                $shortText = trim(preg_replace($patterns, $replaces, $obj['introtext']));
                $shortText = trim(substr(strip_tags($shortText, '<br><br/>'), 0, 1500));
                $text = trim(preg_replace($patterns, $replaces, $obj['introtext']));
                $metaDesc = trim(substr(strip_tags($shortText), 0, 1000)) . '...';
                $title = trim(StringMethods::fastClean($obj['title'], array(), '', true));
                $urlKey = $this->_createUrlKey($title);

                $urlKeyCheck = $this->_checkUrlKey($urlKey, $urlKeys, 'action');
                if ($urlKeyCheck === true) {
                    for ($i = 1; $i <= 50; $i++) {
                        if (!$this->_checkUrlKey($urlKey, $urlKeys, 'action')) {
                            break;
                        } else {
                            $urlKey = $urlKey . '-' . $i;
                        }
                    }
                } elseif ($urlKeyCheck === 5) {
                    continue;
                }

                $urlKeys[] = $urlKey;

                $sql = sprintf($insertActionSql, $urlKey, $title, $shortText, $text, $title, $metaDesc, $obj['created'], $obj['modified']);
                \THCFrame\Core\Core::getLogger()->log($sql, 'system', FILE_APPEND, false);
                $contentCount++;
            }
        }

        if (!empty($news)) {
            $urlKeys = array();
            foreach ($news as $obj) {
                $shortText = trim(preg_replace($patterns, $replaces, $obj['introtext']));
                $shortText = trim(substr(strip_tags($shortText, '<br><br/>'), 0, 1500));
                $text = trim(preg_replace($patterns, $replaces, $obj['introtext']));
                $metaDesc = trim(substr(strip_tags($shortText), 0, 1000)) . '...';
                $title = trim(StringMethods::fastClean($obj['title'], array(), '', true));
                $urlKey = $this->_createUrlKey($title);

                $urlKeyCheck = $this->_checkUrlKey($urlKey, $urlKeys, 'news');
                if ($urlKeyCheck === true) {
                    for ($i = 1; $i <= 50; $i++) {
                        if (!$this->_checkUrlKey($urlKey, $urlKeys, 'news')) {
                            break;
                        } else {
                            $urlKey = $urlKey . '-' . $i;
                        }
                    }
                } elseif ($urlKeyCheck === 5) {
                    continue;
                }

                $urlKeys[] = $urlKey;

                $sql = sprintf($insertNewsSql, $urlKey, $title, $shortText, $text, $title, $metaDesc, $obj['created'], $obj['modified']);
                \THCFrame\Core\Core::getLogger()->log($sql, 'system', FILE_APPEND, false);
                $contentCount++;
            }
        }

        $db->disconnect();

        print('<pre>' . print_r($contentCount, true) . '</pre>');
        die;
    }

    /**
     * @before _secured, _superadmin
     */
    public function testSendEmail()
    {
        $this->_willRenderActionView = false;
        $this->_willRenderLayoutView = false;
        
        $this->sendEmail('Test message', 'Hastrman test email');
        print('<pre>'.print_r('Send', true).'</pre>');die;
    }
    
    
}

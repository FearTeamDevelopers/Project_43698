<?php

namespace Search\Controller;

use Search\Etc\Controller;
use THCFrame\Core\StringMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;

/**
 * 
 */
class IndexController extends Controller
{

    const RANKER_TERMWEIGHT = 100;
    const RANKER_TITLEWEIGHT = 1000;
    const RANKER_URLWEIGHT = 6000;
    const RANKER_KEYWORDWEIGHT = 2000;

    /**
     * @read
     * @var type 
     */
    private $_textSource = array(
        'tb_action' => array(
            'model' => 'App\Model\ActionModel',
            'modelLabel' => 'Akce',
            'where' => array('active = 1', 'approved = 1', 'archive = 0'),
            'path' => '/akce/r/',
            'identifier' => 'urlKey',
            'columns' => array('urlKey', 'keywords', 'metaDescription', 'title', 'created'),
            'textColumns' => array('keywords', 'metaDescription'),
            'previewColumns' => array('metaDescription', 'created')),
        'tb_report' => array(
            'model' => 'App\Model\ReportModel',
            'modelLabel' => 'Reportáže',
            'where' => array('active = 1', 'approved = 1', 'archive = 0'),
            'path' => '/reportaze/r/',
            'identifier' => 'urlKey',
            'columns' => array('urlKey', 'keywords', 'metaDescription', 'title', 'created'),
            'textColumns' => array('keywords', 'metaDescription'),
            'previewColumns' => array('metaDescription', 'created')),
        'tb_pagecontent' => array(
            'model' => 'App\Model\PageContentModel',
            'modelLabel' => 'Obsah webu - statické stránky',
            'where' => array('active = 1'),
            'path' => '/page/',
            'identifier' => 'urlKey',
            'columns' => array('urlKey', 'metaDescription', 'title', 'keywords', 'created'),
            'textColumns' => array('keywords', 'metaDescription'),
            'previewColumns' => array('metaDescription', 'created')),
        'tb_news' => array(
            'model' => 'App\Model\NewsModel',
            'modelLabel' => 'Novinky',
            'where' => array('active = 1', 'approved = 1', 'archive = 0'),
            'path' => '/novinky/r/',
            'identifier' => 'urlKey',
            'columns' => array('urlKey', 'keywords', 'metaDescription', 'title', 'created'),
            'textColumns' => array('keywords', 'metaDescription'),
            'previewColumns' => array('metaDescription', 'created')),
        'tb_advertisement' => array(
            'model' => 'App\Model\AdvertisementModel',
            'modelLabel' => 'Bazar',
            'where' => array('active = 1'),
            'path' => '/bazar/r/',
            'identifier' => 'uniqueKey',
            'columns' => array('uniqueKey', 'keywords', 'content', 'title', 'created'),
            'textColumns' => array('keywords', 'content'),
            'previewColumns' => array('', 'created'))
    );

    /**
     * Get weight for specific term
     * 
     * @param string    $term
     * @param int       $occurence
     * @param string    $title
     * @param string    $url
     * @return int
     */
    private function _getWeight($term, $occurence, $title, $keywords, $url)
    {
        $cleanTitle = $this->_cleanString($title);

        preg_match_all('/ ' . $term . ' /i', $url, $urlcount);
        preg_match_all('/' . $term . '/i', $keywords, $keywordscount);
        preg_match_all('/ ' . $term . ' /i', $cleanTitle, $titlecount);

        $words_in_url = count($urlcount[0]);
        $words_in_keywords = count($keywordscount[0]);
        $words_in_title = count($titlecount[0]);
        $words_in_meta = $occurence;

        $weight = ( $words_in_meta * self::RANKER_TERMWEIGHT 
                + $words_in_title * self::RANKER_TITLEWEIGHT 
                + $words_in_url * self::RANKER_URLWEIGHT 
                + $words_in_keywords * self::RANKER_KEYWORDWEIGHT
                );

        $newweight = intval($weight);

        return $newweight;
    }

    /**
     * Clean string. Cleaned string contains only [a-z0-9\s]
     * 
     * @param string     $str
     * @param null|array $stopWordsCs
     * @param null|array $stopWordsEn
     * @return string
     */
    private function _cleanString($str, $stopWordsCs = null, $stopWordsEn = null)
    {
        $cleanStr = StringMethods::removeDiacriticalMarks($str);
        $cleanStr = strtolower(strip_tags(trim($cleanStr)));
        $cleanStr = preg_replace('/[^a-z0-9\s]+/', ' ', $cleanStr);

        if (null !== $stopWordsCs && null !== $stopWordsEn) {
            $cleanStr = preg_replace('/\b(' . $stopWordsCs . ')\b/', ' ', $cleanStr);
            $cleanStr = preg_replace('/\b(' . $stopWordsEn . ')\b/', ' ', $cleanStr);
        }

        $cleanStr2 = preg_replace('/\s+/', ' ', $cleanStr);

        unset($cleanStr);
        return $cleanStr2;
    }

    /**
     * Get search index log and controll panel for search module
     * 
     * @before _secured, _admin
     */
    public function index()
    {
        $view = $this->getActionView();
        $searchIndexLog = \Search\Model\SearchIndexLogModel::all(array(), array('*'), array('created' => 'desc'), 50);

        $view->set('tables', $this->_textSource)
                ->set('indexLog', $searchIndexLog);
    }

    /**
     * Completly delete and create new search index
     * 
     * @before _cron
     */
    public function buildIndex()
    {
        Event::fire('search.log', array('success', 'Building search index'));
        //ini_set('max_execution_time', 1800);

        $stopWordsCs = implode('|', $this->stopwords_cs);
        $stopWordsEn = implode('|', $this->stopwords_en);

        $database = Registry::get('database')->get('main');
        $insertSql = "INSERT INTO tb_searchindex VALUES (default, ?, ?, ?, ?, ?, ?, ?, ?, now(), default)";
        $insertSqlLog = "INSERT INTO tb_searchindexlog VALUES (default, ?, ?, 'cron', 0, ?, now(), default)";
        $prepareIdSql = "ALTER TABLE tb_searchindex auto_increment = 1";
        $truncateSql = "TRUNCATE tb_searchindex";

        $starttime = microtime(true);

        $database->execute($truncateSql);
        $database->execute($prepareIdSql);
        
        foreach ($this->_textSource as $table => $variables) {
            $sql = "SELECT " . implode(', ', $variables['columns'])
                    . " FROM " . $table
                    . " WHERE " . implode(' AND ', $variables['where']);

            $articles = $database->execute($sql);
            $wordsCount = 0;

            if (null !== $articles) {
                foreach ($articles as $article) {
                    $superText = '';

                    foreach ($variables['textColumns'] as $column) {
                        $superText .= $article[$column].' ';
                    }

                    $cleanStr = $this->_cleanString($superText, $stopWordsCs, $stopWordsEn);

                    unset($superText);

                    $words = array_count_values(str_word_count($cleanStr, 1));
                    arsort($words);

                    $path = $variables['path'] . $article[$variables['identifier']];
                    $title = $article['title'];
                    $rowDesc = $article[$variables['previewColumns'][0]];
                    $rowCreated = $article[$variables['previewColumns'][1]];

                    foreach ($words as $word => $occ) {
                        if (strlen($word) < 3) {
                            continue;
                        }
                        $wordsCount++;
                        $weight = $this->_getWeight($word, $occ, $title, $article['keywords'], $path);

                        $database->execute($insertSql, $variables['model'], $word, $path, $title, $rowDesc, $rowCreated, $occ, $weight);
                    }

                    unset($words);
                    unset($article);
                    unset($path);
                    unset($title);
                }
            } else {
                continue;
            }

            $database->execute($insertSqlLog, $variables['model'], $table, $wordsCount);

            unset($table);
            unset($wordsCount);
        }

        $time = round(microtime(true) - $starttime, 2);
        Event::fire('search.log', array('success', 'Search index built in ' . $time . ' sec'));
        
        //ini_set('max_execution_time', 30);
    }

    /**
     * Manualy build index for specific table
     * 
     * @before _secured, _admin
     * @param string    $table      table name
     */
    public function updateIndex($table)
    {
        $view = $this->getActionView();

        if (!array_key_exists($table, $this->_textSource)) {
            $view->errorMessage('This source does not exists or content cannot be indexed');
            self::redirect('/search/');
        }

        Event::fire('search.log', array('success', sprintf('Building search index for table %s', $table)));
        $userName = $this->getUser()->getWholeName();

        $stopWordsCs = implode('|', $this->stopwords_cs);
        $stopWordsEn = implode('|', $this->stopwords_en);

        $database = Registry::get('database')->get('main');
        
        $insertSql = "INSERT INTO tb_searchindex VALUES (default, ?, ?, ?, ?, ?, ?, ?, ?, now(), default)";
        $insertSqlLog = "INSERT INTO tb_searchindexlog VALUES (default, ?, ?, ?, 1, ?, now(), default)";
        $deleteSql = "DELETE FROM tb_searchindex WHERE sourceModel=?";

        $starttime = microtime(true);
        $variables = $this->_textSource[$table];

        $selectSql = "SELECT " . implode(', ', $variables['columns'])
                . " FROM " . $table
                . " WHERE " . implode(' AND ', $variables['where']);

        $database->execute($deleteSql, $variables['model']);
        $articles = $database->execute($selectSql);
        $wordsCount = 0;

        if (null !== $articles) {
            foreach ($articles as $article) {
                $superText = '';

                foreach ($variables['textColumns'] as $column) {
                    $superText .= $article[$column].' ';
                }

                $cleanStr = $this->_cleanString($superText, $stopWordsCs, $stopWordsEn);

                unset($superText);

                $words = array_count_values(str_word_count($cleanStr, 1));
                arsort($words);

                $path = $variables['path'] . $article[$variables['identifier']];
                $title = $article['title'];
                $rowDesc = $article[$variables['previewColumns'][0]];
                $rowCreated = $article[$variables['previewColumns'][1]];

                foreach ($words as $word => $occ) {
                    if (strlen($word) < 3) {
                        continue;
                    }
                    $wordsCount++;

                    $weight = $this->_getWeight($word, $occ, $title, $article['keywords'], $path);

                    $database->execute($insertSql, $variables['model'], $word, $path, $title, $rowDesc, $rowCreated, $occ, $weight);
                }

                unset($words);
                unset($article);
                unset($article);
                unset($article);
            }

            $database->execute($insertSqlLog, $variables['model'], $table, $userName, $wordsCount);

            unset($wordsCount);
        } else {
            return;
        }

        $time = round(microtime(true) - $starttime, 2);
        Event::fire('search.log', array('success', sprintf('Search index for %s built in %s sec', $table, $time)));

        $view->successMessage(sprintf('Search index for %s has been successfully built', $this->_textSource[$table]['model']));
        self::redirect('/search/');
    }

}

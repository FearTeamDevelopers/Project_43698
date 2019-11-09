<?php

namespace Search\Model\Sources;

use THCFrame\Core\StringMethods;
use THCFrame\Registry\Registry;
use THCFrame\Events\Events as Event;
use Search\Model\IndexableInterface;

/**
 * Description of abstractsource
 *
 * @author Tomy
 */
abstract class AbstractSource implements SourceInterface
{

    public CONST INSERT_SQL = 'INSERT INTO tb_searchindex VALUES (default, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now(), default)';
    public CONST INSERT_LOG_SQL = 'INSERT INTO tb_searchindexlog VALUES (default, ?, ?, ?, 0, ?, now(), default)';
    public CONST DELETE_SQL = 'DELETE FROM tb_searchindex WHERE sourceModel=?';
    public CONST PREPARE_ID_SQL = 'ALTER TABLE tb_searchindex auto_increment = 1';
    public CONST TRUNCATE_SQL = 'TRUNCATE tb_searchindex';
    public const RANKER_TERMWEIGHT = 100;
    public const RANKER_TITLEWEIGHT = 1000;
    public const RANKER_URLWEIGHT = 6000;
    public const RANKER_KEYWORDWEIGHT = 2000;

    protected $dbConnectionTimer;
    protected $dbConnMain;
    protected $dbConnSearch;

    /**
     * @var string
     */
    protected $model;
    protected $pathPrefix;
    protected $table;
    protected $alias;

    /**
     *
     */
    public function __construct()
    {
        $this->dbConnSearch = Registry::get('database')->get('search');
        $this->dbConnMain = Registry::get('database')->get('main');
    }

    /**
     * Get weight for specific term.
     *
     * @param string $term
     * @param int $occurence
     * @param string $title
     * @param $keywords
     * @param string $url
     *
     * @return int
     */
    protected function calculateWeight($term, $occurence, $title, $keywords, $url)
    {
        $cleanTitle = StringMethods::cleanString($title);

        preg_match_all('/' . $term . '/i', $url, $urlcount);
        preg_match_all('/' . $term . '/i', $keywords, $keywordscount);
        preg_match_all('/' . $term . '/i', $cleanTitle, $titlecount);

        $words_in_url = count($urlcount[0]);
        $words_in_keywords = count($keywordscount[0]);
        $words_in_title = count($titlecount[0]);
        $words_in_meta = $occurence;

        $weight = ($words_in_meta * self::RANKER_TERMWEIGHT +
                $words_in_title * self::RANKER_TITLEWEIGHT +
                $words_in_url * self::RANKER_URLWEIGHT +
                $words_in_keywords * self::RANKER_KEYWORDWEIGHT
                );

        return intval($weight);
    }

    /**
     * Reconnect to the database.
     */
    protected function resertConnections()
    {
        if ($this->dbConnectionTimer + 26 < microtime(true)) {
            $config = Registry::get('configuration');
            Registry::get('database')->disconnectAll();

            $database = new \THCFrame\Database\Database();
            $connectors = $database->initialize($config);
            Registry::set('database', $connectors);

            $this->dbConnectionTimer = microtime(true);
            $this->dbConnSearch = Registry::get('database')->get('search');
            $this->dbConnMain = Registry::get('database')->get('main');

            unset($config, $database, $connectors);
        }
    }

    /**
     *
     * @param \Exception $ex
     * @param bool $runByUser
     */
    protected function errorNotification(\Exception $ex, $runByUser = false)
    {
        $this->resertConnections();
        $body = 'Error while building index: ' . $ex->getMessage();

        if ($runByUser === false) {
            $mailer = new \THCFrame\Mailer\Mailer([
                'body' => $body,
                'subject' => 'ERROR: Search buildIndex',
            ]);

            $mailer->setFrom('cron@hastrman.cz')
                    ->send();
        }

        Event::fire('search.log', ['fail', $body]);
    }

    /**
     *
     * @param IndexableInterface $article
     * @return int
     */
    protected function processArticle(IndexableInterface $article)
    {
        $wordsCount = 0;
        $superText = $article->getTitle(). $article->getBody() . $article->getMetaDescription() . $article->getKeywords();
        $cleanStr = StringMethods::cleanString($superText, true);

        unset($superText);

        $words = array_count_values(str_word_count($cleanStr, 1));
        arsort($words);

        $path = $this->pathPrefix . $article->getUrlKey();
        $title = $article->getTitle();
        $rowDesc = '';

        if (!empty($article->getMetaDescription())) {
            $rowDesc = $article->getMetaDescription();
        }
        $rowCreated = $article->getCreated();

        $additionalData = json_encode($this->getAdditionalData($article));

        foreach ($words as $word => $occ) {
            if (strlen($word) < 3) {
                continue;
            }
            $wordsCount += 1;
            $weight = $this->calculateWeight($word, $occ, $title, $article->getKeywords(), $path);

            $this->resertConnections();
            $this->dbConnSearch->execute(self::INSERT_SQL, $this->alias, $article->getId(), $word, $path, $title, $rowDesc, $rowCreated, $occ, $weight, $additionalData);
        }

        $this->resertConnections();

        return $wordsCount;
    }

    protected function preBuild($complete = false)
    {
        if ($complete === true) {
            $this->dbConnSearch->execute(self::TRUNCATE_SQL);
            $this->dbConnSearch->execute(self::PREPARE_ID_SQL);
        } else {
            $this->dbConnSearch->execute(self::DELETE_SQL, $this->alias);
        }
    }

    /**
     * @param $article
     * @return array
     */
    public function getAdditionalData($article)
    {
        return [];
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getPathPrefix()
    {
        return $this->pathPrefix;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function setModel($model)
    {
        $this->model = $model;
        return $this;
    }

    public function setPathPrefix($pathPrefix)
    {
        $this->pathPrefix = $pathPrefix;
        return $this;
    }

    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * @param bool $complete
     * @param bool $runByUser
     */
    abstract public function buildIndex($complete = false, $runByUser = false);
}

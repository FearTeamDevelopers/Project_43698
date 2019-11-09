<?php

namespace Search\Model\Sources\Source;

use Search\Model\Sources\AbstractSource;
use THCFrame\Events\Events as Event;
use THCFrame\Model\Model;
use Search\Model\Exception;

/**
 * Description of action
 *
 * @author Tomy
 */
class Action extends AbstractSource
{

    protected $model = 'App\Model\ActionModel';
    protected $pathPrefix = '/akce/r/';
    protected $table = 'action';
    protected $alias = 'Akce';

    /**
     *
     * @param bool $complete
     * @param bool|string $runByUser
     */
    public function buildIndex($complete = false, $runByUser = false)
    {
        $this->dbConnectionTimer = microtime(true);

        try {
            $this->preBuild($complete);

            $starttime = microtime(true);

            $articles = \App\Model\ActionModel::all(['active' => true, 'approved' => true]);
            $wordsCount = 0;

            if (null !== $articles) {
                /* @var $article \App\Model\ActionModel */
                foreach ($articles as $article) {
                    $wordsCount += $this->processArticle($article);
                }
            } else {
                throw new Exception\Indexer(sprintf('No articles found for indexing in %s', $this->alias));
            }

            unset($articles);

            $time = round(microtime(true) - $starttime, 2);
            //$this->resertConnections();
            $this->dbConnSearch->execute(self::INSERT_LOG_SQL, $this->alias, $this->table, $runByUser, $wordsCount);

            Event::fire('search.log', ['success', sprintf('Search index for %s built in %s sec', $this->alias, $time)]);
        } catch (\Exception $ex) {
            $this->errorNotification($ex, $runByUser);
        }
    }

    /**
     *
     * @param Model $article
     * @return array
     */
    public function getAdditionalData($article)
    {
        return [
            'startDate' => $article->getStartDate(),
        ];
    }
}

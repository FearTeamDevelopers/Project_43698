<?php

namespace Search\Controller;

use Search\Etc\Controller;
use THCFrame\Events\Events as Event;

/**
 *
 */
class IndexController extends Controller
{

    /**
     * Get search index log and controll panel for search module.
     *
     * @before _secured, _admin
     */
    public function index()
    {
        $view = $this->getActionView();
        $searchIndexLog = \Search\Model\SearchIndexLogModel::all([], ['*'], ['created' => 'desc'], 100);
        $indexModel = new \Search\Model\SearchIndexModel();

        $view->set('tables', $indexModel->getDataSourceAliases())
                ->set('indexLog', $searchIndexLog);
    }

    /**
     * Completly delete and create new search index.
     *
     * @before _cron
     */
    public function buildIndex()
    {
        $this->disableView();
        //ini_set('max_execution_time', 1800);

        $indexModel = new \Search\Model\SearchIndexModel();
        $indexModel->indexAllDataSources(true, false);
    }

    /**
     * Manualy build index for specific table.
     *
     * @before _secured, _admin
     *
     * @param string $dataSource table name
     */
    public function updateIndex($dataSource)
    {
        $view = $this->getActionView();
        $indexModel = new \Search\Model\SearchIndexModel();

        try{
            $dataSourceClass = '\Search\Model\Sources\Source\\' . ucfirst($dataSource);
            $dataSourceObject = new $dataSourceClass();
        } catch (Exception $ex) {
            Event::fire('search.log', ['fail', sprintf('Data source class %s not found', $dataSourceClass)]);
            $view->errorMessage('This source does not exists or content cannot be indexed');
            self::redirect('/search/');
        }

        $indexModel->indexDataSource($dataSourceObject, false, $this->getUser()->getWholeName());

        $view->infoMessage('Search index is built');
        self::redirect('/search/');
    }

}

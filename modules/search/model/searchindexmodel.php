<?php

namespace Search\Model;

use Search\Model\Basic\BasicSearchIndexModel;
use Search\Model\Sources\SourceInterface;
use THCFrame\Core\StringMethods;

/**
 *
 */
class SearchIndexModel extends BasicSearchIndexModel
{

    private $dataSources;

    /**
     * @readwrite
     */
    protected $_alias = 'si';

    /**
     * @read
     */
    protected $_databaseIdent = 'search';

    /**
     * Object constructor
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->registerSources();
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
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

    /**
     *
     */
    private function registerSources()
    {
        $this->dataSources = [
            new Sources\Source\Action(),
            new Sources\Source\Report(),
            new Sources\Source\News(),
            new Sources\Source\PageContent(),
            new Sources\Source\Advertisement(),
        ];
    }

    /**
     *
     * @param bool $complete
     * @param bool|string $runByUser
     */
    public function indexAllDataSources($complete, $runByUser)
    {
        if (count($this->dataSources)) {
            /* @var $source \Search\Model\Sources\SourceInterface */
            foreach ($this->dataSources as $source) {
                try {
                    $source->buildIndex($complete, $runByUser);
                } catch (\Exception $ex) {
                    Event::fire('search.log', 'fail', $ex->getMessage());
                }
            }
        }
    }

    /**
     *
     * @param SourceInterface $source
     * @param type $complete
     * @param type $runByUser
     */
    public function indexDataSource(SourceInterface $source, $complete, $runByUser)
    {
        try {
            $source->buildIndex($complete, $runByUser);
        } catch (\Exception $ex) {
            Event::fire('search.log', 'fail', $ex->getMessage());
        }
    }

    /**
     *
     * @param string $text
     * @param int $page
     * @param int $limit
     * @param bool $bazarOnly
     * @return array
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public function search($text, $page = 1, $limit = 10, $bazarOnly = false)
    {
        $cleanStr = StringMethods::cleanString($text);
        $body = [];
        $whereCond = '';
        $totalCount = 0;

        $words = explode(' ', $cleanStr);
        $searchQuery = self::getQuery(
                        ['DISTINCT (si.pathToSource)', 'si.sourceModel', 'si.sourceId', 'si.additionalData',
                            'si.sourceTitle', 'si.sourceMetaDescription', 'si.sourceCreated']
        );

        foreach ($words as $key => $word) {
            if (strlen($word) < 3) {
                unset($words[$key]);
                continue;
            }

            $paramArr[] = $word;
        }

        if (count($words) > 0) {
            if ($bazarOnly === true) {
                $whereCond = "si.sourceModel = 'Bazar' AND ";
            }
            
            $whereCondArr = array_fill(0, count($words), "si.sword LIKE '%%?%%'");
            $whereCond .= '(' . implode(' OR ', $whereCondArr) . ')';

            array_unshift($paramArr, $whereCond);
        } else {
            unset($searchQuery);
        }

        if ($paramArr === null) {
            unset($searchQuery);
        } else {
            call_user_func_array([$searchQuery, 'wheresql'], $paramArr);

            $searchQuery->order('si.weight', 'DESC')
                    ->order('si.occurence', 'DESC')
                    ->order('si.sourceCreated', 'DESC')
                    ->limit(100);
            $searchResult = self::initialize($searchQuery);

            $searchReturnArr = [];
            if (null !== $searchResult) {
                $totalCount = count($searchResult);

                /* @var $model \Search\Model\SearchIndexModel */
                foreach ($searchResult as $model) {
                    $searchReturnArr[] = [
                        'title' => strval($model->getSourceTitle()),
                        'model' => $model->getSourceModel(),
                        'path' => $model->getPathToSource(),
                        'text' => $model->getSourceMetaDescription(),
                        'created' => $model->getSourceCreated(),
                        'additionalData' => json_decode($model->getAdditionalData(), true),
                    ];
                }
            }

            $body = array_slice($searchReturnArr, (int) $limit * ((int) $page - 1), $limit);
            array_unshift($body, $totalCount);
        }

        return $body;
    }

    public function getDataSourceAliases()
    {
        $return = [];

        if (count($this->dataSources)) {
            /* @var $source \Search\Model\Sources\SourceInterface */
            foreach ($this->dataSources as $source) {
                $return[$source->getTable()] = $source->getAlias();
            }
        }

        return $return;
    }

    public function getDataSources()
    {
        return $this->dataSources;
    }

    public function setDataSources($dataSources)
    {
        $this->dataSources = $dataSources;
        return $this;
    }

}

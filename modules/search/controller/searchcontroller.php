<?php

namespace Search\Controller;

use Search\Etc\Controller;
use THCFrame\Request\RequestMethods;

/**
 *
 */
class SearchController extends Controller
{

    /**
     * Main search method.
     *
     * @return json encoded array
     */
    public function doSearch($page = 1)
    {
        $this->disableView();

        $text = RequestMethods::post('str');
        $articlesPerPage = $this->getConfig()->search_results_per_page;

        $indexModel = new \Search\Model\SearchIndexModel();
        $result = $indexModel->search($text, $page, $articlesPerPage);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * Main bazaar search method.
     *
     * @return json encoded array
     */
    public function doAdSearch($page = 1)
    {
        $this->disableView();

        $text = RequestMethods::post('adstr');
        $articlesPerPage = $this->getConfig()->search_results_per_page;

        $indexModel = new \Search\Model\SearchIndexModel();
        $result = $indexModel->search($text, $page, $articlesPerPage, true);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

}

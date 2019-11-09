<?php

namespace Search\Controller;

use Search\Etc\Controller;
use Search\Model\SearchIndexModel;
use THCFrame\Request\RequestMethods;

/**
 *
 */
class SearchController extends Controller
{

    /**
     * Main search method.
     *
     * @param int $page
     * @return void encoded array
     */
    public function doSearch($page = 1)
    {
        $this->disableView();

        $text = RequestMethods::post('str');
        $articlesPerPage = $this->getConfig()->search_results_per_page;

        $indexModel = new SearchIndexModel();
        $result = $indexModel->search($text, $page, $articlesPerPage);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * Main bazaar search method.
     *
     * @param int $page
     * @return void encoded array
     */
    public function doAdSearch($page = 1)
    {
        $this->disableView();

        $text = RequestMethods::post('adstr');
        $articlesPerPage = $this->getConfig()->search_results_per_page;

        $indexModel = new SearchIndexModel();
        $result = $indexModel->search($text, $page, $articlesPerPage, true);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

}

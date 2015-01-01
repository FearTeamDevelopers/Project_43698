<?php

use Search\Etc\Controller;
use THCFrame\Core\StringMethods;
use THCFrame\Request\RequestMethods;

/**
 * 
 */
class Search_Controller_Search extends Controller
{

    /**
     * Clean string. Cleaned string contains only [a-z0-9\s]
     * 
     * @param string $str
     * @return string
     */
    private function _cleanString($str)
    {
        $cleanStr = StringMethods::removeDiacriticalMarks($str);
        $cleanStr = strtolower(trim($cleanStr));
        $cleanStr = preg_replace('/[^a-z0-9\s]+/', ' ', $cleanStr);
        $cleanStr2 = preg_replace('/\s+/', ' ', $cleanStr);

        unset($cleanStr);
        return $cleanStr2;
    }

    /**
     * Main search method
     * 
     * @return json encoded array
     */
    public function doSearch($page = 1)
    {
        $this->_willRenderActionView = false;
        $this->_willRenderLayoutView = FALSE;

        $query = RequestMethods::post('str');
        
        $cleanStr = $this->_cleanString($query);
        $articlesPerPage = $this->getConfig()->search_results_per_page;
        $searchResultCached = $this->getCache()->get('search_' . str_replace(' ', '_', substr($cleanStr, 0, 45)));

        if (null !== $searchResultCached) {
            $searchReturnArr = $searchResultCached;
        } else {
            $words = explode(' ', $cleanStr);
            $searchQuery = Search_Model_Searchindex::getQuery(array('DISTINCT (si.pathToSource)', 'si.sourceTitle'));

            foreach ($words as $key => $word) {
                if (strlen($word) < 3) {
                    unset($words[$key]);
                    continue;
                }

                $paramArr[] = $word;
            }

            if (count($words) > 0) {
                $whereCondArr = array_fill(0, count($words), "si.sword LIKE '%%?%%'");
                $whereCond = implode(' OR ', $whereCondArr);
                array_unshift($paramArr, $whereCond);
            } else {
                unset($searchQuery);
                echo json_encode(array());
            }

            if ($paramArr === null) {
                unset($searchQuery);
                echo json_encode(array());
            } else {
                call_user_func_array(array($searchQuery, 'wheresql'), $paramArr);

                $searchQuery->order('si.weight', 'DESC')
                        ->order('si.occurence', 'DESC');
                $searchResult = Search_Model_Searchindex::initialize($searchQuery);

                $searchReturnArr = array();
                if (null !== $searchResult) {
                    $searchReturnArr['totalCount'] = count($searchResult);
                    
                    foreach ($searchResult as $model) {
                        $searchReturnArr[strval($model->getSourceTitle())] = strval($model->getPathToSource());
                    }
                }

                $this->getCache()->set('search_' . str_replace(' ', '_', substr($cleanStr, 0, 45)), $searchReturnArr);
            }
        }
        
        $slicedReturnArr = array_slice($searchReturnArr, (int)$articlesPerPage * ((int)$page-1), $articlesPerPage);

        echo json_encode($slicedReturnArr);
    }

}

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
     * 
     * @param type $str
     * @param type $stopWordsCs
     * @param type $stopWordsEn
     * @return type
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
     * 
     */
    public function doSearch()
    {
        $this->_willRenderActionView = false;
        $this->_willRenderLayoutView = FALSE;

        $query = RequestMethods::post('str');

        $cleanStr = $this->_cleanString($query);

        $words = explode(' ', $cleanStr);
        $searchQuery = Search_Model_Searchindex::getQuery(array('DISTINCT (si.pathToSource)', 'si.sourceTitle'));

        foreach ($words as $key => $word) {
            if (strlen($word) < 3) {
                unset($words[$key]);
                continue;
            }

            $param_arr[] = $word;
        }

        if (count($words) > 0) {
            $whereCondArr = array_fill(0, count($words), "si.sword LIKE '%%?%%'");
            $whereCond = implode(' OR ', $whereCondArr);
            array_unshift($param_arr, $whereCond);
        } else {
            unset($searchQuery);
            echo json_encode(array());
        }

        if ($param_arr === null) {
            unset($searchQuery);
            echo json_encode(array());
        } else {
            call_user_func_array(array($searchQuery, 'wheresql'), $param_arr);

            $searchQuery->order('si.weight', 'DESC');
            $searchQuery->order('si.occurence', 'DESC');
            $result = Search_Model_Searchindex::initialize($searchQuery);

            $returnArr = array();
            if ($result !== null) {
                foreach ($result as $model) {
                    $returnArr[strval($model->getSourceTitle())] = strval($model->getPathToSource());
                }
            }

            echo json_encode($returnArr);
        }
    }

}

<?php

namespace App\Controller;

use App\Etc\Controller;
use THCFrame\Profiler\Profiler;
use THCFrame\Core\Core;
use THCFrame\Request\RequestMethods;

/**
 * 
 */
class SystemController extends Controller
{

    /**
     * Method called by ajax shows profiler bar at the bottom of screen
     */
    public function showProfiler()
    {
        $this->_willRenderActionView = false;
        $this->_willRenderLayoutView = false;

        echo Profiler::display();
    }

    /**
     * Screen resolution logging
     */
    public function logresolution()
    {
        $this->_willRenderActionView = false;
        $this->_willRenderLayoutView = false;
        
        $width = RequestMethods::post('scwidth');
        $height = RequestMethods::post('scheight');
        $res = $width. ' x '.$height;
        
        Core::getLogger()->log($res, FILE_APPEND, true, 'scres.log');
    }
}

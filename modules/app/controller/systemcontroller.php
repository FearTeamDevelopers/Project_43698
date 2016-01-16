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
     * Method called by ajax shows profiler bar at the bottom of screen.
     */
    public function showProfiler()
    {
        $this->disableView();

        echo Profiler::display();
    }

    /**
     * Screen resolution logging.
     */
    public function logresolution()
    {
        $this->disableView();

        $width = RequestMethods::post('scwidth');
        $height = RequestMethods::post('scheight');
        $res = $width . ' x ' . $height;

        Core::getLogger()->debug($res);
    }

}

<?php

use App\Etc\Controller;

class App_Controller_Index extends Controller
{
    public function index()
    {
        var_dump(THCFrame\Core\Core::generateSecret());die;
    }
    
    
}

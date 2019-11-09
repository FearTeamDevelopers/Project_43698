<?php
namespace Queue\Controller;

use Queue\Etc\Controller;

class IndexController extends Controller
{

    /**
     * @before _secured, _superadmin
     */
    public function show()
    {
        
    }

    /**
     * @before _secured, _superadmin
     * @param $id
     */
    public function postpone($id)
    {
        
    }

    /**
     * @before _secured, _superadmin
     * @param $id
     */
    public function remove($id)
    {
        
    }

    /**
     * @before _cron
     */
    public function process()
    {
        
    }
}

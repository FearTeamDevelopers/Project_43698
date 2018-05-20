<?php

namespace Apitester\Controller;

use Apitester\Etc\Controller;
use THCFrame\Request\RequestMethods;

/**
 *
 */
class IndexController extends Controller
{

    /**
     * @before _secured, _superadmin
     */
    public function index()
    {
        $view = $this->getActionView();

        if (RequestMethods::post('submitTestRequest')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                    $this->checkMultiSubmissionProtectionToken() !== true) {
                self::redirect('/apitester/');
            }

            $data = [
                'requestUrl' => RequestMethods::post('requesturl'),
                'requestMethod' => RequestMethods::post('requestmethod'),
                'requestData' => RequestMethods::post('text')
            ];

            try{
                $testerModel = new \Apitester\Model\Tester($data);
                $testerModel->makeCall();
                $view->set('request', $testerModel)
                        ->set('response', $testerModel->getResponse());

                die;
            } catch (Exception $ex) {
                $view->set('error', ['exception' => $ex->getMessage()])
                        ->set('request', null)
                        ->set('response', null);
            }
        }

        $view->set('request', null)
                ->set('response', null);
    }
}

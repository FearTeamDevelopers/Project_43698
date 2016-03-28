<?php

namespace Api\Controller;

use Api\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;

/**
 *
 */
class UserController extends Controller
{

    /**
     * Check login credentials and send back api token
     */
    public function login()
    {
        $security = Registry::get('security');
        $email = RequestMethods::post('email');
        $password = RequestMethods::post('password');
        $error = false;

        if (empty($email)) {
            $message = $this->lang('LOGIN_EMAIL_ERROR');
            $error = true;
        }

        if (empty($password)) {
            $message = $this->lang('LOGIN_PASS_ERROR');
            $error = true;
        }

        if (!$error) {
            try {
                $security->authenticate($email, $password);
                $user = $security->getUser();

                $apiToken = \THCFrame\Security\Model\ApiTokenModel::first(array('userId = ?' => $user->getId()));

                $this->ajaxResponse('Authenticated', false, 200, array('apiV1Token' => $apiToken->getToken()));
            } catch (\Exception $e) {
                Event::fire('api.log', array('fail', 'annonymous', 'Exception: ' . get_class($e) . ' Message: ' . $e->getMessage()));
                $message = $this->lang('LOGIN_COMMON_ERROR');
                $this->ajaxResponse('Authentication failed: '.$message, true, 401);
            }
        } else {
            $this->ajaxResponse('Authentication failed: '.$message, true, 401);
        }
    }

}

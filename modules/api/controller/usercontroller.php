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
    public function loginPost()
    {
        $security = Registry::get('security');
        $email = RequestMethods::post('email');
        $password = RequestMethods::post('password');
        $apiTokenPost = RequestMethods::post('apiToken');
        $error = false;

        if (empty($email)) {
            $message = $this->lang('LOGIN_EMAIL_ERROR');
            $error = true;
        }

        if (empty($password)) {
            $message = $this->lang('LOGIN_PASS_ERROR');
            $error = true;
        }

        if (!empty($apiTokenPost)) {
            $apiToken = \THCFrame\Security\Model\ApiTokenModel::first(['token = ?' => $apiTokenPost]);
            if (!empty($apiToken)) {
                $this->ajaxResponse('Authenticated', false, 200, ['apiToken' => $apiToken->getToken()]);
            } else {
                $message = $this->lang('LOGIN_COMMON_ERROR');
                Event::fire('api.log', ['fail', 'annonymous', 'This api ' . $apiTokenPost . ' token does not exists']);
                $this->ajaxResponse('Authentication failed: ' . $message, true, 401);
            }
        } elseif (!$error && $apiToken === null) {
            try {
                $security->authenticate($email, $password);
                $user = $security->getUser();

                $apiToken = \THCFrame\Security\Model\ApiTokenModel::first(['userId = ?' => $user->getId()]);

                if (!empty($apiToken)) {
                    $this->ajaxResponse('Authenticated', false, 200, ['apiToken' => $apiToken->getToken()]);
                } else {
                    $message = $this->lang('LOGIN_API_TOKEN_NOT_ASSIGN');
                    Event::fire('api.log', ['fail', $user->getWholeName(), 'User has not assign api token']);
                    $this->ajaxResponse('Authentication failed: ' . $message, true, 401);
                }
            } catch (\Exception $e) {
                Event::fire('api.log', ['fail', 'annonymous', 'Exception: ' . get_class($e) . ' Message: ' . $e->getMessage()]);
                $message = $this->lang('LOGIN_COMMON_ERROR');
                $this->ajaxResponse('Authentication failed: ' . $message, true, 401);
            }
        } else {
            $this->ajaxResponse('Authentication failed: ' . $message, true, 401);
        }
    }

}

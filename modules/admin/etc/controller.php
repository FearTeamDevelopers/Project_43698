<?php

namespace Admin\Etc;

use THCFrame\Events\Events as Events;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Controller\Controller as BaseController;
use THCFrame\Core\StringMethods;

/**
 * Module specific controller class extending framework controller class
 */
class Controller extends BaseController
{

    const SUCCESS_MESSAGE_1 = ' has been successfully created';
    const SUCCESS_MESSAGE_2 = 'All changes were successfully saved';
    const SUCCESS_MESSAGE_3 = ' has been successfully deleted';
    const SUCCESS_MESSAGE_4 = 'Everything has been successfully activated';
    const SUCCESS_MESSAGE_5 = 'Everything has been successfully deactivated';
    const SUCCESS_MESSAGE_6 = 'Everything has been successfully deleted';
    const SUCCESS_MESSAGE_7 = 'Everything has been successfully uploaded';
    const SUCCESS_MESSAGE_8 = 'Everything has been successfully saved';
    const SUCCESS_MESSAGE_9 = 'Everything has been successfully added';
    const ERROR_MESSAGE_1 = 'Oops, something went wrong';
    const ERROR_MESSAGE_2 = 'Not found';
    const ERROR_MESSAGE_3 = 'Unknown error eccured';
    const ERROR_MESSAGE_4 = 'You dont have permissions to do this';
    const ERROR_MESSAGE_5 = 'Required fields are not valid';
    const ERROR_MESSAGE_6 = 'Access denied';

    /**
     * Store security context object
     * @var type 
     * @read
     */
    protected $_security;

    /**
     * Store initialized cache object
     * @var type 
     * @read
     */
    protected $_cache;

    /**
     * Store configuration
     * @var type 
     * @read
     */
    protected $_config;

    /**
     * 
     * @param type $string
     * @return type
     */
    protected function _createUrlKey($string)
    {
        $neutralChars = array('.', ',', '_', '(', ')', '[', ']', '|', ' ');
        $preCleaned = StringMethods::fastClean($string, $neutralChars, '-');
        $cleaned = StringMethods::fastClean($preCleaned);
        $return = mb_ereg_replace('[\-]+', '-', trim(trim($cleaned), '-'));
        return strtolower($return);
    }

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->_security = Registry::get('security');
        $this->_cache = Registry::get('cache');
        $this->_config = Registry::get('configuration');

        // schedule disconnect from database 
        Events::add('framework.controller.destruct.after', function($name) {
            $database = Registry::get('database');
            $database->disconnect();
        });
    }

    /**
     * @protected
     */
    public function _secured()
    {
        $session = Registry::get('session');

        //This line should be present only for DEV env
        //$this->_security->forceLogin(1);

        $user = $this->_security->getUser();

        if (!$user) {
            $this->_willRenderActionView = false;
            $this->_willRenderLayoutView = false;
            self::redirect('/admin/login');
        }

        //60min inactivity till logout
        if (time() - $session->get('lastActive') < 3600) {
            $session->set('lastActive', time());
        } else {
            $view = $this->getActionView();

            $view->infoMessage('You has been logged out for long inactivity');
            $this->_security->logout();
            self::redirect('/admin/login');
        }
    }

    /**
     * @protected
     */
    public function _member()
    {
        $view = $this->getActionView();

        if ($this->_security->getUser() && $this->_security->isGranted('role_member') !== true) {
            throw new \THCFrame\Security\Exception\Unauthorized(self::ERROR_MESSAGE_6);
        }
    }

    /**
     * 
     * @return boolean
     */
    protected function isMember()
    {
        if ($this->_security->getUser() && $this->_security->isGranted('role_member') === true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @protected
     */
    public function _participant()
    {
        $view = $this->getActionView();

        if ($this->_security->getUser() && $this->_security->isGranted('role_participant') !== true) {
            throw new \THCFrame\Security\Exception\Unauthorized(self::ERROR_MESSAGE_6);
        }
    }

    /**
     * 
     * @return boolean
     */
    protected function isParticipant()
    {
        if ($this->_security->getUser() && $this->_security->isGranted('role_participant') === true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @protected
     */
    public function _admin()
    {
        $view = $this->getActionView();

        if ($this->_security->getUser() && $this->_security->isGranted('role_admin') !== true) {
            throw new \THCFrame\Security\Exception\Unauthorized(self::ERROR_MESSAGE_6);
        }
    }

    /**
     * 
     * @return boolean
     */
    protected function isAdmin()
    {
        if ($this->_security->getUser() && $this->_security->isGranted('role_admin') === true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @protected
     */
    public function _superadmin()
    {
        $view = $this->getActionView();

        if ($this->_security->getUser() && $this->_security->isGranted('role_superadmin') !== true) {
            throw new \THCFrame\Security\Exception\Unauthorized(self::ERROR_MESSAGE_6);
        }
    }

    /**
     * 
     * @return boolean
     */
    protected function isSuperAdmin()
    {
        if ($this->_security->getUser() && $this->_security->isGranted('role_superadmin') === true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * load user from security context
     */
    public function getUser()
    {
        return $this->_security->getUser();
    }

    /**
     * 
     */
    public function mutliSubmissionProtectionToken()
    {
        $session = Registry::get('session');
        $token = $session->get('submissionprotection');

        if ($token === null) {
            $token = md5(microtime());
            $session->set('submissionprotection', $token);
        }

        return $token;
    }

    /**
     * 
     * @return type
     */
    public function revalidateMutliSubmissionProtectionToken()
    {
        $session = Registry::get('session');
        $session->erase('submissionprotection');
        $token = md5(microtime());
        $session->set('submissionprotection', $token);

        return $token;
    }

    /**
     * 
     * @param type $token
     */
    public function checkMutliSubmissionProtectionToken($token)
    {
        $session = Registry::get('session');
        $sessionToken = $session->get('submissionprotection');

        if ($token == $sessionToken) {
            $session->erase('submissionprotection');
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     */
    public function checkCSRFToken()
    {
        if ($this->_security->getCSRF()->verifyRequest()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     */
    public function render()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        if ($view) {
            $view->set('authUser', $this->_security->getUser())
                    ->set('env', ENV)
                    ->set('isMember', $this->isMember())
                    ->set('isParticipant', $this->isParticipant())
                    ->set('isAdmin', $this->isAdmin())
                    ->set('isSuperAdmin', $this->isSuperAdmin())
                    ->set('token', $this->_security->getCSRF()->getToken());
        }

        if ($layoutView) {
            $layoutView->set('authUser', $this->_security->getUser())
                    ->set('env', ENV)
                    ->set('isMember', $this->isMember())
                    ->set('isParticipant', $this->isParticipant())
                    ->set('isAdmin', $this->isAdmin())
                    ->set('isSuperAdmin', $this->isSuperAdmin())
                    ->set('token', $this->_security->getCSRF()->getToken());
        }

        parent::render();
    }

}

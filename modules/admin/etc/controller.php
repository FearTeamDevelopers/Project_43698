<?php

namespace Admin\Etc;

use THCFrame\Events\Events as Events;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Controller\Controller as BaseController;
use THCFrame\Core\StringMethods;

/**
 * Module specific controller class extending framework controller class
 *
 * @author Tomy
 */
class Controller extends BaseController
{

    protected $_security;

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
     * 
     * @param type $string
     * @return type
     */
    protected function _createUrlKey($string)
    {
        $string = StringMethods::removeDiacriticalMarks($string);
        $string = str_replace(array('.', ',', '_', '(', ')', '[', ']', '|', ' '), '-', $string);
        $string = str_replace(array('?', '!', '@', '&', '*', ':', '+', '=', '~', '°', '´', '`', '%', "'", '"'), '', $string);
        $string = trim($string);
        $string = trim($string, '-');
        return strtolower($string);
    }

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->_security = Registry::get('security');

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

        $user = $this->_security->getUser();

        if (!$user) {
            self::redirect('/admin/login');
        }

        //30min inactivity till logout
        if (time() - $session->get('lastActive') < 1800) {
            $session->set('lastActive', time());
        } else {
            $view = $this->getActionView();

            $view->infoMessage('You has been logged out for long inactivity');
            self::redirect('/admin/logout');
        }
    }

    /**
     * @protected
     */
    public function _member()
    {
        $view = $this->getActionView();

        if ($this->_security->getUser() && $this->_security->isGranted('role_member') !== true) {
            $view->infoMessage(self::ERROR_MESSAGE_6);
            self::redirect('/admin/logout');
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
            $view->infoMessage(self::ERROR_MESSAGE_6);
            self::redirect('/admin/logout');
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
            $view->infoMessage(self::ERROR_MESSAGE_6);
            self::redirect('/admin/logout');
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
            $view->infoMessage(self::ERROR_MESSAGE_6);
            self::redirect('/admin/logout');
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
        $user = $this->_security->getUser();

        if ($view) {
            $view->set('authUser', $user)
                    ->set('env', ENV);
            $view->set('isMember', $this->isMember())
                    ->set('isParticipant', $this->isParticipant())
                    ->set('isAdmin', $this->isAdmin())
                    ->set('isSuperAdmin', $this->isSuperAdmin())
                    ->set('token', $this->_security->getCSRF()->getToken());
        }

        if ($layoutView) {
            $layoutView->set('authUser', $user)
                    ->set('env', ENV);
            $layoutView->set('isMember', $this->isMember())
                    ->set('isParticipant', $this->isParticipant())
                    ->set('isAdmin', $this->isAdmin())
                    ->set('isSuperAdmin', $this->isSuperAdmin())
                    ->set('token', $this->_security->getCSRF()->getToken());
        }

        parent::render();
    }

}

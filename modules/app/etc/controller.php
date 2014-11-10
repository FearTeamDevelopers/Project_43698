<?php

namespace App\Etc;

use THCFrame\Events\Events as Events;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Controller\Controller as BaseController;

/**
 * Module specific controller class extending framework controller class
 *
 * @author Tomy
 */
class Controller extends BaseController
{

    /**
     *
     * @var type 
     */
    private $_security;

    const SUCCESS_MESSAGE_1 = ' byl(a) �sp�n� vytov�en(a)';
    const SUCCESS_MESSAGE_2 = 'V�echny zm�ny byly �sp�n� ulo�eny';
    const SUCCESS_MESSAGE_3 = ' byl(a) �sp�n� smaz�n(a)';
    const SUCCESS_MESSAGE_4 = 'V�e bylo �sp�n� aktivov�no';
    const SUCCESS_MESSAGE_5 = 'V�e bylo �sp�n� deaktivov�no';
    const SUCCESS_MESSAGE_6 = 'V�e bylo �sp�n� smaz�no';
    const SUCCESS_MESSAGE_7 = 'V�e bylo �sp�n� nahr�no';
    const SUCCESS_MESSAGE_8 = 'V�e bylo �sp�n� ulo�eno';
    const SUCCESS_MESSAGE_9 = 'V�e bylo �sp�n� p�id�no';
    const ERROR_MESSAGE_1 = 'Oops, n�co se pokazilo';
    const ERROR_MESSAGE_2 = 'Nenalezeno';
    const ERROR_MESSAGE_3 = 'Nastala nezn�m� chyby';
    const ERROR_MESSAGE_4 = 'Na tuto operaci nem�te opr�vn�n�';
    const ERROR_MESSAGE_5 = 'Povinn� pole nejsou validn�';
    const ERROR_MESSAGE_6 = 'P��sput odep�en';

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
     * 
     * @param type $string
     * @return type
     */
    protected function _createUrlKey($string)
    {
        $string = StringMethods::removeDiacriticalMarks($string);
        $string = str_replace(array('.', ',', '_', '(', ')', '[', ']', '|', ' '), '-', $string);
        $string = str_replace(array('?', '!', '@', '&', '*', ':', '+', '=', '~', '�', '�', '`', '%', "'", '"'), '', $string);
        $string = trim($string);
        $string = trim($string, '-');
        return strtolower($string);
    }

    /**
     * @protected
     */
    public function _secured()
    {
        $session = Registry::get('session');

        $user = $this->_security->getUser();

        if (!$user) {
            self::redirect('/login');
        }

        //30min inactivity till logout
        if (time() - $session->get('lastActive') < 1800) {
            $session->set('lastActive', time());
        } else {
            $view = $this->getActionView();

            $view->infoMessage('Byl jste odhl�en z d�vodu dlouh� neaktivity');
            $this->_security->logout();
            self::redirect('/login');
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
            self::redirect('/logout');
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
                    ->set('token', $this->_security->getCSRF()->getToken());
        }

        if ($layoutView) {
            $layoutView->set('authUser', $user)
                    ->set('env', ENV);
            $layoutView->set('isMember', $this->isMember())
                    ->set('token', $this->_security->getCSRF()->getToken());
        }

        parent::render();
    }

}

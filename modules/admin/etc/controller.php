<?php

namespace Admin\Etc;

use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Controller\Controller as BaseController;
use THCFrame\Core\StringMethods;
use THCFrame\Request\RequestMethods;

/**
 * Module specific controller class extending framework controller class
 */
class Controller extends BaseController
{

//    const SUCCESS_MESSAGE_1 = 'Content has been successfully created';
//    const SUCCESS_MESSAGE_2 = 'All changes were successfully saved';
//    const SUCCESS_MESSAGE_3 = 'Content has been successfully deleted';
//    const SUCCESS_MESSAGE_4 = 'Everything has been successfully activated';
//    const SUCCESS_MESSAGE_5 = 'Everything has been successfully deactivated';
//    const SUCCESS_MESSAGE_6 = 'Everything has been successfully deleted';
//    const SUCCESS_MESSAGE_7 = 'Everything has been successfully uploaded';
//    const SUCCESS_MESSAGE_8 = 'Everything has been successfully saved';
//    const SUCCESS_MESSAGE_9 = 'Everything has been successfully added';
//    const SUCCESS_MESSAGE_10 = 'New password has been successfully generated and sent to email';
//    const ERROR_MESSAGE_1 = 'Oops, something went wrong';
//    const ERROR_MESSAGE_2 = 'Not found';
//    const ERROR_MESSAGE_3 = 'Unknown error eccured';
//    const ERROR_MESSAGE_4 = 'You dont have permissions to do this';
//    const ERROR_MESSAGE_5 = 'Required fields are not valid';
//    const ERROR_MESSAGE_6 = 'Access denied';
//    const ERROR_MESSAGE_7 = 'Password is too weak';

    const SUCCESS_MESSAGE_1 = 'Vše bylo úspěšně vytovřeno';
    const SUCCESS_MESSAGE_2 = 'Všechny změny byly úspěšně uloženy';
    const SUCCESS_MESSAGE_3 = 'Vybraná položka byla úspěšně smazána';
    const SUCCESS_MESSAGE_4 = 'Vše bylo úspěšně aktivováno';
    const SUCCESS_MESSAGE_5 = 'Vše bylo úspěšně deaktivováno';
    const SUCCESS_MESSAGE_6 = 'Vše bylo úspěšně smazáno';
    const SUCCESS_MESSAGE_7 = 'Vše bylo úspěšně nahráno';
    const SUCCESS_MESSAGE_8 = 'Vše bylo úspěšně uloženo';
    const SUCCESS_MESSAGE_9 = 'Vše bylo úspěšně přidáno';
    const SUCCESS_MESSAGE_10 = 'Heslo bylo nastaveno a posláno na email uživatele';
    const ERROR_MESSAGE_1 = 'Oops, něco se pokazilo';
    const ERROR_MESSAGE_2 = 'Nenalezeno';
    const ERROR_MESSAGE_3 = 'Nastala neznámá chyby';
    const ERROR_MESSAGE_4 = 'Na tuto operaci nemáte oprávnění';
    const ERROR_MESSAGE_5 = 'Povinná pole nejsou validní';
    const ERROR_MESSAGE_6 = 'Přísput odepřen';
    const ERROR_MESSAGE_7 = 'Heslo je příliš slabé';
    const ERROR_MESSAGE_8 = 'Platnost hesla vzprší během %s dní';
    const ERROR_MESSAGE_9 = 'Původní heslo není platné';

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
     * @param type $body
     * @param type $subject
     * @param type $sendTo
     * @param type $sendFrom
     * @return boolean
     */
    protected function _sendEmail($body, $subject, $sendTo = null, $sendFrom = null)
    {
        try {
            require_once APP_PATH . '/vendors/swiftmailer/swift_required.php';
            $transport = \Swift_MailTransport::newInstance();
            $mailer = \Swift_Mailer::newInstance($transport);

            $message = \Swift_Message::newInstance(null)
                    ->setSubject($subject)
                    ->setBody($body, 'text/html');

            if (null === $sendTo) {
                $message->setTo($this->getConfig()->system->adminemail);
            } else {
                $message->setTo($sendTo);
            }

            if (null === $sendFrom) {
                $message->setFrom('info@hastrman.cz');
            } else {
                $message->setFrom($sendFrom);
            }

            if ($mailer->send($message)) {
                return true;
            } else {
                Event::fire('admin.log', array('fail', 'No email sent'));
                return false;
            }
        } catch (\Exception $ex) {
            Event::fire('admin.log', array('fail', 'Error while sending email: ' . $ex->getMessage()));
            return false;
        }
    }

    /**
     * Disable view, used for ajax calls
     */
    protected function _disableView()
    {
        $this->_willRenderActionView = false;
        $this->_willRenderLayoutView = false;
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
        Event::add('framework.controller.destruct.after', function($name) {
            Registry::get('database')->disconnectAll();
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

        //5h inactivity till logout
        if (time() - $session->get('lastActive') < 18000) {
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
    public function _cron()
    {
        if (!preg_match('#^Links.*#i', RequestMethods::server('HTTP_USER_AGENT')) &&
                '95.168.206.203' != RequestMethods::server('REMOTE_ADDR')) {
            throw new \THCFrame\Security\Exception\Unauthorized(self::ERROR_MESSAGE_6);
        }
    }

    /**
     * 
     * @return boolean
     */
    protected function isCron()
    {
        if (preg_match('#^Links.*#i', RequestMethods::server('HTTP_USER_AGENT')) &&
                '95.168.206.203' == RequestMethods::server('REMOTE_ADDR')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @protected
     */
    public function _member()
    {
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
     * 
     */
    protected function _mutliSubmissionProtectionToken()
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
    protected function _revalidateMutliSubmissionProtectionToken()
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
    protected function _checkMutliSubmissionProtectionToken($token)
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
    protected function _checkCSRFToken()
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

    /**
     * Load user from security context
     */
    public function getUser()
    {
        return $this->_security->getUser();
    }

}

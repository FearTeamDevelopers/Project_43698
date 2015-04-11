<?php

namespace App\Etc;

use THCFrame\Events\Events as Event;
use THCFrame\Controller\Controller as BaseController;
use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;
use THCFrame\Core\StringMethods;

/**
 * Module specific controller class extending framework controller class
 */
class Controller extends BaseController
{
//    const SUCCESS_MESSAGE_1 = ' has been successfully created';
//    const SUCCESS_MESSAGE_2 = 'All changes were successfully saved';
//    const SUCCESS_MESSAGE_3 = ' has been successfully deleted';
//    const SUCCESS_MESSAGE_4 = 'Everything has been successfully activated';
//    const SUCCESS_MESSAGE_5 = 'Everything has been successfully deactivated';
//    const SUCCESS_MESSAGE_6 = 'Everything has been successfully deleted';
//    const SUCCESS_MESSAGE_7 = 'Everything has been successfully uploaded';
//    const SUCCESS_MESSAGE_8 = 'Everything has been successfully saved';
//    const SUCCESS_MESSAGE_9 = 'Everything has been successfully added';
//    const ERROR_MESSAGE_1 = 'Oops, something went wrong';
//    const ERROR_MESSAGE_2 = 'Not found';
//    const ERROR_MESSAGE_3 = 'Unknown error eccured';
//    const ERROR_MESSAGE_4 = 'You dont have permissions to do this';
//    const ERROR_MESSAGE_5 = 'Required fields are not valid';
//    const ERROR_MESSAGE_6 = 'Access denied';
//    const ERROR_MESSAGE_7 = 'Password is too weak';

    const SUCCESS_MESSAGE_1 = ' byl(a) úspěšně vytovřen(a)';
    const SUCCESS_MESSAGE_2 = 'Všechny změny byly úspěšně uloženy';
    const SUCCESS_MESSAGE_3 = ' byl(a) úspěšně smazán(a)';
    const SUCCESS_MESSAGE_4 = 'Vše bylo úspěšně aktivováno';
    const SUCCESS_MESSAGE_5 = 'Vše bylo úspěšně deaktivováno';
    const SUCCESS_MESSAGE_6 = 'Vše bylo úspěšně smazáno';
    const SUCCESS_MESSAGE_7 = 'Vše bylo úspěšně nahráno';
    const SUCCESS_MESSAGE_8 = 'Vše bylo úspěšně uloženo';
    const SUCCESS_MESSAGE_9 = 'Vše bylo úspěšně přidáno';
    const ERROR_MESSAGE_1 = 'Oops, něco se pokazilo';
    const ERROR_MESSAGE_2 = 'Nenalezeno';
    const ERROR_MESSAGE_3 = 'Nastala neznámá chyby';
    const ERROR_MESSAGE_4 = 'Na tuto operaci nemáte oprávnění';
    const ERROR_MESSAGE_5 = 'Povinná pole nejsou validní';
    const ERROR_MESSAGE_6 = 'Přísput odepřen';
    const ERROR_MESSAGE_7 = 'Heslo je příliš slabé';

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
     * Store server host name
     * @var type 
     * @read
     */
    protected $_serverHost;

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->_security = Registry::get('security');
        $this->_serverHost = RequestMethods::server('HTTP_HOST');
        $this->_cache = Registry::get('cache');
        $this->_config = Registry::get('configuration');

        // schedule disconnect from database 
        Event::add('framework.controller.destruct.after', function($name) {
            Registry::get('database')->disconnectAll();
        });

        $metaData = $this->getCache()->get('global_meta_data');

        if (null !== $metaData) {
            $metaData = $metaData;
        } else {
            $metaData = array(
                'metadescription' => $this->getConfig()->meta_description,
                'metarobots' => $this->getConfig()->meta_robots,
                'metatitle' => $this->getConfig()->meta_title,
                'metaogurl' => $this->getConfig()->meta_og_url,
                'metaogtype' => $this->getConfig()->meta_og_type,
                'metaogimage' => $this->getConfig()->meta_og_image,
                'metaogsitename' => $this->getConfig()->meta_og_site_name,
                'showfeedback' => $this->getConfig()->show_feedback
            );

            $this->getCache()->set('global_meta_data', $metaData);
        }

        $this->getLayoutView()
                ->set('metatitle', $metaData['metatitle'])
                ->set('metarobots', $metaData['metarobots'])
                ->set('metadescription', $metaData['metadescription'])
                ->set('metaogurl', $metaData['metaogurl'])
                ->set('metaogtype', $metaData['metaogtype'])
                ->set('metaogimage', $metaData['metaogimage'])
                ->set('metaogsitename', $metaData['metaogsitename'])
                ->set('showfeedback', $metaData['showfeedback']);
    }

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
     * @param type $pageCount
     * @param type $page
     * @param type $path
     */
    protected function _pagerMetaLinks($pageCount, $page, $path)
    {
        if ($pageCount > 1) {
            $prevPage = $page - 1;
            $nextPage = $page + 1;

            if ($nextPage > $pageCount) {
                $nextPage = 0;
            }

            $this->getLayoutView()
                    ->set('pagedprev', $prevPage)
                    ->set('pagedprevlink', $path . $prevPage)
                    ->set('pagednext', $nextPage)
                    ->set('pagednextlink', $path . $nextPage);
        }
    }

    /**
     * 
     * @param type $body
     * @param type $subject
     * @param type $sendTo
     * @param type $sendFrom
     * @return boolean
     */
    protected function sendEmail($body, $subject, $sendTo = null, $sendFrom = null)
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
     * @protected
     */
    public function _secured()
    {
        $session = Registry::get('session');
        $user = $this->_security->getUser();

        if (!$user) {
            $this->_willRenderActionView = false;
            $this->_willRenderLayoutView = false;
            self::redirect('/prihlasit');
        }

        //1h inactivity till logout
        if (time() - $session->get('lastActive') < 3600) {
            $session->set('lastActive', time());
        } else {
            $view = $this->getActionView();

            $view->infoMessage('Byl jste odhlášen z důvodu dlouhé neaktivity');
            $this->_security->logout();
            self::redirect('/');
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
                    ->set('token', $this->_security->getCSRF()->getToken());
        }

        if ($layoutView) {
            $layoutView->set('authUser', $this->_security->getUser())
                    ->set('env', ENV)
                    ->set('isMember', $this->isMember())
                    ->set('isParticipant', $this->isParticipant())
                    ->set('token', $this->_security->getCSRF()->getToken());
        }

        parent::render();
    }

}

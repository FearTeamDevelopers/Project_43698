<?php

namespace Cron\Etc;

use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Controller\Controller as BaseController;
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
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->_security = Registry::get('security');
        $this->_cache = Registry::get('cache');
        $this->_config = Registry::get('configuration');
        $this->_willRenderActionView = false;
        $this->_willRenderLayoutView = false;

        // schedule disconnect from database 
        Event::add('framework.controller.destruct.after', function($name) {
            Registry::get('database')->disconnectAll();
        });
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
     * 
     */
    public function render()
    {
        parent::render();
    }

}

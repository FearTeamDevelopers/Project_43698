<?php

namespace Search\Etc;

use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Controller\Controller as BaseController;
use THCFrame\Core\StringMethods;
use THCFrame\Request\RequestMethods;
use THCFrame\Core\Lang;

/**
 * Module specific controller class extending framework controller class
 */
class Controller extends BaseController
{

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
     * Store language extension
     * @var type 
     * @read
     */
    protected $_lang;

    /**
     * @read
     * @var type 
     */
    protected $_stopwords_en = array("a", "able", "about", "across", "after", "all", "almost", "also", "am", "among", "an", "and", "any", "are", "as", "at",
        "be", "because", "been", "but", "by", "can", "cannot", "could", "dear", "did", "do", "does", "either", "else", "ever", "every", "for", "from", "get", "got",
        "had", "has", "have", "he", "her", "hers", "him", "his", "how", "however", "i", "if", "in", "into", "is", "it", "its", "just", "least", "let", "like", "likely",
        "may", "me", "might", "most", "must", "my", "neither", "no", "nor", "not", "of", "off", "often", "on", "only", "or", "other", "our", "own", "rather",
        "said", "say", "says", "she", "should", "since", "so", "some", "than", "that", "the", "their", "them", "then", "there", "these", "they", "this", "tis", "to", "too", "twas",
        "us", "wants", "was", "we", "were", "what", "when", "where", "which", "while", "who", "whom", "why", "will", "with", "would", "yet", "you", "your",
        "ain't", "aren't", "can't", "could've", "couldn't", "didn't", "doesn't", "don't", "hasn't", "he'd", "he'll", "he's", "how'd", "how'll", "how's",
        "i'd", "i'll", "i'm", "i've", "isn't", "it's", "might've", "mightn't", "must've", "mustn't", "shan't", "she'd", "she'll", "she's", "should've",
        "shouldn't", "that'll", "that's", "there's", "they'd", "they'll", "they're", "they've", "wasn't", "we'd", "we'll", "we're", "weren't", "what'd",
        "what's", "when'd", "when'll", "when's", "where'd", "where'll", "where's", "who'd", "who'll", "who's", "why'd", "why'll", "why's", "won't", "would've",
        "wouldn't", "you'd", "you'll", "you're", "you've");

    /**
     * @read
     * @var type 
     */
    protected $_stopwords_cs = array(
        'com', 'net', 'org', 'div', 'nbsp', 'http', 'jeden', 'jedna', 'dva', 'tri', 'ctyri', 'pet', 'sest', 'sedm', 'osm',
        'devet', 'deset', 'dny', 'den', 'dne', 'dni', 'dnes', 'timto', 'budes', 'budem', 'byli', 'jses', 'muj', 'svym',
        'tomto', 'tam', 'tohle', 'tuto', 'tyto', 'jej', 'zda', 'proc', 'mate', 'tato', 'kam', 'tohoto', 'kdo', 'kteri',
        'nam', 'tom', 'tomuto', 'mit', 'nic', 'proto', 'kterou', 'byla', 'toho', 'protoze', 'asi', 'nasi', 'napiste',
        'coz', 'tim', 'takze', 'svych', 'jeji', 'svymi', 'jste', 'tedy', 'teto', 'bylo', 'kde', 'prave', 'nad', 'nejsou',
        'pod', 'tema', 'mezi', 'pres', 'pak', 'vam', 'ani', 'kdyz', 'vsak', 'jsem', 'tento', 'clanku', 'clanky', 'aby',
        'jsme', 'pred', 'pta', 'jejich', 'byl', 'jeste', 'bez', 'take', 'pouze', 'prvni', 'vase', 'ktera', 'nas', 'novy',
        'tipy', 'pokud', 'muze', 'design', 'strana', 'jeho', 'sve', 'jine', 'zpravy', 'nove', 'neni', 'vas', 'jen', 'podle',
        'zde', 'clanek', 'email', 'byt', 'vice', 'bude', 'jiz', 'nez', 'ktery', 'ktere', 'nebo', 'ten', 'tak', 'pri', 'jsou',
        'jak', 'dalsi', 'ale', 'jako', 'zpet', 'pro', 'www', 'atd', 'cca', 'cili', 'dal', 'der', 'des', 'det', 'druh', 'faq',
        'hot', 'for', 'info', 'ing'
    );

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
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->_security = Registry::get('security');
        $this->_cache = Registry::get('cache');
        $this->_config = Registry::get('configuration');
        $this->_lang = Lang::getInstance();

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
        $user = $this->_security->getUser();

        if (!$user) {
            self::redirect('/admin/login');
        }

        //60min inactivity till logout
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
    public function _admin()
    {
        $view = $this->getActionView();

        if ($this->_security->getUser() && $this->_security->isGranted('role_admin') !== true) {
            throw new \THCFrame\Security\Exception\Unauthorized($this->lang('ACCESS_DENIED'));
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
    public function _cron()
    {
        if (!preg_match('#^Links.*#i', RequestMethods::server('HTTP_USER_AGENT')) &&
                '95.168.206.203' != RequestMethods::server('REMOTE_ADDR')) {
            throw new \THCFrame\Security\Exception\Unauthorized($this->lang('ACCESS_DENIED'));
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
    public function _superadmin()
    {
        $view = $this->getActionView();

        if ($this->_security->getUser() && $this->_security->isGranted('role_superadmin') !== true) {
            throw new \THCFrame\Security\Exception\Unauthorized($this->lang('ACCESS_DENIED'));
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
     * Load user from security context
     */
    public function getUser()
    {
        return $this->_security->getUser();
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
                    ->set('env', ENV)
                    ->set('isAdmin', $this->isAdmin())
                    ->set('isSuperAdmin', $this->isSuperAdmin())
                    ->set('token', $this->_security->getCSRF()->getToken());
        }

        if ($layoutView) {
            $layoutView->set('authUser', $user)
                    ->set('env', ENV)
                    ->set('isAdmin', $this->isAdmin())
                    ->set('isSuperAdmin', $this->isSuperAdmin())
                    ->set('token', $this->_security->getCSRF()->getToken());
        }

        parent::render();
    }

}

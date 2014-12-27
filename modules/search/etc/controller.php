<?php

namespace Search\Etc;

use THCFrame\Events\Events as Events;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Controller\Controller as BaseController;
use THCFrame\Core\StringMethods;
use THCFrame\Request\RequestMethods;

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
        $return = trim(trim($cleaned), '-');
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
    public function _cron()
    {
        if (RequestMethods::server('HTTP_HOST') === null) {
            exit;
        }
    }

    /**
     * 
     * @return boolean
     */
    protected function isCron()
    {
        if (RequestMethods::server('HTTP_HOST') === null) {
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
            $view->set('isAdmin', $this->isAdmin())
                    ->set('isSuperAdmin', $this->isSuperAdmin())
                    ->set('token', $this->_security->getCSRF()->getToken());
        }

        if ($layoutView) {
            $layoutView->set('authUser', $user)
                    ->set('env', ENV);
            $layoutView->set('isAdmin', $this->isAdmin())
                    ->set('isSuperAdmin', $this->isSuperAdmin())
                    ->set('token', $this->_security->getCSRF()->getToken());
        }

        parent::render();
    }

}

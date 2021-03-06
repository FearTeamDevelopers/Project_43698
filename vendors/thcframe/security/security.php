<?php

namespace THCFrame\Security;

use THCFrame\Core\Base;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\Security\Exception;
use THCFrame\Security\SecurityInterface;
use THCFrame\Security\CSRF;
use THCFrame\Security\PasswordManager;
use THCFrame\Security\Model\BasicUserModel;

/**
 * Security context class. Wrapper for authentication and authorization methods
 */
class Security extends Base implements SecurityInterface
{

    /**
     * Authentication object
     *
     * @read
     * @var THCFrame\Security\Authentication\Authentication
     */
    protected $_authentication;

    /**
     * Authorization object
     *
     * @read
     * @var THCFrame\Security\Authorization\Authorization
     */
    protected $_authorization;

    /**
     * Cross-site request forgery protection
     *
     * @read
     * @var THCFrame\Security\CSRF
     */
    protected $_csrf;

    /**
     * PasswordManager object
     *
     * @read
     * @var THCFrame\Security\PasswordManager
     */
    protected $_passwordManager;

    /**
     * Authenticated user object
     *
     * @readwrite
     * @var \THCFrame\Security\Model\BasicUserModel or null
     */
    protected $_user = null;

    /**
     * Session object
     *
     * @read
     * @var THCFrame\Session\Driver
     */
    protected $_session;

    /**
     *
     * @param type $method
     * @return \THCFrame\Security\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     * Method initialize security context. Check session for user token and
     * initialize authentication and authorization classes
     */
    public function initialize($configuration)
    {
        Event::fire('framework.security.initialize.before', []);

        @session_regenerate_id();
        $this->_session = Registry::get('session');

        if (!empty($configuration->security)) {
            $this->_csrf = new CSRF($this->_session);
            $this->_passwordManager = new PasswordManager($configuration->security);
        } else {
            throw new Exception\Argument('Error in configuration file');
        }

        $user = $this->_session->get('authUser');

        $authentication = new Authentication\Authentication();
        $this->_authentication = $authentication->initialize($configuration);

        $authorization = new Authorization\Authorization();
        $this->_authorization = $authorization->initialize($configuration);

        if ($user instanceof BasicUserModel) {
            $this->_user = $user;
            Event::fire('framework.security.initialize.user', [$user]);
        }

        Event::fire('framework.security.initialize.after', []);

        return $this;
    }

    /**
     * @param BasicUserModel $user
     */
    public function setUser(BasicUserModel $user)
    {
        @session_regenerate_id();
        $user->password = null;
        $user->salt = null;

        $this->_session->set('authUser', $user)
            ->set('lastActive', time());

        $this->_user = $user;
    }

    /**
     * @return BasicUserModel
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * Return Cross-site request forgery object
     *
     * @return THCFrame\Security\CSRF
     */
    public function getCsrf()
    {
        return $this->_csrf;
    }

    /**
     * Return PasswordManager object
     *
     * @return THCFrame\Security\PasswordManager
     */
    public function getPasswordManager()
    {
        return $this->_passwordManager;
    }

    /**
     * Method erases all authentication tokens for logged user and regenerates
     * session
     */
    public function logout()
    {
        $this->_session->remove('authUser')
            ->remove('lastActive')
            ->remove('csrf');

        BasicUserModel::deleteAuthenticationToken();

        $this->_user = null;
        @session_regenerate_id();
    }

    /**
     * Authentication facade method
     *
     * @param string $name
     * @param string $pass
     * @return true or re-throw exception
     * @throws \THCFrame\Security\Exception
     */
    public function authenticate($name, $pass)
    {
        try {
            $user = $this->_authentication->authenticate($name, $pass);
            $this->setUser($user);
            return true;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Authorization facade method
     *
     * @param string $requiredRole
     * @return mixed
     */
    public function isGranted($requiredRole)
    {
        try {
            return $this->_authorization->isGranted($this->getUser(), $requiredRole);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    /**
     * Encrypt provided text
     *
     * @param string $string
     * @return string
     */
    public function encrypt($string)
    {
        $encryptMethod = Registry::get('configuration')->security->encryption->cipher;
        $secret = Registry::get('configuration')->security->encryption->key;
        $secretIv = Registry::get('configuration')->security->encryption->iv;

        // hash
        $key = hash('sha512', $secret);

        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $secretIv), 0, 16);

        $output = openssl_encrypt($string, $encryptMethod, $key, 0, $iv);
        return base64_encode($output);
    }

    /**
     * Decrypt encrypted text
     *
     * @param string $string
     * @return string
     */
    public function decrypt($string)
    {
        $encryptMethod = Registry::get('configuration')->security->encryption->cipher;
        $secret = Registry::get('configuration')->security->encryption->key;
        $secretIv = Registry::get('configuration')->security->encryption->iv;

        // hash
        $key = hash('sha512', $secret);

        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $secretIv), 0, 16);

        return openssl_decrypt(base64_decode($string), $encryptMethod, $key, 0, $iv);
    }

    /**
     * Function for user to log-in forcefully i.e without providing user-credentials
     *
     * @param integer $userId
     * @return boolean
     * @throws Exception\UserNotExists
     */
    public function forceLogin($userId)
    {
        $user = \App\Model\UserModel::first(['id = ?' => (int)$userId]);

        if ($user === null) {
            throw new Exception\UserNotExists('User not found');
        }

        $this->setUser($user);
        return true;
    }

    /**
     * Method creates new salt and salted password and
     * returns new hash with salt as string.
     * Method can be used only in development environment
     *
     * @param string $string
     * @return string|null
     */
    public function getPwdHash($string)
    {
        if (ENV == \THCFrame\Core\Core::ENV_DEV) {
            $salt = $this->getPasswordManager()->createSalt();
            return $this->getPasswordManager()->getPasswordHash($string, $salt) . '/' . $salt;
        } else {
            return null;
        }
    }

}

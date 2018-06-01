<?php

namespace THCFrame\Security;

use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;
use THCFrame\Core\Rand;

/**
 * Cross-site Request Forgery protection
 */
class CSRF
{

    /**
     * Token name
     *
     * @var string
     */
    protected static $tokenName = 'csrf';

    /**
     * Session object
     *
     * @var THCFrame\Session\Driver
     */
    private $session;

    /**
     * CSRF token
     *
     * @var string
     */
    private $token = null;

    /**
     *
     * @param string $tokenname
     */
    public function __construct(\THCFrame\Session\Driver $sesion)
    {
        $this->session = $sesion;
        $this->token = $this->getTokenFromSession();
    }

    /**
     * Generates the HTML input field with the token
     */
    public function generateHiddenField()
    {
        return '<input type="hidden" name="' . self::$tokenName . '" value="' . $this->getToken() . '" />';
    }

    /**
     * Verifies whether the post token was set, else dies with error
     *
     * @return boolean
     */
    public function verifyRequest()
    {
        $checkPost = RequestMethods::issetpost(self::$tokenName) && (RequestMethods::post(self::$tokenName) === $this->getTokenFromSession());
        $checkGet = RequestMethods::issetget(self::$tokenName) && (RequestMethods::get(self::$tokenName) === $this->getTokenFromSession());

        $newToken = Rand::randStr(32);
        $this->eraseToken()
                ->setToken($newToken);

        if ($checkGet || $checkPost) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return token value
     *
     * @return string
     */
    public function getToken()
    {
        if($this->token === null){
            $newToken = Rand::randStr(32);
            $this->eraseToken()
                ->setToken($newToken);
        }
        return $this->token;
    }

    /**
     * Return token value from session
     *
     * @return string
     */
    public function getTokenFromSession()
    {
        return $this->session->get(self::$tokenName);
    }

    /**
     * Set token value and store it in session
     *
     * @param string $token
     * @return \THCFrame\Security\CSRF
     */
    public function setToken($token)
    {
        $this->token = $token;
        $this->session->set(self::$tokenName, $token);

        return $this;
    }

    /**
     * Set token value to null and delete it from session
     *
     * @return \THCFrame\Security\CSRF
     */
    public function eraseToken()
    {
        $this->token = null;
        $this->session->remove(self::$tokenName);

        return $this;
    }

    /**
     * Return tokenname
     *
     * @return string
     */
    public function getTokenname()
    {
        return self::$tokenName;
    }

}

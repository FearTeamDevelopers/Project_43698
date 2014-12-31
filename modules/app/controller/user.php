<?php

use App\Etc\Controller as Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Security\PasswordManager;

/**
 * Description of App_Controller_User
 *
 * 
 */
class App_Controller_User extends Controller
{

    /**
     * 
     */
    public function login()
    {
        $view = $this->getActionView();

        $canonical = 'http://' . $this->getServerHost() . '/login';

        $this->getLayoutView()
                ->set('metatitle', 'Hastrman - Přihlásit se')
                ->set('canonical', $canonical);

        if (RequestMethods::post('submitLogin')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/login');
            }
            
            $email = RequestMethods::post('email');
            $password = RequestMethods::post('password');
            $error = false;

            if (empty($email)) {
                $view->set('email_error', 'Email není vyplňen');
                $error = true;
            }

            if (empty($password)) {
                $view->set('password_error', 'Heslo není vyplněno');
                $error = true;
            }

            if (!$error) {
                try {
                    $status = $this->getSecurity()->authenticate($email, $password);

                    if ($status === true) {
                        self::redirect('/muj-profil');
                    } else {
                        $view->set('account_error', 'Email a/nebo heslo je špatně');
                    }
                } catch (\Exception $e) {
                    if (ENV == 'dev') {
                        $view->set('account_error', $e->getMessage());
                    } else {
                        $view->set('account_error', 'Email a/nebo heslo je špatně');
                    }
                }
            }
        }
    }

    /**
     * 
     */
    public function logout()
    {
        $this->_willRenderActionView = false;
        $this->_willRenderLayoutView = false;

        $this->getSecurity()->logout();
        self::redirect('/');
    }

    /**
     * 
     */
    public function registration()
    {
        $view = $this->getActionView();
        $view->set('submstoken', $this->mutliSubmissionProtectionToken());

        $canonical = 'http://' . $this->getServerHost() . '/registrace';

        $this->getLayoutView()
                ->set('metatitle', 'Hastrman - Registrace')
                ->set('canonical', $canonical);

        if (RequestMethods::post('register')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/');
            }
            $errors = array();

            if (RequestMethods::post('password') !== RequestMethods::post('password2')) {
                $errors['password2'] = array('Hesla se neshodují');
            }

            $email = App_Model_User::first(
                            array('email = ?' => RequestMethods::post('email')), array('email')
            );

            if ($email) {
                $errors['email'] = array('Tento email je již použit');
            }

            $salt = PasswordManager::createSalt();
            $hash = PasswordManager::hashPassword(RequestMethods::post('password'), $salt);
            
            $user = new App_Model_User(array(
                'firstname' => RequestMethods::post('firstname'),
                'lastname' => RequestMethods::post('lastname'),
                'email' => RequestMethods::post('email'),
                'phoneNumber' => RequestMethods::post('phone'),
                'password' => $hash,
                'salt' => $salt,
                'role' => 'role_member'
            ));

            if (empty($errors) && $user->validate()) {
                $user->save();

                $view->successMessage('Registrace byla úspěšná');
                self::redirect('/muj-profil');
            } else {
                $view->set('errors', $errors + $user->getErrors())
                        ->set('user', $user);
            }
        }
    }

    /**
     * @before _secured, _member
     */
    public function profile()
    {
        $view = $this->getActionView();
        $errors = array();
        
        $canonical = 'http://' . $this->getServerHost() . '/profil';

        $user = App_Model_User::first(array('id = ?' => $this->getUser()->getId()));

        $this->getLayoutView()
                ->set('metatile', 'Hastrman - Můj profil')
                ->set('canonical', $canonical);
        $view->set('user', $user);

        if (RequestMethods::post('editProfile')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/muj-profil');
            }
            
            if (RequestMethods::post('password') !== RequestMethods::post('password2')) {
                $errors['password2'] = array('Hesla se neshodují');
            }

            if (RequestMethods::post('email') != $user->email) {
                $email = App_Model_User::first(
                                array('email = ?' => RequestMethods::post('email', $user->email)), array('email')
                );

                if ($email) {
                    $errors['email'] = array('Tento email je již použit');
                }
            }

            $pass = RequestMethods::post('password');

            if ($pass === null || $pass == '') {
                $salt = $user->getSalt();
                $hash = $user->getPassword();
            } else {
                $salt = PasswordManager::createSalt();
                $hash = PasswordManager::hashPassword($pass, $salt);
            }

            $user->firstname = RequestMethods::post('firstname');
            $user->lastname = RequestMethods::post('lastname');
            $user->email = RequestMethods::post('email');
            $user->phoneNumber = RequestMethods::post('phone');
            $user->password = $hash;
            $user->salt = $salt;

            if (empty($errors) && $user->validate()) {
                $user->save();
                $this->getSecurity()->setUser($user);

                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/muj-profil');
            } else {
                $view->set('errors', $errors + $user->getErrors());
            }
        }
    }
}

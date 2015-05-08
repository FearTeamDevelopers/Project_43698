<?php

namespace App\Controller;

use App\Etc\Controller as Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Security\PasswordManager;
use THCFrame\Events\Events as Event;
use THCFrame\Core\Rand;

/**
 * 
 */
class UserController extends Controller
{

    private function _checkEmailActToken($token)
    {
        $exists = \App\Model\UserModel::first(array('emailActivationToken = ?' => $token));

        if ($exists === null) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * App module login
     */
    public function login()
    {
        $view = $this->getActionView();

        $canonical = 'http://' . $this->getServerHost() . '/prihlasit';

        $this->getLayoutView()
                ->set('metatitle', 'Hastrman - Přihlásit se')
                ->set('canonical', $canonical);

        if (RequestMethods::post('submitLogin')) {
            if ($this->_checkCSRFToken() !== true) {
                self::redirect('/prihlasit');
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
                    $this->getSecurity()->authenticate($email, $password);
                    $daysToExpiration = $this->getSecurity()->getUser()->getDaysToPassExpiration();
                    
                    if($daysToExpiration !== false){
                        if($daysToExpiration < 14 && $daysToExpiration > 1){
                            $view->infoMessage(sprintf(self::ERROR_MESSAGE_8, $daysToExpiration));
                        }elseif($daysToExpiration < 5 && $daysToExpiration > 1){
                            $view->warningMessage(sprintf(self::ERROR_MESSAGE_8, $daysToExpiration));
                        }elseif($daysToExpiration >= 1){
                            $view->errorMessage(sprintf(self::ERROR_MESSAGE_8, $daysToExpiration));
                        }
                    }
                    
                    self::redirect('/muj-profil');
                } catch (\THCFrame\Security\Exception\UserBlocked $ex) {
                    $view->set('account_error', 'Účet byl uzamčen. Přihlášení opakujte za 15 min.');
                } catch (\THCFrame\Security\Exception\UserInactive $ex) {
                    $view->set('account_error', 'Účet ještě nebyl aktivován');
                } catch (\THCFrame\Security\Exception\UserExpired $ex) {
                    $view->set('account_error', 'Vypršela platnost účtu');
                } catch (\Exception $e) {
                    if (ENV == 'dev') {
                        $view->set('account_error', $e->getMessage());
                    } else {
                        $view->set('account_error', 'Email a/nebo heslo není správně');
                    }
                }
            }
        }
    }

    /**
     * App module logout
     */
    public function logout()
    {
        $this->_willRenderActionView = false;
        $this->_willRenderLayoutView = false;

        $this->getSecurity()->logout();
        self::redirect('/');
    }

    /**
     * Registration. Create only members without access into administration
     */
    public function registration()
    {
        $view = $this->getActionView();
        $user = null;

        $canonical = 'http://' . $this->getServerHost() . '/registrace';

        $view->set('submstoken', $this->_mutliSubmissionProtectionToken())
                ->set('user', $user);

        $this->getLayoutView()
                ->set('metatitle', 'Hastrman - Registrace')
                ->set('canonical', $canonical);

        if (RequestMethods::post('register')) {
            if ($this->_checkCSRFToken() !== true &&
                    $this->_checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/');
            }
            $errors = array();

            if (RequestMethods::post('password') !== RequestMethods::post('password2')) {
                $errors['password2'] = array('Hesla se neshodují');
            }

            $email = \App\Model\UserModel::first(
                            array('email = ?' => RequestMethods::post('email')), array('email')
            );

            if ($email) {
                $errors['email'] = array('Tento email je již použit');
            }

            if (PasswordManager::strength(RequestMethods::post('password')) <= 0.5) {
                $errors['password'] = array(self::ERROR_MESSAGE_7);
            }

            $salt = PasswordManager::createSalt();
            $hash = PasswordManager::hashPassword(RequestMethods::post('password'), $salt);
            $verifyEmail = $this->getConfig()->registration_verif_email;

            if ($verifyEmail) {
                $active = false;
            } else {
                $active = true;
            }

            $actToken = Rand::randStr(50);
            for ($i = 1; $i <= 75; $i++) {
                if ($this->_checkEmailActToken($actToken)) {
                    break;
                } else {
                    $actToken = Rand::randStr(50);
                }

                if ($i == 75) {
                    $errors['email'] = array(self::ERROR_MESSAGE_3 . ' Zkuste registraci opakovat později');
                    break;
                }
            }

            $user = new \App\Model\UserModel(array(
                'firstname' => RequestMethods::post('firstname'),
                'lastname' => RequestMethods::post('lastname'),
                'email' => RequestMethods::post('email'),
                'phoneNumber' => RequestMethods::post('phone'),
                'password' => $hash,
                'salt' => $salt,
                'role' => 'role_member',
                'active' => $active,
                'emailActivationToken' => $actToken
            ));

            if (empty($errors) && $user->validate()) {
                $uid = $user->save();

                if ($verifyEmail) {
                    $emailBody = 'Děkujem za Vaši registraci na stránkách Hastrman.cz<br/>'
                            . 'Po kliknutí na následující odkaz bude Váš účet aktivován<br/><br/>'
                            . '<a href="http://' . $this->getServerHost() . '/aktivovatucet/' . $actToken . '">Aktivovat účet</a><br/><br/>'
                            . 'S pozdravem,<br/>Hastrmani';

                    if ($this->_sendEmail($emailBody, 'Hastrman - Registrace', $user->getEmail(), 'registrace@hastrman.cz')) {
                        Event::fire('app.log', array('success', 'User Id with email activation: ' . $uid));
                        $view->successMessage('Registrace byla úspěšná. Na uvedený email byl zaslán odkaz k aktivaci účtu.');
                    } else {
                        Event::fire('app.log', array('fail', 'Email not send for User Id: ' . $uid));
                        $user->delete();
                        $view->errorMessage('Nepodařilo se odeslat aktivační email, opakujte registraci později');
                        self::redirect('/');
                    }
                } else {
                    Event::fire('app.log', array('success', 'User Id: ' . $uid));
                    $view->successMessage('Registrace byla úspěšná');
                }

                self::redirect('/');
            } else {
                $view->set('errors', $errors + $user->getErrors())
                        ->set('user', $user);
            }
        }
    }

    /**
     * Edit user currently logged in
     * 
     * @before _secured, _member
     */
    public function profile()
    {
        $view = $this->getActionView();
        $errors = array();

        $canonical = 'http://' . $this->getServerHost() . '/profil';

        $user = \App\Model\UserModel::first(array('id = ?' => $this->getUser()->getId()));

        $this->getLayoutView()
                ->set('metatile', 'Hastrman - Můj profil')
                ->set('canonical', $canonical);
        $view->set('user', $user);

        if (RequestMethods::post('editProfile')) {
            if ($this->_checkCSRFToken() !== true) {
                self::redirect('/muj-profil');
            }

            if (RequestMethods::post('password') !== RequestMethods::post('password2')) {
                $errors['password2'] = array('Hesla se neshodují');
            }

            if (RequestMethods::post('email') != $user->email) {
                $email = \App\Model\UserModel::first(
                                array('email = ?' => RequestMethods::post('email', $user->email)), array('email')
                );

                if ($email) {
                    $errors['email'] = array('Tento email je již použit');
                }
            }

            $oldPassword = RequestMethods::post('oldpass');
            if (!empty($oldPassword)) {
                $newPass = RequestMethods::post('password');
                
                try{
                    $user = $user->changePassword($oldPassword, $newPass);
                } catch (\THCFrame\Security\Exception\WrongPassword $ex) {
                    $errors['oldpass'] = array(self::ERROR_MESSAGE_9);
                }  catch (\THCFrame\Security\Exception\WeakPassword $ex){
                    $errors['password'] = array(self::ERROR_MESSAGE_7);
                }
            }

            $user->firstname = RequestMethods::post('firstname');
            $user->lastname = RequestMethods::post('lastname');
            $user->email = RequestMethods::post('email');
            $user->phoneNumber = RequestMethods::post('phone');

            if (empty($errors) && $user->validate()) {
                $user->update();
                $this->getSecurity()->setUser($user);

                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/muj-profil');
            } else {
                $view->set('errors', $errors + $user->getErrors());
            }
        }
    }

    /**
     * Activate account via activation link send by email
     * 
     * @param string    $key    activation token
     */
    public function activateAccount($key)
    {
        $view = $this->getActionView();

        $user = \App\Model\UserModel::first(array('active = ?' => false, 'emailActivationToken = ?' => $key));

        if (null === $user) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/');
        }

        if ($user->activateAccount()) {
            Event::fire('app.log', array('success', 'User Id: ' . $user->getId()));
            $view->successMessage('Účet byl aktivován');
            self::redirect('/');
        } else {
            Event::fire('app.log', array('fail', 'User Id: ' . $user->getId(),
                'Errors: ' . json_encode($user->getErrors())));
            $view->warningMessage(self::ERROR_MESSAGE_1);
            self::redirect('/');
        }
    }

}

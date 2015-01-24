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
            if ($this->checkCSRFToken() !== true) {
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
                    $status = $this->getSecurity()->authenticate($email, $password);

                    if ($status === true) {
                        self::redirect('/muj-profil');
                    } else {
                        $view->set('account_error', 'Email a/nebo heslo je špatně');
                    }
                } catch (THCFrame\Security\Exception\UserInactive $e){
                    $view->set('account_error', 'Účet ještě nebyl aktivován');
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

            $email = \App\Model\UserModel::first(
                            array('email = ?' => RequestMethods::post('email')), array('email')
            );

            if ($email) {
                $errors['email'] = array('Tento email je již použit');
            }

            $salt = PasswordManager::createSalt();
            $hash = PasswordManager::hashPassword(RequestMethods::post('password'), $salt);
            $verifyEmail = $this->getConfig()->registration_verif_email;
            
            if ($verifyEmail) {
                $active = false;
                $actToken = Rand::randStr(50);
            }else{
                $active = true;
                $actToken = null;
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
                    require_once APP_PATH . '/vendors/swiftmailer/swift_required.php';
                    $transport = \Swift_SmtpTransport::newInstance('smtp.ebola.cz', 465, 'ssl')
                            ->setUsername('info@fear-team.cz')
                            ->setPassword('ThcEbmInfo-2015*');

                    $mailer = \Swift_Mailer::newInstance($transport);

                    $emailBody = 'Děkujem za Vaši registraci na stránkách Hastrman.cz<br/>'
                            . 'Po kliknutí na následující odkaz bude Váš účet aktivován<br/><br/>'
                            . '<a href="http://'.$this->getServerHost().'/aktivovatucet/' . $actToken . '">Aktivovat účet</a><br/><br/>'
                            . 'S pozdravem,<br/>Hastrmani';

                    $regEmail = \Swift_Message::newInstance()
                            ->setSubject('Hastrman - Registrace')
                            ->setFrom('info@fear-team.cz')
                            ->setTo($user->getEmail())
                            ->setBody($emailBody, 'text/html');
                    $mailer->send($regEmail);
                }

                Event::fire('app.log', array('success', 'User Id: '.$uid));
                
                if ($verifyEmail) {
                    $view->successMessage('Registrace byla úspěšná. Na uvedený email byl zaslán odkaz k aktivaci účtu.');
                }else{
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
            if ($this->checkCSRFToken() !== true) {
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
    
    /**
     * Activate account via activation link send by email
     * 
     * @param string    $key    activation token
     */
    public function activateAccount($key)
    {
        $view = $this->getActionView();
        
        $user = \App\Model\UserModel::first(array('active = ?' => false, 'emailActivationToken = ?' => $key));
        
        if(null === $user){
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/');
        }
        
        $user->active = true;
        $user->emailActivationToken = null;
        
        if($user->validate()){
            $user->save();
            
            Event::fire('app.log', array('success', 'User Id: '.$user->getId()));
            $view->successMessage('Účet byl aktivován');
            self::redirect('/');
        }else{
            Event::fire('app.log', array('fail', 'User Id: '.$user->getId()));
            $view->warningMessage(self::ERROR_MESSAGE_1);
            self::redirect('/');
        }
    }
}

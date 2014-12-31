<?php

use App\Etc\Controller as Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Security\PasswordManager;
use THCFrame\Events\Events as Event;
use THCFrame\Core\Rand;

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
            $actToken = Rand::randStr(50);
            
            $user = new App_Model_User(array(
                'firstname' => RequestMethods::post('firstname'),
                'lastname' => RequestMethods::post('lastname'),
                'email' => RequestMethods::post('email'),
                'phoneNumber' => RequestMethods::post('phone'),
                'password' => $hash,
                'salt' => $salt,
                'role' => 'role_member',
                'active' => false,
                'emailActivationToken' => $actToken
            ));

            if (empty($errors) && $user->validate()) {
                $uid = $user->save();
                
                require_once APP_PATH . '/vendors/swiftmailer/swift_required.php';
                $transport = Swift_MailTransport::newInstance();
                $mailer = Swift_Mailer::newInstance($transport);

                $emailBody = 'Děkujem za Vaši registraci na stránkách Hastrman.cz<br/>'
                        . 'Po kliknutí na následující odkaz bude Váš účet aktivován<br/><br/>'
                        . '<a href="/aktivovatucet/'.$actToken.'">Aktivovat účet</a><br/><br/>'
                        . 'S pozdravem,<br/> Hastrmani';
                
                $email = Swift_Message::newInstance()
                        ->setSubject('Hastrman - Bazar - Dotaz k inzerátu')
                        ->setFrom('bazar@hastrman.cz')
                        ->setTo($user->getEmail())
                        ->setBody($emailBody);

                $mailer->send($email);

                Event::fire('admin.log', array('success', 'User Id: '.$uid));
                $view->successMessage('Registrace byla úspěšná');
                self::redirect('/');
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
    
    /**
     * 
     * @param type $key
     */
    public function activateAccount($key)
    {
        $view = $this->getActionView();
        
        $user = App_Model_User::first(array('active = ?' => false, 'emailActivationToken = ?' => $key));
        
        if(null === $user){
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/');
        }
        
        $user->active = true;
        $user->emailActivationToken = '';
        
        if($user->validate()){
            $user->save();
            
            Event::fire('admin.log', array('success', 'User Id: '.$user->getId()));
            $view->successMessage('Účet byl aktivován');
            self::redirect('/');
        }else{
            Event::fire('admin.log', array('fail', 'User Id: '.$user->getId()));
            $view->warningMessage(self::ERROR_MESSAGE_1);
            self::redirect('/');
        }
    }
}

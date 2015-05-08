<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Security\PasswordManager;
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
     * Login into administration
     */
    public function login()
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();

        if (RequestMethods::post('submitLogin')) {
            
            $email = RequestMethods::post('email');
            $password = RequestMethods::post('password');
            $error = false;

            if (empty($email)) {
                $view->set('account_error', 'Email není vyplňen');
                $error = true;
            }

            if (empty($password)) {
                $view->set('account_error', 'Heslo není vyplněno');
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
                    
                    self::redirect('/admin/');
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
     * Logout from administration
     */
    public function logout()
    {
        $this->_disableView();

        $this->getSecurity()->logout();
        self::redirect('/admin/');
    }

    /**
     * Get list users with basic roles
     * 
     * @before _secured, _admin
     */
    public function index()
    {
        $view = $this->getActionView();

        $users = \App\Model\UserModel::all(
                        array('role <> ?' => 'role_superadmin'), 
                        array('id', 'firstname', 'lastname', 'email', 'role', 'active', 'created', 'blocked'),
                        array('id' => 'asc')
        );

        $view->set('users', $users);
    }

    /**
     * Create new user
     * 
     * @before _secured, _admin
     */
    public function add()
    {
        $view = $this->getActionView();
        $user = null;

        $view->set('submstoken', $this->_mutliSubmissionProtectionToken())
                ->set('user', $user);

        if (RequestMethods::post('submitAddUser')) {
            if ($this->_checkCSRFToken() !== true &&
                    $this->_checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/user/');
            }

            $errors = array();

            if (RequestMethods::post('password') !== RequestMethods::post('password2')) {
                $errors['password2'] = array('Hesla se neshodují');
            }

            $email = \App\Model\UserModel::first(array('email = ?' => RequestMethods::post('email')), array('email'));

            if ($email) {
                $errors['email'] = array('Tento email se již používá');
            }

            if (PasswordManager::strength(RequestMethods::post('password')) <= 0.6) {
                $errors['password'] = array(self::ERROR_MESSAGE_7);
            }

            $salt = PasswordManager::createSalt();
            $hash = PasswordManager::hashPassword(RequestMethods::post('password'), $salt);

            $actToken = Rand::randStr(50);
            for ($i = 1; $i <= 75; $i++) {
                if ($this->_checkEmailActToken($actToken)) {
                    break;
                } else {
                    $actToken = Rand::randStr(50);
                }

                if ($i == 75) {
                    $errors['email'] = array(self::ERROR_MESSAGE_3 . ' Zkuste vytvoření uživatele opakovat později');
                    break;
                }
            }

            $user = new \App\Model\UserModel(array(
                'firstname' => RequestMethods::post('firstname'),
                'lastname' => RequestMethods::post('lastname'),
                'email' => RequestMethods::post('email'),
                'phoneNumber' => RequestMethods::post('phone'),
                'emailActivationToken' => null,
                'password' => $hash,
                'salt' => $salt,
                'active' => true,
                'role' => RequestMethods::post('role', 'role_member')
            ));

            if (empty($errors) && $user->validate()) {
                $userId = $user->save();

                Event::fire('admin.log', array('success', 'User id: ' . $userId));
                $view->successMessage(self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/user/');
            } else {
                Event::fire('admin.log', array('fail', 'Errors: ' . json_encode($errors + $user->getErrors())));
                $view->set('errors', $errors + $user->getErrors())
                        ->set('submstoken', $this->_revalidateMutliSubmissionProtectionToken())
                        ->set('user', $user);
            }
        }
    }

    /**
     * Edit user currently logged in
     * 
     * @before _secured, _participant
     */
    public function updateProfile()
    {
        $view = $this->getActionView();

        $user = \App\Model\UserModel::first(
                        array('active = ?' => true, 'id = ?' => $this->getUser()->getId()));

        if (NULL === $user) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            $this->_willRenderActionView = false;
            self::redirect('/admin/user/');
        }

        $view->set('user', $user);

        if (RequestMethods::post('submitUpdateProfile')) {
            if ($this->_checkCSRFToken() !== true) {
                self::redirect('/admin/user/');
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
                    $user = $user->changePassword($oldPassword, $newPass, 0.6);
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

                Event::fire('admin.log', array('success', 'User id: ' . $user->getId()));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/');
            } else {
                Event::fire('admin.log', array('fail', 'User id: ' . $user->getId(),
                    'Errors: ' . json_encode($errors + $user->getErrors())));
                $view->set('errors', $errors + $user->getErrors());
            }
        }
    }

    /**
     * Edit existing user
     * 
     * @before _secured, _admin
     * @param int   $id     user id
     */
    public function edit($id)
    {
        $view = $this->getActionView();
        $user = \App\Model\UserModel::first(array('id = ?' => (int) $id));

        if (NULL === $user) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            $this->_willRenderActionView = false;
            self::redirect('/admin/user/');
        } elseif ($user->role == 'role_superadmin' && $this->getUser()->getRole() != 'role_superadmin') {
            $view->warningMessage(self::ERROR_MESSAGE_4);
            $this->_willRenderActionView = false;
            self::redirect('/admin/user/');
        }

        $view->set('user', $user);

        if (RequestMethods::post('submitEditUser')) {
            if ($this->_checkCSRFToken() !== true) {
                self::redirect('/admin/user/');
            }

            $errors = array();

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
                    $user = $user->changePassword($oldPassword, $newPass, 0.6);
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
            $user->role = RequestMethods::post('role', $user->getRole());
            $user->active = RequestMethods::post('active');
            $user->blocked = RequestMethods::post('blocked');

            if (empty($errors) && $user->validate()) {
                $user->update();

                Event::fire('admin.log', array('success', 'User id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/user/');
            } else {
                Event::fire('admin.log', array('fail', 'User id: ' . $id,
                    'Errors: ' . json_encode($errors + $user->getErrors())));
                $view->set('errors', $errors + $user->getErrors());
            }
        }
    }

    /**
     * Delete existing user
     * 
     * @before _secured, _admin
     * @param int   $id     user id
     */
    public function delete($id)
    {
        $this->_disableView();

        $user = \App\Model\UserModel::first(array('id = ?' => $id));

        if (NULL === $user) {
            echo self::ERROR_MESSAGE_2;
        } else {
            if ($user->delete()) {
                Event::fire('admin.log', array('success', 'User id: ' . $id));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'User id: ' . $id));
                echo self::ERROR_MESSAGE_1;
            }
        }
    }

    /**
     * Show help for user section
     * 
     * @before _secured, _participant
     */
    public function help()
    {
        
    }

    /**
     * Generate new password and send it to the user
     * 
     * @before _secured, _admin
     * @param int   $id     user id
     */
    public function forcePasswordReset($id)
    {
        $view = $this->getActionView();
        $user = \App\Model\UserModel::first(array('id = ?' => (int) $id));

        if (NULL === $user) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            $this->_willRenderActionView = false;
            self::redirect('/admin/user/');
        } elseif ($user->role == 'role_superadmin' && $this->getUser()->getRole() != 'role_superadmin') {
            $view->warningMessage(self::ERROR_MESSAGE_4);
            $this->_willRenderActionView = false;
            self::redirect('/admin/user/');
        }

        try {
            $newPass = $user->forceResetPassword();

            if ($newPass !== false) {
                $emailBody = 'Dobrý den, <br/><br/>bylo Vám vyresetováno heslo. Po úspěšném přihlášení si ho změňte. '
                        . '<br/><br/>Nové heslo: ' . $newPass . ' <br/><br/>S pozdravem <br/>Hastrman';

                $this->_sendEmail($emailBody, 'Hastrman - Nové heslo', $user->getEmail());
                $view->successMessage(self::SUCCESS_MESSAGE_10);
                Event::fire('admin.log', array('success', 'Force password change for user: ' . $user->getId()));
                self::redirect('/admin/user/');
            } else {
                $view->warningMessage(self::ERROR_MESSAGE_1);
                Event::fire('admin.log', array('fail', 'Force password change for user: ' . $user->getId(),
                    'Errors: ' . json_encode($user->getErrors())));
                self::redirect('/admin/user/');
            }
        } catch (\Exception $ex) {
            $view->errorMessage(self::ERROR_MESSAGE_3);
            Event::fire('admin.log', array('fail', 'Force password change for user: ' . $user->getId(),
                'Errors: ' . $ex->getMessage()));
            self::redirect('/admin/user/');
        }
    }

}

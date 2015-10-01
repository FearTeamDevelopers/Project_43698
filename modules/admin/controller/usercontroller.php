<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Security\PasswordManager;
use THCFrame\Core\Rand;
use THCFrame\Core\StringMethods;

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
     * Login into administration.
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
                $view->set('account_error', $this->lang('LOGIN_EMAIL_ERROR'));
                $error = true;
            }

            if (empty($password)) {
                $view->set('account_error', $this->lang('LOGIN_PASS_ERROR'));
                $error = true;
            }

            if (!$error) {
                try {
                    $this->getSecurity()->authenticate($email, $password);
                    $daysToExpiration = $this->getSecurity()->getUser()->getDaysToPassExpiration();

                    if ($daysToExpiration !== false) {
                        if ($daysToExpiration < 14 && $daysToExpiration > 1) {
                            $view->infoMessage($this->lang('PASS_EXPIRATION', array($daysToExpiration)));
                        } elseif ($daysToExpiration < 5 && $daysToExpiration > 1) {
                            $view->warningMessage($this->lang('PASS_EXPIRATION', array($daysToExpiration)));
                        } elseif ($daysToExpiration <= 1) {
                            $view->errorMessage($this->lang('PASS_EXPIRATION_TOMORROW'));
                        }
                    }

                    self::redirect('/admin/');
                } catch (\THCFrame\Security\Exception\UserBlocked $ex) {
                    $view->set('account_error', $this->lang('LOGIN_COMMON_ERROR'));
                    Event::fire('admin.log', array('fail', sprintf('Account locked for %s', $email)));
                } catch (\THCFrame\Security\Exception\UserInactive $ex) {
                    $view->set('account_error', $this->lang('LOGIN_COMMON_ERROR'));
                    Event::fire('admin.log', array('fail', sprintf('Account inactive for %s', $email)));
                } catch (\THCFrame\Security\Exception\UserExpired $ex) {
                    $view->set('account_error', $this->lang('LOGIN_COMMON_ERROR'));
                    Event::fire('admin.log', array('fail', sprintf('Account expired for %s', $email)));
                } catch (\THCFrame\Security\Exception\UserNotExists $ex) {
                    $view->set('account_error', $this->lang('LOGIN_COMMON_ERROR'));
                    Event::fire('admin.log', array('fail', sprintf('User %s does not exists', $email)));
                } catch (\THCFrame\Security\Exception\WrongPassword $ex) {
                    $view->set('account_error', $this->lang('LOGIN_COMMON_ERROR'));
                    Event::fire('admin.log', array('fail', sprintf('User %s does not exists', $email)));
                } catch (\THCFrame\Security\Exception\UserPassExpired $ex) {
                    $view->set('account_error', $this->lang('LOGIN_COMMON_ERROR'));
                    Event::fire('admin.log', array('fail', sprintf('Password has expired for user %s', $email)));
                } catch (\Exception $e) {
                    Event::fire('admin.log', array('fail', 'Exception: '.$e->getMessage()));

                    if (ENV == 'dev') {
                        $view->set('account_error', $e->getMessage());
                    } else {
                        $view->set('account_error', $this->lang('LOGIN_COMMON_ERROR'));
                    }
                }
            }
        }
    }

    /**
     * Logout from administration.
     */
    public function logout()
    {
        $view = $this->getActionView();
        
        if($this->getUser() !== null && $this->getUser()->getForcePassChange() == true){
            $view->errorMessage($this->lang('LOGOUT_PASS_EXP_CHECK'));
            $this->getUser()
                    ->setForcePassChange(false)
                    ->update();
            self::redirect('/admin/user/profile/');
            exit;
        }
        
        $this->_disableView();
        
        $this->getSecurity()->logout();
        self::redirect('/admin/');
    }

    /**
     * Get list users with basic roles.
     * 
     * @before _secured, _admin
     */
    public function index()
    {
        $view = $this->getActionView();

        $users = \App\Model\UserModel::fetchAll();

        $view->set('users', $users);
    }

    /**
     * Create new user.
     * 
     * @before _secured, _admin
     */
    public function add()
    {
        $view = $this->getActionView();
        $user = null;

        $view->set('user', $user)
                ->set('roles', \App\Model\UserModel::getAllRoles());

        if (RequestMethods::post('submitAddUser')) {
            if ($this->_checkCSRFToken() !== true &&
                    $this->_checkMutliSubmissionProtectionToken() !== true) {
                self::redirect('/admin/user/');
            }

            $errors = array();

            if (RequestMethods::post('password') !== RequestMethods::post('password2')) {
                $errors['password2'] = array($this->lang('PASS_DOESNT_MATCH'));
            }

            $email = \App\Model\UserModel::first(array('email = ?' => RequestMethods::post('email')), array('email'));

            if ($email) {
                $errors['email'] = array($this->lang('EMAIL_IS_TAKEN'));
            }

            if (PasswordManager::strength(RequestMethods::post('password')) <= 0.5) {
                $errors['password'] = array($this->lang('PASS_WEAK'));
            }

            $salt = PasswordManager::createSalt();
            $hash = PasswordManager::hashPassword(RequestMethods::post('password'), $salt);
            $cleanHash = StringMethods::getHash(RequestMethods::post('password'));

            $actToken = Rand::randStr(50);
            for ($i = 1; $i <= 75; $i+=1) {
                if ($this->_checkEmailActToken($actToken)) {
                    break;
                } else {
                    $actToken = Rand::randStr(50);
                }

                if ($i == 75) {
                    $errors['email'] = array($this->lang('UNKNOW_ERROR'));
                    break;
                }
            }

            $user = new \App\Model\UserModel(array(
                'firstname' => RequestMethods::post('firstname'),
                'lastname' => RequestMethods::post('lastname'),
                'email' => RequestMethods::post('email'),
                'phoneNumber' => RequestMethods::post('phone'),
                'getNewActionNotification' => RequestMethods::post('actionNotification'),
                'getNewReportNotification' => RequestMethods::post('reportNotification'),
                'emailActivationToken' => null,
                'password' => $hash,
                'passwordHistory1' => $cleanHash,
                'salt' => $salt,
                'active' => true,
                'role' => RequestMethods::post('role', 'role_member'),
            ));

            if (empty($errors) && $user->validate()) {
                $userId = $user->save();

                Event::fire('admin.log', array('success', 'User id: '.$userId));
                $view->successMessage($this->lang('CREATE_SUCCESS'));
                self::redirect('/admin/user/');
            } else {
                Event::fire('admin.log', array('fail', 'Errors: '.json_encode($errors + $user->getErrors())));
                $view->set('errors', $errors + $user->getErrors())
                        ->set('submstoken', $this->_revalidateMutliSubmissionProtectionToken())
                        ->set('user', $user);
            }
        }
    }

    /**
     * Edit user currently logged in.
     * 
     * @before _secured, _participant
     */
    public function profile()
    {
        $view = $this->getActionView();

        $user = \App\Model\UserModel::first(
                        array('active = ?' => true, 'id = ?' => $this->getUser()->getId()));

        if (null === $user) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            $this->_willRenderActionView = false;
            self::redirect('/admin/user/');
        }

        $view->set('user', $user)
                ->set('roles', \App\Model\UserModel::getAllRoles());

        if (RequestMethods::post('submitUpdateProfile')) {
            if ($this->_checkCSRFToken() !== true) {
                self::redirect('/admin/user/');
            }
            $errors = array();

            if (RequestMethods::post('password') !== RequestMethods::post('password2')) {
                $errors['password2'] = array($this->lang('PASS_DOESNT_MATCH'));
            }

            if (RequestMethods::post('email') != $user->email) {
                $email = \App\Model\UserModel::first(
                                array('email = ?' => RequestMethods::post('email', $user->email)), array('email')
                );

                if ($email) {
                    $errors['email'] = array($this->lang('EMAIL_IS_TAKEN'));
                }
            }

            $oldPassword = RequestMethods::post('oldpass');
            if (!empty($oldPassword)) {
                $newPass = RequestMethods::post('password');

                try {
                    $user = $user->changePassword($oldPassword, $newPass, 0.5);
                } catch (\THCFrame\Security\Exception\WrongPassword $ex) {
                    $errors['oldpass'] = array($this->lang('PASS_ORIGINAL_NOT_CORRECT'));
                } catch (\THCFrame\Security\Exception\WeakPassword $ex) {
                    $errors['password'] = array($this->lang('PASS_WEAK'));
                } catch (\THCFrame\Security\Exception\PasswordInHistory $ex){
                    $errors['password'] = array($this->lang('PASS_IN_HISTORY'));
                }
            }

            $user->firstname = RequestMethods::post('firstname');
            $user->lastname = RequestMethods::post('lastname');
            $user->email = RequestMethods::post('email');
            $user->phoneNumber = RequestMethods::post('phone');
            $user->getNewActionNotification = RequestMethods::post('actionNotification');
            $user->getNewReportNotification = RequestMethods::post('reportNotification');

            if (empty($errors) && $user->validate()) {
                $user->update();
                $this->getSecurity()->setUser($user);

                Event::fire('admin.log', array('success', 'User id: '.$user->getId()));
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                self::redirect('/admin/');
            } else {
                Event::fire('admin.log', array('fail', 'User id: '.$user->getId(),
                    'Errors: '.json_encode($errors + $user->getErrors()), ));
                $view->set('errors', $errors + $user->getErrors());
            }
        }
    }

    /**
     * Edit existing user.
     * 
     * @before _secured, _admin
     *
     * @param int $id user id
     */
    public function edit($id)
    {
        $view = $this->getActionView();
        $user = \App\Model\UserModel::first(array('id = ?' => (int) $id));

        if (null === $user) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            $this->_willRenderActionView = false;
            self::redirect('/admin/user/');
        } elseif ($user->getRole() == 'role_superadmin' && $this->getUser()->getRole() != 'role_superadmin') {
            $view->warningMessage($this->lang('LOW_PERMISSIONS'));
            $this->_willRenderActionView = false;
            self::redirect('/admin/user/');
        }

        $view->set('user', $user)
                ->set('roles', \App\Model\UserModel::getAllRoles());

        if (RequestMethods::post('submitEditUser')) {
            if ($this->_checkCSRFToken() !== true) {
                self::redirect('/admin/user/');
            }

            $errors = array();

            if (RequestMethods::post('password') !== RequestMethods::post('password2')) {
                $errors['password2'] = array($this->lang('PASS_DOESNT_MATCH'));
            }

            if (RequestMethods::post('email') != $user->email) {
                $email = \App\Model\UserModel::first(
                                array('email = ?' => RequestMethods::post('email', $user->email)), array('email')
                );

                if ($email) {
                    $errors['email'] = array($this->lang('EMAIL_IS_TAKEN'));
                }
            }

            $oldPassword = RequestMethods::post('oldpass');
            if (!empty($oldPassword)) {
                $newPass = RequestMethods::post('password');

                try {
                    $user = $user->changePassword($oldPassword, $newPass, 0.5);
                } catch (\THCFrame\Security\Exception\WrongPassword $ex) {
                    $errors['oldpass'] = array($this->lang('PASS_ORIGINAL_NOT_CORRECT'));
                } catch (\THCFrame\Security\Exception\WeakPassword $ex) {
                    $errors['password'] = array($this->lang('PASS_WEAK'));
                } catch (\THCFrame\Security\Exception\PasswordInHistory $ex){
                    $errors['password'] = array($this->lang('PASS_IN_HISTORY'));
                }
            }

            $user->firstname = RequestMethods::post('firstname');
            $user->lastname = RequestMethods::post('lastname');
            $user->email = RequestMethods::post('email');
            $user->phoneNumber = RequestMethods::post('phone');
            $user->getNewActionNotification = RequestMethods::post('actionNotification');
            $user->getNewReportNotification = RequestMethods::post('reportNotification');
            $user->role = RequestMethods::post('role', $user->getRole());
            $user->active = RequestMethods::post('active');
            $user->blocked = RequestMethods::post('blocked');

            if (empty($errors) && $user->validate()) {
                $user->update();

                Event::fire('admin.log', array('success', 'User id: '.$id));
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                self::redirect('/admin/user/');
            } else {
                Event::fire('admin.log', array('fail', 'User id: '.$id,
                    'Errors: '.json_encode($errors + $user->getErrors()), ));
                $view->set('errors', $errors + $user->getErrors());
            }
        }
    }

    /**
     * Delete existing user.
     * 
     * @before _secured, _admin
     *
     * @param int $id user id
     */
    public function delete($id)
    {
        $this->_disableView();

        $user = \App\Model\UserModel::first(array('id = ?' => (int) $id));

        if (null === $user) {
            echo $this->lang('NOT_FOUND');
        } else {
            $user->deleted = true;

            if ($user->validate()) {
                $user->save();
                Event::fire('admin.log', array('success', 'User id: '.$id));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'User id: '.$id));
                echo $this->lang('COMMON_FAIL');
            }
        }
    }

    /**
     * Show help for user section.
     * 
     * @before _secured, _participant
     */
    public function help()
    {
    }

    /**
     * Generate new password and send it to the user.
     * 
     * @before _secured, _admin
     *
     * @param int $id user id
     */
    public function forcePasswordReset($id)
    {
        $this->_disableView();
        $view = $this->getActionView();
        
        $user = \App\Model\UserModel::first(array('id = ?' => (int) $id));

        if (null === $user) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            $this->_willRenderActionView = false;
            self::redirect('/admin/user/');
        } elseif ($user->getRole() == 'role_superadmin' && $this->getUser()->getRole() != 'role_superadmin') {
            $view->warningMessage($this->lang('LOW_PERMISSIONS'));
            $this->_willRenderActionView = false;
            self::redirect('/admin/user/');
        }

        try {
            $newPass = $user->forceResetPassword();

            if ($newPass !== false) {
                $data = array('{NEWPASS}' => $newPass);
                $email = \Admin\Model\EmailModel::loadAndPrepare('password-reset', $data);
                $email->setRecipient($user->getEmail())
                        ->send();

                $view->successMessage($this->lang('PASS_RESET_EMAIL'));
                Event::fire('admin.log', array('success', 'Force password change for user: '.$user->getId()));
                self::redirect('/admin/user/');
            } else {
                $view->warningMessage($this->lang('COMMON_FAIL'));
                Event::fire('admin.log', array('fail', 'Force password change for user: '.$user->getId(),
                    'Errors: '.json_encode($user->getErrors()), ));
                self::redirect('/admin/user/');
            }
        } catch (\Exception $ex) {
            $view->errorMessage($this->lang('UNKNOW_ERROR'));
            Event::fire('admin.log', array('fail', 'Force password change for user: '.$user->getId(),
                'Errors: '.$ex->getMessage(), ));
            self::redirect('/admin/user/');
        }
    }
    
    /**
     * Activate user account and send email notification
     * 
     * @before _secured, _admin
     * 
     * @param type $id
     */
    public function accountActivation($id)
    {
        $this->_disableView();
        $view = $this->getActionView();
        
        $user = \App\Model\UserModel::first(array('id = ?' => (int) $id));

        if (null === $user) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            $this->_willRenderActionView = false;
            self::redirect('/admin/user/');
        }
        
        $user->active = 1;
        
        try {
            if ($user->validate()) {
                $user->update();
                
                $email = \Admin\Model\EmailModel::loadAndPrepare('user-account-activation-notification');
                $email->setRecipient($user->getEmail())
                        ->send(false, 'registrace@hastrman.cz');

                Event::fire('admin.log', array('success', 'Activate User id: '.$id));
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
            } else {
                Event::fire('admin.log', array('fail', 'Activate User id: '.$id,
                    'Validation Errors: '.json_encode($user->getErrors()), ));
                $view->errorMessage($this->lang('UNKNOW_ERROR'));
            }
            
            self::redirect('/admin/user/');
        } catch (\Exception $ex) {
            $view->errorMessage($this->lang('UNKNOW_ERROR'));
            Event::fire('admin.log', array('fail', 'Activate User id: '.$user->getId(),
                'Send email Errors: '.$ex->getMessage(), ));
            self::redirect('/admin/user/');
        }
    }
}

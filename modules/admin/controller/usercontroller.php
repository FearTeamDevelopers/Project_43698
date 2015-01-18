<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Security\PasswordManager;

/**
 * 
 */
class UserController extends Controller
{

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
                $view->set('account_error', 'Není zadán email');
                $error = true;
            }

            if (empty($password)) {
                $view->set('account_error', 'Není zadáno heslo');
                $error = true;
            }

            if (!$error) {
                try {
                    $security = Registry::get('security');
                    $status = $security->authenticate($email, $password);

                    if ($status === true) {
                        self::redirect('/admin/');
                    } else {
                        $view->set('account_error', 'Email a/nebo heslo není správně');
                    }
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
        $security = Registry::get('security');
        $security->logout();
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
                        array('id', 'firstname', 'lastname', 'email', 'role', 'active', 'created'), 
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

        $view->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddUser')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
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

            $salt = PasswordManager::createSalt();
            $hash = PasswordManager::hashPassword(RequestMethods::post('password'), $salt);

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
                $view->successMessage('Uživatel' . self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/user/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $errors + $user->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
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
            if ($this->checkCSRFToken() !== true) {
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

            $pass = RequestMethods::post('password');

            if (null === $pass || $pass == '') {
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

                Event::fire('admin.log', array('success', 'User id: ' . $user->getId()));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/');
            } else {
                Event::fire('admin.log', array('fail', 'User id: ' . $user->getId()));
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
            if ($this->checkCSRFToken() !== true) {
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

            $pass = RequestMethods::post('password');

            if (null === $pass || $pass == '') {
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
            $user->role = RequestMethods::post('role', $user->getRole());
            $user->active = RequestMethods::post('active');

            if (empty($errors) && $user->validate()) {
                $user->save();

                Event::fire('admin.log', array('success', 'User id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/user/');
            } else {
                Event::fire('admin.log', array('fail', 'User id: ' . $id));
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
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $user = \App\Model\UserModel::first(array('id = ?' => $id));

        if (NULL === $user) {
            echo self::ERROR_MESSAGE_2;
        } else {
            $pathMain = $user->getUnlinkPath();
            $pathThumb = $user->getUnlinkThumbPath();

            if ($user->delete()) {
                @unlink($pathMain);
                @unlink($pathThumb);
                Event::fire('admin.log', array('success', 'User id: ' . $id));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'User id: ' . $id));
                echo self::ERROR_MESSAGE_1;
            }
        }
    }

}

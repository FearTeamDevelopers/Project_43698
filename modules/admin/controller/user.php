<?php

use Admin\Etc\Controller;
use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Filesystem\FileManager;
use THCFrame\Security\PasswordManager;

/**
 * Description of Admin_Controller_User
 *
 * @author Tomy
 */
class Admin_Controller_User extends Controller
{

    /**
     * 
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
     * 
     */
    public function logout()
    {
        $security = Registry::get('security');
        $security->logout();
        self::redirect('/admin/');
    }

    /**
     * @before _secured, _admin
     */
    public function index()
    {
        $view = $this->getActionView();
        $security = Registry::get('security');

        $superAdmin = $security->isGranted('role_superadmin');

        $users = App_Model_User::all(
                        array('role <> ?' => 'role_superadmin'), array('id', 'firstname', 'lastname', 'email', 'role', 'active', 'created'), array('id' => 'asc')
        );

        $view->set('users', $users)
                ->set('superadmin', $superAdmin);
    }

    /**
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

            $email = App_Model_User::first(array('email = ?' => RequestMethods::post('email')), array('email'));

            if ($email) {
                $errors['email'] = array('Tento email se již používá');
            }

            $salt = PasswordManager::createSalt();
            $hash = PasswordManager::hashPassword(RequestMethods::post('password'), $salt);

            $cfg = Registry::get('configuration');

            $fileManager = new FileManager(array(
                'thumbWidth' => $cfg->thumb_width,
                'thumbHeight' => $cfg->thumb_height,
                'thumbResizeBy' => $cfg->thumb_resizeby,
                'maxImageWidth' => $cfg->photo_maxwidth,
                'maxImageHeight' => $cfg->photo_maxheight
            ));

            $photoNameRaw = RequestMethods::post('firstname') . '-' . RequestMethods::post('lastname');
            $photoName = $this->_createUrlKey($photoNameRaw);

            $fileErrors = $fileManager->uploadBase64Image(RequestMethods::post('croppedimage'), $photoName, 'members', time() . '_')->getUploadErrors();
            $files = $fileManager->getUploadedFiles();

            if (!empty($fileErrors)) {
                $errors['croppedimage'] = $fileErrors;
            }

            if (!empty($files)) {
                foreach ($files as $i => $file) {
                    if ($file instanceof \THCFrame\Filesystem\Image) {
                        $user = new App_Model_User(array(
                            'firstname' => RequestMethods::post('firstname'),
                            'lastname' => RequestMethods::post('lastname'),
                            'email' => RequestMethods::post('email'),
                            'profile' => RequestMethods::post('text'),
                            'password' => $hash,
                            'salt' => $salt,
                            'role' => RequestMethods::post('role', 'role_member'),
                            'imgMain' => trim($file->getFilename(), '.'),
                            'imgThumb' => trim($file->getThumbname(), '.')
                        ));

                        break;
                    }
                }
            }

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
     * @before _secured, _participant
     */
    public function updateProfile()
    {
        $view = $this->getActionView();

        $user = App_Model_User::first(
                        array('active = ?' => true, 'id = ?' => $this->getUser()->getId()));

        if (NULL === $user) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
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

            if ($user->imgMain == '') {
                $cfg = Registry::get('configuration');

                $fileManager = new FileManager(array(
                    'thumbWidth' => $cfg->thumb_width,
                    'thumbHeight' => $cfg->thumb_height,
                    'thumbResizeBy' => $cfg->thumb_resizeby,
                    'maxImageWidth' => $cfg->photo_maxwidth,
                    'maxImageHeight' => $cfg->photo_maxheight
                ));

                $photoNameRaw = RequestMethods::post('firstname') . '-' . RequestMethods::post('lastname');
                $photoName = $this->_createUrlKey($photoNameRaw);

                $fileErrors = $fileManager->uploadBase64Image(RequestMethods::post('croppedimage'), $photoName, 'members', time() . '_')->getUploadErrors();
                $files = $fileManager->getUploadedFiles();

                if (!empty($files)) {
                    foreach ($files as $i => $file) {
                        if ($file instanceof \THCFrame\Filesystem\Image) {
                            $imgMain = trim($file->getFilename(), '.');
                            $imgThumb = trim($file->getThumbname(), '.');
                            break;
                        }
                    }
                } else {
                    $errors['croppedimage'] = $fileErrors;
                }
            } else {
                $imgMain = $user->imgMain;
                $imgThumb = $user->imgThumb;
            }

            $user->firstname = RequestMethods::post('firstname');
            $user->lastname = RequestMethods::post('lastname');
            $user->email = RequestMethods::post('email');
            $user->profile = RequestMethods::post('text');
            $user->password = $hash;
            $user->salt = $salt;
            $user->imgMain = $imgMain;
            $user->imgThumb = $imgThumb;

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
     * @before _secured, _admin
     * @param type $id
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $user = App_Model_User::first(array('id = ?' => (int) $id));

        if (NULL === $user) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/user/');
        } elseif ($user->role == 'role_superadmin' && $this->getUser()->getRole() != 'role_superadmin') {
            $view->errorMessage(self::ERROR_MESSAGE_4);
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

            if ($user->imgMain == '') {
                $cfg = Registry::get('configuration');

                $fileManager = new FileManager(array(
                    'thumbWidth' => $cfg->thumb_width,
                    'thumbHeight' => $cfg->thumb_height,
                    'thumbResizeBy' => $cfg->thumb_resizeby,
                    'maxImageWidth' => $cfg->photo_maxwidth,
                    'maxImageHeight' => $cfg->photo_maxheight
                ));

                $photoNameRaw = RequestMethods::post('firstname') . '-' . RequestMethods::post('lastname');
                $photoName = $this->_createUrlKey($photoNameRaw);

                $fileErrors = $fileManager->uploadBase64Image(RequestMethods::post('croppedimage'), $photoName, 'members', time() . '_')->getUploadErrors();
                $files = $fileManager->getUploadedFiles();

                if (!empty($files)) {
                    foreach ($files as $i => $file) {
                        if ($file instanceof \THCFrame\Filesystem\Image) {
                            $imgMain = trim($file->getFilename(), '.');
                            $imgThumb = trim($file->getThumbname(), '.');
                            break;
                        }
                    }
                } else {
                    $errors['croppedimage'] = $fileErrors;
                }
            } else {
                $imgMain = $user->imgMain;
                $imgThumb = $user->imgThumb;
            }

            $user->firstname = RequestMethods::post('firstname');
            $user->lastname = RequestMethods::post('lastname');
            $user->email = RequestMethods::post('email');
            $user->profile = RequestMethods::post('text');
            $user->password = $hash;
            $user->salt = $salt;
            $user->imgMain = $imgMain;
            $user->imgThumb = $imgThumb;
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
     * 
     * @before _secured, _superadmin
     * @param type $id
     */
    public function delete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $user = App_Model_User::first(array('id = ?' => $id));

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

    /**
     * @before _secured, _participant
     */
    public function deleteUserMainPhoto($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkCSRFToken()) {
            if ($this->_security->isGranted('role_admin')) {
                $user = App_Model_User::first(array('id = ?' => (int) $id));
            } else {
                $user = App_Model_User::first(array('id = ?' => $this->getUser()->getId()));
            }

            if ($user === null) {
                echo self::ERROR_MESSAGE_2;
            } else {
                $unlinkMainImg = $user->getUnlinkPath();
                $unlinkThumbImg = $user->getUnlinkThumbPath();
                $user->imgMain = '';
                $user->imgThumb = '';

                if ($user->validate()) {
                    $user->save();
                    @unlink($unlinkMainImg);
                    @unlink($unlinkThumbImg);

                    Event::fire('admin.log', array('success', 'User id: ' . $user->getId()));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'User id: ' . $user->getId()));
                    echo self::ERROR_MESSAGE_1;
                }
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

}

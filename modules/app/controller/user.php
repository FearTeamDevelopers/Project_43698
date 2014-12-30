<?php

use App\Etc\Controller as Controller;
use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;
use THCFrame\Filesystem\FileManager;
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

        $this->getLayoutView()->set('metatitle', 'Hastrman - Přihlásit se')
                ->set('canonical', $canonical);

        if (RequestMethods::post('submitLogin')) {
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
                        self::redirect('/');
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

        $canonical = 'http://' . $this->getServerHost() . '/registrace';

        $this->getLayoutView()->set('metatitle', 'Hastrman - Registrace')
                ->set('canonical', $canonical);

        if (RequestMethods::post('register')) {
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

            $salt = PasswordManager::createSalt();
            $hash = PasswordManager::hashPassword(RequestMethods::post('password'), $salt);
            
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
                            'role' => 'role_member',
                            'imgMain' => trim($file->getFilename(), '.'),
                            'imgThumb' => trim($file->getThumbname(), '.')
                        ));

                        break;
                    }
                }
            }

            if (empty($errors) && $user->validate()) {
                $user->save();

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
                $this->getSecurity()->setUser($user);

                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/muj-profil');
            } else {
                $view->set('errors', $errors + $user->getErrors());
            }
        }
    }

    /**
     * @before _secured, _member
     */
    public function deleteUserMainPhoto()
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkCSRFToken()) {
            $user = App_Model_User::first(array('id = ?' => (int) $this->getUser()->getId()));

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

                    echo 'success';
                } else {
                    echo self::ERROR_MESSAGE_1;
                }
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

}

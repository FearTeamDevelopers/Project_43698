<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;

/**
 *
 */
class UserController extends Controller
{

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
                            $view->infoMessage($this->lang('PASS_EXPIRATION', [$daysToExpiration]));
                        } elseif ($daysToExpiration < 5 && $daysToExpiration > 1) {
                            $view->warningMessage($this->lang('PASS_EXPIRATION', [$daysToExpiration]));
                        } elseif ($daysToExpiration <= 1) {
                            $view->errorMessage($this->lang('PASS_EXPIRATION_TOMORROW'));
                        }
                    }

                    self::redirect('/admin/');
                } catch (\Exception $e) {
                    Event::fire('admin.log', ['fail', 'Exception: ' . get_class($e) . ' Message: ' . $e->getMessage()]);
                    $view->set('account_error', $this->lang('LOGIN_COMMON_ERROR'));
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

        if ($this->getUser() !== null && $this->getUser()->getForcePassChange() == true) {
            $view->errorMessage($this->lang('LOGOUT_PASS_EXP_CHECK'));
            $this->getUser()
                    ->setForcePassChange(false)
                    ->update();
            self::redirect('/admin/user/profile/');
        }

        $this->disableView();

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
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                    $this->checkMultiSubmissionProtectionToken() !== true) {
                self::redirect('/admin/user/');
            }
            
            list($user, $errors) = \App\Model\UserModel::createUser(RequestMethods::getPostDataBag(), ['checkForRole' => true]);

            if (empty($errors) && $user->validate()) {
                $userId = $user->save();

                Event::fire('admin.log', ['success', 'User id: ' . $userId]);
                $view->successMessage($this->lang('CREATE_SUCCESS'));
                self::redirect('/admin/user/');
            } else {
                Event::fire('admin.log', ['fail', 'Errors: ' . json_encode($errors + $user->getErrors())]);
                $view->set('errors', $errors + $user->getErrors())
                        ->set('submstoken', $this->revalidateMultiSubmissionProtectionToken())
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
                        ['active = ?' => true, 'id = ?' => $this->getUser()->getId()]);

        if (null === $user) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            $this->willRenderActionView = false;
            self::redirect('/admin/user/');
        }

        $view->set('user', $user)
                ->set('roles', \App\Model\UserModel::getAllRoles());

        if (RequestMethods::post('submitUpdateProfile')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/admin/user/');
            }
            
            list($user, $errors) = \App\Model\UserModel::editUserProfile(RequestMethods::getPostDataBag(), $user);

            if (empty($errors) && $user->validate()) {
                $user->save();
                $this->getSecurity()->setUser($user);

                Event::fire('admin.log', ['success', 'User id: ' . $user->getId()]);
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                self::redirect('/admin/');
            } else {
                Event::fire('admin.log', ['fail', 'User id: ' . $user->getId(),
                    'Errors: ' . json_encode($errors + $user->getErrors()),]);
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
        $user = \App\Model\UserModel::first(['id = ?' => (int) $id]);

        if (null === $user) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            $this->willRenderActionView = false;
            self::redirect('/admin/user/');
        } elseif ($user->getRole() == 'role_superadmin' && $this->getUser()->getRole() != 'role_superadmin') {
            $view->warningMessage($this->lang('LOW_PERMISSIONS'));
            $this->willRenderActionView = false;
            self::redirect('/admin/user/');
        } elseif ($user->pdLimitProcessing == 1) {
            $view->warningMessage($this->lang('ACCOUNT_PROCESSING_DISABLED'));
            $this->willRenderActionView = false;
            self::redirect('/admin/user/');
        }

        $view->set('user', $user)
                ->set('roles', \App\Model\UserModel::getAllRoles());

        if (RequestMethods::post('submitEditUser')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/admin/user/');
            }

            $errors = [];

            if (RequestMethods::post('password') !== RequestMethods::post('password2')) {
                $errors['password2'] = [$this->lang('PASS_DOESNT_MATCH')];
            }

            if (RequestMethods::post('email') != $user->email) {
                $email = \App\Model\UserModel::first(
                                ['email = ?' => RequestMethods::post('email', $user->email)], ['email']
                );

                if ($email) {
                    $errors['email'] = [$this->lang('EMAIL_IS_TAKEN')];
                }
            }

            $oldPassword = RequestMethods::post('oldpass');
            $newPassword = RequestMethods::post('password');

            $user->firstname = RequestMethods::post('firstname');
            $user->lastname = RequestMethods::post('lastname');
            $user->email = RequestMethods::post('email');
            $user->phoneNumber = RequestMethods::post('phone');
            $user->getNewActionNotification = RequestMethods::post('actionNotification');
            $user->getNewReportNotification = RequestMethods::post('reportNotification');
            $user->getNewNewsNotification = RequestMethods::post('newsNotification');
            $user->role = RequestMethods::post('role', $user->getRole());
            $user->active = RequestMethods::post('active');
            $user->blocked = RequestMethods::post('blocked');
            $user->pdLimitProcessing = RequestMethods::post('pdLimitProcessing', 0);


            if ($this->isSuperAdmin() && !empty($newPassword)) {
                try {
                    $user = $user->forceResetPassword($newPassword);
                } catch (\THCFrame\Security\Exception\WeakPassword $ex) {
                    $errors['password'] = [$this->lang('PASS_WEAK')];
                }
            } elseif (!empty($oldPassword) && !empty($newPassword)) {
                try {
                    $user = $user->changePassword($oldPassword, $newPassword);
                } catch (\THCFrame\Security\Exception\WrongPassword $ex) {
                    $errors['oldpass'] = [$this->lang('PASS_ORIGINAL_NOT_CORRECT')];
                } catch (\THCFrame\Security\Exception\WeakPassword $ex) {
                    $errors['password'] = [$this->lang('PASS_WEAK')];
                } catch (\THCFrame\Security\Exception\PasswordInHistory $ex) {
                    $errors['password'] = [$this->lang('PASS_IN_HISTORY')];
                }
            } elseif (empty($oldPassword) && !empty($newPassword)) {
                $errors['oldpass'] = [$this->lang('PASS_ORIGINAL_NOT_CORRECT')];
            }

            if (empty($errors) && $user->validate()) {
                $user->save();

                Event::fire('admin.log', ['success', 'User id: ' . $id]);
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                self::redirect('/admin/user/');
            } else {
                Event::fire('admin.log', ['fail', 'User id: ' . $id,
                    'Errors: ' . json_encode($errors + $user->getErrors()),]);
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
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $user = \App\Model\UserModel::first(['id = ?' => (int) $id]);

        if (null === $user) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            if (\App\Model\UserModel::deleteUser($id)) {
                Event::fire('admin.log', ['success', 'Delete user id: ' . $id]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log', ['fail', 'Delete user id: ' . $id]);
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
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
        $this->disableView();
        $view = $this->getActionView();

        $user = \App\Model\UserModel::first(['id = ?' => (int) $id]);

        if (null === $user) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            $this->willRenderActionView = false;
            self::redirect('/admin/user/');
        } elseif ($user->getRole() == 'role_superadmin' && $this->getUser()->getRole() != 'role_superadmin') {
            $view->warningMessage($this->lang('LOW_PERMISSIONS'));
            $this->willRenderActionView = false;
            self::redirect('/admin/user/');
        }

        try {
            $user = $user->forceResetPassword();

            if ($user->validate()) {
                $user->save();

                $data = ['{NEWPASS}' => $user->getNewCleanPassword()];
                $user->setNewCleanPassword(null);

                $emailTpl = \Admin\Model\EmailModel::loadAndPrepare('password-reset', $data);

                if ($emailTpl !== null) {
                    $mailer = new \THCFrame\Mailer\Mailer();
                    $mailer->setBody($emailTpl->getBody())
                            ->setSubject($emailTpl->getSubject())
                            ->setSendTo($user->getEmail())
                            ->send();

                    $view->successMessage($this->lang('PASS_RESET_EMAIL'));
                    Event::fire('admin.log', ['success', 'Force password change for user: ' . $user->getId()]);
                } else {
                    $view->errorMessage($this->lang('COMMON_FAIL'));
                    Event::fire('admin.log', ['fail', 'Email template not found']);
                }
                self::redirect('/admin/user/');
            } else {
                $view->errorMessage($this->lang('COMMON_FAIL'));
                Event::fire('admin.log', ['fail', 'Force password change for user: ' . $user->getId(),
                    'Errors: ' . json_encode($user->getErrors()),]);
                self::redirect('/admin/user/');
            }
        } catch (\Exception $ex) {
            $view->errorMessage($this->lang('UNKNOW_ERROR'));
            Event::fire('admin.log', ['fail', 'Force password change for user: ' . $user->getId(),
                'Exception: ' . $ex->getMessage(),]);
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
        $this->disableView();
        $view = $this->getActionView();

        $user = \App\Model\UserModel::first(['id = ?' => (int) $id]);

        if (null === $user) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            $this->willRenderActionView = false;
            self::redirect('/admin/user/');
        }

        $user->active = 1;

        try {
            if ($user->activateAccount()) {
                $emailTpl = \Admin\Model\EmailModel::loadAndPrepare('user-account-activation-notification');

                if ($emailTpl !== null) {
                    $mailer = new \THCFrame\Mailer\Mailer();
                    $mailer->setBody($emailTpl->getBody())
                            ->setSubject($emailTpl->getSubject())
                            ->setSendTo($user->getEmail())
                            ->setFrom('registrace@hastrman.cz')
                            ->send();

                    Event::fire('admin.log', ['success', 'Activate User id: ' . $id]);
                    $view->successMessage($this->lang('UPDATE_SUCCESS'));
                } else {
                    $view->errorMessage($this->lang('COMMON_FAIL'));
                    Event::fire('admin.log', ['fail', 'Email template not found']);
                }
            } else {
                Event::fire('admin.log', ['fail', 'Activate User id: ' . $id,
                    'Validation Errors: ' . json_encode($user->getErrors()),]);
                $view->errorMessage($this->lang('UNKNOW_ERROR'));
            }

            self::redirect('/admin/user/');
        } catch (\Exception $ex) {
            $view->errorMessage($this->lang('UNKNOW_ERROR'));
            Event::fire('admin.log', ['fail', 'Activate User id: ' . $user->getId(),
                'Send email Errors: ' . $ex->getMessage(),]);
            self::redirect('/admin/user/');
        }
    }

    /**
     * Force delete user from database
     *
     * @param string $email
     * @before _secured, _superadmin
     */
    public function forceUserDelete($email)
    {
        $this->disableView();
        if (strtolower(ENV) == 'live') {
            self::redirect('/admin/');
        }

        $view = $this->getActionView();
        $user = \App\Model\UserModel::first(['email = ?' => $email]);

        if ($user !== null) {
            if (\App\Model\UserModel::deleteAll(['id = ?' => $user->getId()]) != -1) {
                Event::fire('admin.log', ['success', 'Delete User id: ' . $user->getId()]);
                $view->successMessage('User ' . $email . ' has been deleted');
                self::redirect('/admin/user/');
            } else {
                Event::fire('admin.log', ['fail', 'Delete User id: ' . $user->getId()]);
                $view->errorMessage('An error occured while deleting user ' . $email . ' from database');
                self::redirect('/admin/user/');
            }
        } else {
            Event::fire('admin.log', ['fail', 'Delete User id: ' . $user->getId()]);
            $view->errorMessage('User ' . $email . ' not found');
            self::redirect('/admin/user/');
        }
    }

    /**
     * Create api token for user
     *
     * @param type $userId
     * @before _secured, _superadmin
     */
    public function generateApiToken($userId)
    {
        $this->disableView();
        $view = $this->getActionView();
        $user = \App\Model\UserModel::first(['id = ?' => (int) $userId]);

        if ($user !== null) {
            $userHasToken = \THCFrame\Security\Model\ApiTokenModel::first(['userId = ?' => $user->getId()]);

            if ($userHasToken !== null) {
                $view->warningMessage($this->lang('API_TOKEN_ALREADY_EXISTS'));
                self::redirect('/admin/user/');
            } else {
                $token = \THCFrame\Security\Model\ApiTokenModel::generateToken();

                $newToken = new \THCFrame\Security\Model\ApiTokenModel([
                    'userId' => (int) $userId,
                    'token' => $token
                ]);

                if ($newToken->validate()) {
                    $newToken->save();

                    Event::fire('admin.log', ['success', 'New token for user id: ' . $user->getId()]);
                    $view->successMessage($this->lang('API_TOKEN_CREATED'));
                    self::redirect('/admin/user/');
                } else {
                    Event::fire('admin.log', ['fail', 'New token for user id: ' . $user->getId(),
                        'Validation Errors: ' . json_encode($newToken->getErrors())]);
                    $view->errorMessage($this->lang('CREATE_FAIL'));
                    self::redirect('/admin/user/');
                }
            }
        } else {
            Event::fire('admin.log', ['fail', 'User id: ' . (int) $userId . ' not found']);
            $view->errorMessage($this->lang('NOT_FOUND'));
            self::redirect('/admin/user/');
        }
    }

    /**
     * Delete user api token
     *
     * @param type $userId
     * @before _secured, _superadmin
     */
    public function deleteApiToken($userId)
    {
        $this->disableView();
        $view = $this->getActionView();
        $token = \THCFrame\Security\Model\ApiTokenModel::first(['userId = ?' => (int) $userId]);

        if ($token !== null) {
            if ($token->delete()) {
                Event::fire('admin.log', ['success', 'Delete token for user id: ' . (int) $userId]);
                $view->successMessage($this->lang('API_TOKEN_DELETED'));
                self::redirect('/admin/user/');
            } else {
                Event::fire('admin.log', ['fail', 'Delete token for user id: ' . (int) $userId,
                    'Validation Errors: ' . json_encode($token->getErrors())]);
                $view->errorMessage($this->lang('DELETE_FAIL'));
                self::redirect('/admin/user/');
            }
        } else {
            Event::fire('admin.log', ['fail', 'Token for user id: ' . (int) $userId . ' not found']);
            $view->errorMessage($this->lang('NOT_FOUND'));
            self::redirect('/admin/user/');
        }
    }

}

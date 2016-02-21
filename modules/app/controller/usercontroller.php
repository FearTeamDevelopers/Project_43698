<?php

namespace App\Controller;

use App\Etc\Controller as Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Security\PasswordManager;
use THCFrame\Events\Events as Event;
use THCFrame\Core\Rand;
use THCFrame\Registry\Registry;
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
     * App module login.
     */
    public function login()
    {
        $view = $this->getActionView();

        $canonical = 'http://' . $this->getServerHost() . '/prihlasit';

        $this->getLayoutView()
                ->set('metatitle', 'Hastrman - Přihlásit se')
                ->set('canonical', $canonical);

        if (RequestMethods::post('submitLogin')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/prihlasit');
            }

            $email = RequestMethods::post('email');
            $password = RequestMethods::post('password');
            $error = false;

            if (empty($email)) {
                $view->set('email_error', $this->lang('LOGIN_EMAIL_ERROR'));
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

                    self::redirect('/muj-profil');
                } catch (\THCFrame\Security\Exception\UserBlocked $ex) {
                    $view->set('account_error', $this->lang('LOGIN_COMMON_ERROR'));
                    Event::fire('app.log', array('fail', sprintf('Account locked for %s', $email)));
                } catch (\THCFrame\Security\Exception\UserInactive $ex) {
                    $view->set('account_error', $this->lang('LOGIN_COMMON_ERROR'));
                    Event::fire('app.log', array('fail', sprintf('Account inactive for %s', $email)));
                } catch (\THCFrame\Security\Exception\UserExpired $ex) {
                    $view->set('account_error', $this->lang('LOGIN_COMMON_ERROR'));
                    Event::fire('app.log', array('fail', sprintf('Account expired for %s', $email)));
                } catch (\THCFrame\Security\Exception\UserNotExists $ex) {
                    $view->set('account_error', $this->lang('LOGIN_COMMON_ERROR'));
                    Event::fire('app.log', array('fail', sprintf('User %s does not exists', $email)));
                } catch (\THCFrame\Security\Exception\WrongPassword $ex) {
                    $view->set('account_error', $this->lang('LOGIN_COMMON_ERROR'));
                    Event::fire('app.log', array('fail', sprintf('Wrong password provided for user %s', $email)));
                } catch (\THCFrame\Security\Exception\UserPassExpired $ex) {
                    $view->set('account_error', $this->lang('LOGIN_COMMON_ERROR'));
                    Event::fire('app.log', array('fail', sprintf('Password has expired for user %s', $email)));
                } catch (\Exception $e) {
                    Event::fire('app.log', array('fail', 'Exception: ' . $e->getMessage()));

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
     * App module logout.
     */
    public function logout()
    {
        $view = $this->getActionView();

        if ($this->getUser() !== null && $this->getUser()->getForcePassChange() === true) {
            $view->errorMessage($this->lang('LOGOUT_PASS_EXP_CHECK'));
            $this->getUser()
                    ->setForcePassChange(false)
                    ->update();
            self::redirect('/muj-profil');
            exit;
        }

        $this->disableView();

        $this->getSecurity()->logout();
        self::redirect('/');
    }

    /**
     * Registration. Create only members without access into administration.
     */
    public function registration()
    {
        $view = $this->getActionView();
        $user = null;

        $canonical = 'http://' . $this->getServerHost() . '/registrace';

        $view->set('user', $user);

        $this->getLayoutView()
                ->set('metatitle', 'Hastrman - Registrace')
                ->set('canonical', $canonical);

        if (RequestMethods::post('register')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                    $this->checkMutliSubmissionProtectionToken() !== true) {
                self::redirect('/');
            }
            $errors = array();

            if (RequestMethods::post('password') !== RequestMethods::post('password2')) {
                $errors['password2'] = array($this->lang('PASS_DOESNT_MATCH'));
            }

            $email = \App\Model\UserModel::first(
                            array('email = ?' => RequestMethods::post('email')), array('email')
            );

            if ($email) {
                $errors['email'] = array($this->lang('EMAIL_IS_TAKEN'));
            }

            if (strlen(RequestMethods::post('password')) < 5 || PasswordManager::strength(RequestMethods::post('password')) <= \App\Model\UserModel::MEMBER_PASS_STRENGHT) {
                $errors['password'] = array($this->lang('PASS_WEAK'));
            }

            $salt = PasswordManager::createSalt();
            $hash = PasswordManager::hashPassword(RequestMethods::post('password'), $salt);
            $cleanHash = StringMethods::getHash(RequestMethods::post('password'));
            $verifyEmail = $this->getConfig()->registration_verif_email;
            $adminAccountActivation = $this->getConfig()->registration_admin_activate;

            if ($adminAccountActivation) {
                $active = false;
            } else {
                if ($verifyEmail) {
                    $active = false;
                } else {
                    $active = true;
                }
            }

            $actToken = Rand::randStr(50);
            for ($i = 1; $i <= 75; $i+=1) {
                if ($this->_checkEmailActToken($actToken)) {
                    break;
                } else {
                    $actToken = Rand::randStr(50);
                }

                if ($i == 75) {
                    $errors['email'] = array($this->lang('UNKNOW_ERROR') . $this->lang('REGISTRATION_FAIL'));
                    break;
                }
            }

            $user = new \App\Model\UserModel(array(
                'firstname' => RequestMethods::post('firstname'),
                'lastname' => RequestMethods::post('lastname'),
                'email' => RequestMethods::post('email'),
                'phoneNumber' => RequestMethods::post('phone'),
                'password' => $hash,
                'passwordHistory1' => $cleanHash,
                'salt' => $salt,
                'role' => 'role_member',
                'active' => $active,
                'emailActivationToken' => $actToken,
                'getNewActionNotification' => RequestMethods::post('actionNotification'),
                'getNewReportNotification' => RequestMethods::post('reportNotification'),
            ));

            if (empty($errors) && $user->validate()) {
                $uid = $user->save();

                $mailer = new \THCFrame\Mailer\Mailer();

                //odeslani notifikace administratorum, ze byl zaregistrovan novy uzivatel
                if ($adminAccountActivation) {
                    $admins = \App\Model\UserModel::fetchAdminsEmail();

                    $data = array('{USERNAME}' => $user->getWholeName(), '{USEREMAIL}' => $user->getEmail());
                    $emailTpl = \Admin\Model\EmailModel::loadAndPrepare('new-registration-notification', $data);
                    $mailer->setBody($emailTpl->getBody())
                            ->setBody($emailTpl->getSubject())
                            ->setFrom('registrace@hastrman.cz')
                            ->setSendTo($admins);

                    if ($mailer->send()) {
                        Event::fire('app.log', array('success', 'Notification email about new registration to admin'));
                        $view->successMessage($this->lang('REGISTRATION_SUCCESS_ADMIN_ACTIVATION'));
                    } else {
                        $user->delete();
                        Event::fire('app.log', array('fail', 'Notification email about new registration to admin'));
                        $view->errorMessage($this->lang('REGISTRATION_FAIL'));
                    }
                } elseif ($verifyEmail) { //odeslani overovaciho emailu
                    $data = array('{TOKEN}' => $actToken);
                    $emailTpl = \Admin\Model\EmailModel::loadAndPrepare('email-verification', $data);
                    $mailer->setBody($emailTpl->getBody())
                            ->setBody($emailTpl->getSubject())
                            ->setFrom('registrace@hastrman.cz')
                            ->setSendTo($user->getEmail());

                    if ($mailer->send()) {
                        Event::fire('app.log', array('success', 'User Id with email activation: ' . $uid));
                        $view->successMessage($this->lang('REGISTRATION_EMAIL_SUCCESS'));
                    } else {
                        $user->delete();
                        Event::fire('app.log', array('fail', 'Email not send for User Id: ' . $uid));
                        $view->errorMessage($this->lang('REGISTRATION_EMAIL_FAIL'));
                    }
                } else {
                    Event::fire('app.log', array('success', 'User Id: ' . $uid));
                    $view->successMessage($this->lang('REGISTRATION_SUCCESS'));
                }

                self::redirect('/');
            } else {
                Event::fire('app.log', array('fail', 'User id: ' . $id,
                    'Errors: ' . json_encode($errors + $user->getErrors()),));
                $view->set('errors', $errors + $user->getErrors())
                        ->set('user', $user);
            }
        }
    }

    /**
     * Edit user currently logged in.
     *
     * @before _secured, _member
     */
    public function profile()
    {
        $view = $this->getActionView();
        $errors = array();

        $canonical = 'http://' . $this->getServerHost() . '/profil';

        $user = \App\Model\UserModel::first(array('id = ?' => $this->getUser()->getId()));
        $myActions = \App\Model\AttendanceModel::fetchActionsByUserId($this->getUser()->getId(), true);

        if (!empty($myActions)) {
            foreach ($myActions as &$action) {
                $action->latestComments = \App\Model\CommentModel::fetchByTypeAndCreated(
                                \App\Model\CommentModel::RESOURCE_ACTION, $action->getId(), Registry::get('session')->get('userLastLogin')
                );
                unset($action);
            }
        }

        $this->getLayoutView()
                ->set('metatile', 'Hastrman - Můj profil')
                ->set('canonical', $canonical);
        $view->set('user', $user)
                ->set('myactions', $myActions);

        if (RequestMethods::post('editProfile')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/muj-profil');
            }

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
            $newPassword = RequestMethods::post('password');

            if (!empty($oldPassword) && !empty($newPassword)) {
                try {
                    $user = $user->changePassword($oldPassword, $newPassword);
                } catch (\THCFrame\Security\Exception\WrongPassword $ex) {
                    $errors['oldpass'] = array($this->lang('PASS_ORIGINAL_NOT_CORRECT'));
                } catch (\THCFrame\Security\Exception\WeakPassword $ex) {
                    $errors['password'] = array($this->lang('PASS_WEAK'));
                } catch (\THCFrame\Security\Exception\PasswordInHistory $ex) {
                    $errors['password'] = array($this->lang('PASS_IN_HISTORY'));
                }
            } elseif (empty($oldPassword) && !empty($newPassword)) {
                $errors['oldpass'] = array($this->lang('PASS_ORIGINAL_NOT_CORRECT'));
            }

            $user->firstname = RequestMethods::post('firstname');
            $user->lastname = RequestMethods::post('lastname');
            $user->email = RequestMethods::post('email');
            $user->phoneNumber = RequestMethods::post('phone');
            $user->getNewActionNotification = RequestMethods::post('actionNotification');
            $user->getNewReportNotification = RequestMethods::post('reportNotification');

            if (empty($errors) && $user->validate()) {
                $user->save();
                $this->getSecurity()->setUser($user);

                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                self::redirect('/muj-profil');
            } else {
                Event::fire('app.log', array('fail', 'User id: ' . $user->getId(),
                    'Errors: ' . json_encode($errors + $user->getErrors()),));
                $view->set('errors', $errors + $user->getErrors());
            }
        }
    }

    /**
     * Activate account via activation link send by email.
     *
     * @param string $key activation token
     */
    public function activateAccount($key)
    {
        $view = $this->getActionView();

        $user = \App\Model\UserModel::first(array('active = ?' => false, 'emailActivationToken = ?' => $key));
        $adminAccountActivation = $this->getConfig()->registration_admin_activate;

        if (null === $user) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            self::redirect('/');
        }

        if ($adminAccountActivation) {
            $view->infoMessage($this->lang('REGISTRATION_WAITING_ADMIN_ACTIVATION'));
            self::redirect('/');
        }

        if ($user->activateAccount()) {
            Event::fire('app.log', array('success', 'User Id: ' . $user->getId()));
            $view->successMessage($this->lang('ACCOUNT_ACTIVATED'));
            self::redirect('/');
        } else {
            Event::fire('app.log', array('fail', 'User Id: ' . $user->getId(),
                'Errors: ' . json_encode($user->getErrors()),));
            $view->warningMessage($this->lang('COMMON_FAIL'));
            self::redirect('/');
        }
    }

    /**
     * Form for visitors feedback.
     */
    public function feedback()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $canonical = 'http://' . $this->getServerHost() . '/feedback';

        $view->set('feedback', null);
        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Feedback');

        if (RequestMethods::post('submitFeedback')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                    $this->checkMutliSubmissionProtectionToken() !== true) {
                self::redirect('/feedback');
            }

            $userAlias = $this->getUser() !== null ? $this->getUser()->getWholeName() : 'annonymous';
            $feedback = new \App\Model\FeedbackModel(array(
                'userAlias' => $userAlias,
                'message' => RequestMethods::post('message'),
            ));

            if ($feedback->validate()) {
                $id = $feedback->save();

                $data = array('{TITLE}' => 'Hastrman feedback', '{TEXT}' => 'User: ' . $feedback->getUserAlias() . '<br/>Message: ' . $feedback->getMessage());
                $email = \Admin\Model\EmailModel::loadAndPrepare('default-email', $data);
                $email->setSubject('Hastrman - Feedback');

                if ($email->send()) {
                    Event::fire('app.log', array('success', 'Feedback id: ' . $id));
                    $view->successMessage($this->lang('SEND_FEEDBACK_SUCCESS'));
                    self::redirect('/');
                } else {
                    Event::fire('app.log', array('fail', 'Send feedback email: ' . $id));
                    $view->errorMessage($this->lang('SEND_FEEDBACK_FAIL'));
                    self::redirect('/');
                }
            } else {
                Event::fire('app.log', array('fail', 'Errors: ' . json_encode($feedback->getErrors())));
                $view->set('feedback', $feedback)
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('errors', $feedback->getErrors());
            }
        }
    }

    /**
     *
     */
    public function eprivacy()
    {
        $canonical = 'http://' . $this->getServerHost() . '/ochrana-soukromi';

        $this->getLayoutView()
                ->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Ochrana soukromí');
    }

}

<?php
namespace App\Controller;

use App\Etc\Controller as Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\View\View;

/**
 *
 */
class UserController extends Controller
{

    /**
     * App module login.
     */
    public function login()
    {
        $view = $this->getActionView();

        $canonical = $this->getServerHost() . '/prihlasit';

        $this->getLayoutView()
            ->set(View::META_TITLE, 'Hastrman - Přihlásit se')
            ->set(View::META_CANONICAL, $canonical);

        if (RequestMethods::post('submitLogin')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true ||
                $this->checkMultiSubmissionProtectionToken() !== true ||
                $this->checkBrowserAgentAndReferer()) {
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
                            $view->infoMessage($this->lang('PASS_EXPIRATION', [$daysToExpiration]));
                        } elseif ($daysToExpiration < 5 && $daysToExpiration > 1) {
                            $view->warningMessage($this->lang('PASS_EXPIRATION', [$daysToExpiration]));
                        } elseif ($daysToExpiration <= 1) {
                            $view->errorMessage($this->lang('PASS_EXPIRATION_TOMORROW'));
                        }
                    }

                    self::redirect('/muj-profil');
                } catch (\THCFrame\Security\Exception\UserBlocked $ex) {
                    $view->set('account_error', $this->lang('LOGIN_COMMON_ERROR'));
                    Event::fire('app.log', ['fail', sprintf('Account locked for %s', $email)]);
                } catch (\THCFrame\Security\Exception\UserInactive $ex) {
                    $view->set('account_error', $this->lang('LOGIN_COMMON_ERROR'));
                    Event::fire('app.log', ['fail', sprintf('Account inactive for %s', $email)]);
                } catch (\THCFrame\Security\Exception\UserExpired $ex) {
                    $view->set('account_error', $this->lang('LOGIN_COMMON_ERROR'));
                    Event::fire('app.log', ['fail', sprintf('Account expired for %s', $email)]);
                } catch (\THCFrame\Security\Exception\UserNotExists $ex) {
                    $view->set('account_error', $this->lang('LOGIN_COMMON_ERROR'));
                    Event::fire('app.log', ['fail', sprintf('User %s does not exists', $email)]);
                } catch (\THCFrame\Security\Exception\WrongPassword $ex) {
                    $view->set('account_error', $this->lang('LOGIN_COMMON_ERROR'));
                    Event::fire('app.log', ['fail', sprintf('Wrong password provided for user %s', $email)]);
                } catch (\THCFrame\Security\Exception\UserPassExpired $ex) {
                    $view->set('account_error', $this->lang('LOGIN_COMMON_ERROR'));
                    Event::fire('app.log', ['fail', sprintf('Password has expired for user %s', $email)]);
                } catch (\Exception $e) {
                    Event::fire('app.log', ['fail', 'Exception: ' . $e->getMessage()]);

                    if (ENV == 'dev') {
                        $view->set('account_error', $e->getMessage());
                    } else {
                        $view->set('account_error', $this->lang('LOGIN_COMMON_ERROR'));
                    }
                }
            } else {
                $view->set('submstoken', $this->revalidateMultiSubmissionProtectionToken());
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

        $canonical = $this->getServerHost() . '/registrace';

        $view->set('user', $user);

        $this->getLayoutView()
            ->set(View::META_TITLE, 'Hastrman - Registrace')
            ->set(View::META_CANONICAL, $canonical);

        if (RequestMethods::post('register')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true ||
                $this->checkMultiSubmissionProtectionToken() !== true ||
                $this->checkBrowserAgentAndReferer()) {
                self::redirect('/');
            }

            //Bait for bots
            if (RequestMethods::post('url') !== '') {
                return;
            }

            list($user, $errors) = \App\Model\UserModel::createUser(RequestMethods::getPostDataBag(), [
                    'verifyEmail' => $this->getConfig()->registration_verif_email,
                    'adminAccountActivation' => $this->getConfig()->registration_admin_activate]
            );

            if (empty($errors) && $user->validate()) {
                $uid = $user->save();

                $mailer = new \THCFrame\Mailer\Mailer();

                //odeslani notifikace administratorum, ze byl zaregistrovan novy uzivatel
                if ($this->getConfig()->registration_admin_activate) {
                    $admins = \App\Model\UserModel::fetchAdminsEmail();

                    $data = ['{USERNAME}' => $user->getWholeName(), '{USEREMAIL}' => $user->getEmail()];
                    $emailTpl = \Admin\Model\EmailModel::loadAndPrepare('new-registration-notification', $data);
                    if ($emailTpl !== null) {
                        $mailer->setBody($emailTpl->getBody())
                            ->setSubject($emailTpl->getSubject())
                            ->setFrom('registrace@hastrman.cz')
                            ->setSendTo($admins);

                        if ($mailer->send()) {
                            Event::fire('app.log', ['success', 'Notification email about new registration to admin']);
                        } else {
                            Event::fire('app.log', ['fail', 'Notification email about new registration to admin failed']);
                        }

                        $view->successMessage($this->lang('REGISTRATION_SUCCESS_ADMIN_ACTIVATION'));
                    } else {
                        $view->warningMessage($this->lang('COMMON_FAIL'));
                        Event::fire('app.log', ['fail', 'Email template not found']);
                    }
                } elseif ($this->getConfig()->registration_verif_email) { //odeslani overovaciho emailu
                    $data = ['{TOKEN}' => $actToken];
                    $emailTpl = \Admin\Model\EmailModel::loadAndPrepare('email-verification', $data);
                    if ($emailTpl !== null) {
                        $mailer->setBody($emailTpl->getBody())
                            ->setSubject($emailTpl->getSubject())
                            ->setFrom('registrace@hastrman.cz')
                            ->setSendTo($user->getEmail());

                        if ($mailer->send()) {
                            Event::fire('app.log', ['success', 'User Id with email activation: ' . $uid]);
                            $view->successMessage($this->lang('REGISTRATION_EMAIL_SUCCESS'));
                        } else {
                            $user->delete();
                            Event::fire('app.log', ['fail', 'Email not send for User Id: ' . $uid]);
                            $view->errorMessage($this->lang('REGISTRATION_EMAIL_FAIL'));
                        }
                    } else {
                        $user->delete();
                        $view->errorMessage($this->lang('REGISTRATION_EMAIL_FAIL'));
                        Event::fire('app.log', ['fail', 'Email template not found']);
                    }
                } else {
                    Event::fire('app.log', ['success', 'User Id: ' . $uid]);
                    $view->successMessage($this->lang('REGISTRATION_SUCCESS'));
                }

                self::redirect('/');
            } else {
                Event::fire('app.log', ['fail', 'User id: ' . $id,
                    'Errors: ' . json_encode($errors + $user->getErrors()),]);
                $view->set('errors', $errors + $user->getErrors())
                    ->set('submstoken', $this->revalidateMultiSubmissionProtectionToken())
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

        $canonical = $this->getServerHost() . '/muj-profil';

        $user = \App\Model\UserModel::first(['id = ?' => $this->getUser()->getId()]);
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
            ->set(View::META_CANONICAL, $canonical);
        $view->set('user', $user)
            ->set('myactions', $myActions);

        if (RequestMethods::post('editProfile')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/muj-profil');
            }

            list($user, $errors) = \App\Model\UserModel::editUserProfile(RequestMethods::getPostDataBag(), $user);

            if (empty($errors) && $user->validate()) {
                $user->save();
                $this->getSecurity()->setUser($user);

                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                self::redirect('/muj-profil');
            } else {
                Event::fire('app.log', ['fail', 'User id: ' . $user->getId(),
                    'Errors: ' . json_encode($errors + $user->getErrors()),]);
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

        $user = \App\Model\UserModel::first(['active = ?' => false, 'emailActivationToken = ?' => $key]);
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
            Event::fire('app.log', ['success', 'User Id: ' . $user->getId()]);
            $view->successMessage($this->lang('ACCOUNT_ACTIVATED'));
            self::redirect('/');
        } else {
            Event::fire('app.log', ['fail', 'User Id: ' . $user->getId(),
                'Errors: ' . json_encode($user->getErrors()),]);
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

        $canonical = $this->getServerHost() . '/feedback';

        $view->set('feedback', null);
        $layoutView->set(View::META_CANONICAL, $canonical)
            ->set(View::META_TITLE, 'Hastrman - Feedback');

        if (RequestMethods::post('submitFeedback')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true ||
                $this->checkMultiSubmissionProtectionToken() !== true ||
                $this->checkBrowserAgentAndReferer()) {
                self::redirect('/feedback');
            }

            //Bait for bots
            if (RequestMethods::post('url') !== '') {
                return;
            }

            $userAlias = $this->getUser() !== null ? $this->getUser()->getWholeName() : 'annonymous';
            $feedback = new \App\Model\FeedbackModel([
                'userAlias' => $userAlias,
                'message' => RequestMethods::post('message'),
            ]);

            if ($feedback->validate()) {
                $id = $feedback->save();

                $data = ['{TITLE}' => 'Hastrman feedback', '{TEXT}' => 'User: ' . $feedback->getUserAlias() . '<br/>Message: ' . $feedback->getMessage()];
                $emailTpl = \Admin\Model\EmailModel::loadAndPrepare('default-email', $data);

                if ($emailTpl !== null) {
                    $mailer = new \THCFrame\Mailer\Mailer();
                    $mailer->setBody($emailTpl->getBody())
                        ->setSubject($emailTpl->getSubject() . ' - Feedback')
                        ->send();

                    Event::fire('app.log', ['success', 'Feedback id: ' . $id]);
                    $view->successMessage($this->lang('SEND_FEEDBACK_SUCCESS'));
                } else {
                    Event::fire('app.log', ['fail', 'Email template not found']);
                    $view->errorMessage($this->lang('EMAIL_SEND_FAIL'));
                }
                self::redirect('/');
            } else {
                Event::fire('app.log', ['fail', 'Errors: ' . json_encode($feedback->getErrors())]);
                $view->set('feedback', $feedback)
                    ->set('submstoken', $this->revalidateMultiSubmissionProtectionToken())
                    ->set('errors', $feedback->getErrors());
            }
        }
    }

    /**
     * 
     */
    public function deleteAccount()
    {
        $view = $this->getActionView();

        $this->disableView();

        $userId = $this->getUser()->getId();
        $res = \App\Model\UserModel::deleteUser($userId);

        if ($res === true) {
            Event::fire('app.log', ['success', 'User account deleted: ' . $userId]);
            $view->successMessage($this->lang('ACCOUNT_DELETED'));
            self::redirect('/odhlasit');
        } else {
            Event::fire('app.log', ['fail', 'User account deleted: ' . $userId]);
            $view->errorMessage($this->lang('COMMON_FAIL'));
            self::redirect('/muj-profil');
        }
    }
}

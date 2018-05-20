<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use Admin\Model\EmailModel;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Core\StringMethods;

/**
 * Controller for email templates management and mass email sending.
 */
class EmailController extends Controller
{

    /**
     * Show list of email templates.
     *
     * @before _secured, _admin
     */
    public function index()
    {
        $view = $this->getActionView();

        if ($this->isSuperAdmin()) {
            $templates = EmailModel::fetchAll();
        } else {
            $templates = EmailModel::fetchAllCommon();
        }

        $view->set('emails', $templates);
    }

    /**
     * Send mass email.
     *
     * @before _secured, _admin
     */
    public function send()
    {
        $view = $this->getActionView();

        if ($this->isSuperAdmin()) {
            $templates = EmailModel::fetchAllActive();
        } else {
            $templates = EmailModel::fetchAllCommonActive();
        }

        $actions = \App\Model\ActionModel::fetchActiveWithLimit(0);

        $view->set('email', null)
                ->set('templates', $templates)
                ->set('actions', $actions);

        if (RequestMethods::post('submitSendEmail')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                    $this->checkMultiSubmissionProtectionToken() !== true) {
                self::redirect('/admin/email/send/');
            }

            $errors = [];

            $email = new EmailModel([
                'type' => RequestMethods::post('type'),
                'subject' => RequestMethods::post('subject'),
                'body' => StringMethods::prepareEmailText(stripslashes(RequestMethods::post('text'))),
            ]);

            $email->populate();

            $mailer = new \THCFrame\Mailer\Mailer();
            $mailer->setBody($email->getBody())
                    ->setSubject($email->getSubject());

            if (empty(RequestMethods::post('singlerecipients')) && empty(RequestMethods::post('grouprecipients')) && $email->type != 3) {
                $errors['recipientlist'] = [$this->lang('EMAIL_NO_RECIPIENTS')];
            }

            if (empty($errors) && $email->type == 1) {
                $recipients = RequestMethods::post('singlerecipients');
                $recipientsArr = explode(',', $recipients);
                array_map('trim', $recipientsArr);
                $mailer->setSendTo($recipientsArr);

                if ($mailer->send()) {
                    Event::fire('admin.log', ['success', 'Email sent to: '.$recipients]);
                    $view->successMessage($this->lang('EMAIL_SEND_SUCCESS'));
                    self::redirect('/admin/email/send/');
                } else {
                    $view->errorMessage($this->lang('EMAIL_SEND_FAIL'));
                    self::redirect('/admin/email/send/');
                }
            } elseif (empty($errors) && $email->type == 2) {
                $roles = RequestMethods::post('grouprecipients');
                $users = \App\Model\UserModel::all(['active = ?' => true, 'deleted = ?' => false, 'role in ?' => $roles], ['email']);
                $recipientsArr = [];

                foreach ($users as $user) {
                    $recipientsArr[] = $user->getEmail();
                }

                $mailer->setSendTo($recipientsArr);

                if ($mailer->send()) {
                    Event::fire('admin.log', ['success', 'Email sent to: '.implode(',', $recipientsArr)]);
                    $view->successMessage($this->lang('EMAIL_SEND_SUCCESS'));
                    self::redirect('/admin/email/send/');
                } else {
                    $view->errorMessage($this->lang('EMAIL_SEND_FAIL'));
                    self::redirect('/admin/email/send/');
                }
            } elseif (empty($errors) && $email->type == 3) {
                $actionId = RequestMethods::post('actionid');
                $recipients = \App\Model\AttendanceModel::fetchUsersByActionId($actionId);

                if (!empty($recipients)) {
                    foreach ($recipients as $recipient) {
                        $mailer->setSendTo($recipient->email);
                    }
                }

                if ($mailer->send()) {
                    $recipientStr = $mailer->getSendToAsString();
                    Event::fire('admin.log', ['success', 'Email sent to: '.$recipientStr]);
                    $view->successMessage($this->lang('EMAIL_SEND_SUCCESS'));
                    self::redirect('/admin/email/send/');
                } else {
                    $view->errorMessage($this->lang('EMAIL_SEND_FAIL'));
                    self::redirect('/admin/email/send/');
                }
            } else {
                Event::fire('admin.log', ['fail', 'Errors: ']);
                $view->set('errors', $errors)
                    ->set('submstoken', $this->revalidateMultiSubmissionProtectionToken())
                    ->set('email', $email);
            }
        }
    }

    /**
     * Ajax - Load template into ckeditor.
     *
     * @before _secured, _admin
     */
    public function loadTemplate($id, $lang = 'cs')
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        if ($lang == 'en') {
            $fieldName = 'bodyEn';
        } else {
            $fieldName = 'body';
        }

        if ($this->isSuperAdmin()) {
            $template = EmailModel::fetchActiveByIdAndLang($id, $fieldName);
        } else {
            $template = EmailModel::fetchCommonActiveByIdAndLang($id, $fieldName);
        }

        $this->ajaxResponse($this->lang('COMMON_SUCCESS'), false, 200, ['text' => stripslashes($template->$fieldName), 'subject' => $template->getSubject()]);
    }

    /**
     * Create new email template.
     *
     * @before _secured, _admin
     */
    public function add()
    {
        $view = $this->getActionView();

        $view->set('template', null);

        if (RequestMethods::post('submitAddEmailTemplate')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                    $this->checkMultiSubmissionProtectionToken() !== true) {
                self::redirect('/admin/email/');
            }

            $errors = [];
            $urlKey = $urlKeyCh = StringMethods::createUrlKey(RequestMethods::post('title'));

            for ($i = 1; $i <= 100; $i+=1) {
                if (EmailModel::checkUrlKey($urlKeyCh)) {
                    break;
                } else {
                    $urlKeyCh = $urlKey.'-'.$i;
                }

                if ($i == 100) {
                    $errors['title'] = [$this->lang('ARTICLE_UNIQUE_ID')];
                    break;
                }
            }

            $emailTemplate = new EmailModel([
                'title' => RequestMethods::post('title'),
                'subject' => RequestMethods::post('subject'),
                'urlKey' => $urlKeyCh,
                'body' => stripslashes(RequestMethods::post('text')),
                'bodyEn' => stripslashes(RequestMethods::post('texten')),
                'type' => $this->isSuperAdmin() ? RequestMethods::post('type') : 1,
            ]);

            if (empty($errors) && $emailTemplate->validate()) {
                $id = $emailTemplate->save();

                Event::fire('admin.log', ['success', 'Email template id: '.$id]);
                $view->successMessage($this->lang('CREATE_SUCCESS'));
                self::redirect('/admin/email/');
            } else {
                Event::fire('admin.log', ['fail', 'Errors: '.json_encode($errors + $emailTemplate->getErrors())]);
                $view->set('errors', $errors + $emailTemplate->getErrors())
                    ->set('submstoken', $this->revalidateMultiSubmissionProtectionToken())
                    ->set('template', $emailTemplate);
            }
        }
    }

    /**
     * Edit exiting email template.
     *
     * @before _secured, _admin
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $emailTemplate = EmailModel::first(['id = ?' => (int) $id]);

        if (null === $emailTemplate) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            $this->willRenderActionView = false;
            self::redirect('/admin/email/');
        }

        if($emailTemplate->getType() == 2 && !$this->isSuperAdmin()){
            $view->warningMessage($this->lang('LOW_PERMISSIONS'));
            $this->willRenderActionView = false;
            self::redirect('/admin/email/');
        }

        $view->set('template', $emailTemplate);

        if (RequestMethods::post('submitEditEmailTemplate')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/admin/email/');
            }

            $errors = [];
            $urlKey = $urlKeyCh = StringMethods::createUrlKey(RequestMethods::post('title'));

            if ($emailTemplate->urlKey != $urlKey && !EmailModel::checkUrlKey($urlKey)) {
                for ($i = 1; $i <= 100; $i+=1) {
                    if (EmailModel::checkUrlKey($urlKeyCh)) {
                        break;
                    } else {
                        $urlKeyCh = $urlKey . '-' . $i;
                    }

                    if ($i == 100) {
                        $errors['title'] = [$this->lang('ARTICLE_TITLE_IS_USED')];
                        break;
                    }
                }
            }

            $emailTemplate->title = RequestMethods::post('title');
            $emailTemplate->subject = RequestMethods::post('subject');
            $emailTemplate->urlKey = $urlKeyCh;
            $emailTemplate->body = stripslashes(RequestMethods::post('text'));
            $emailTemplate->bodyEn = stripslashes(RequestMethods::post('texten'));
            $emailTemplate->type = $this->isSuperAdmin() ? RequestMethods::post('type') : 1;
            $emailTemplate->active = RequestMethods::post('active');

            if (empty($errors) && $emailTemplate->validate()) {
                $emailTemplate->save();

                Event::fire('admin.log', ['success', 'Email template id: '.$id]);
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                self::redirect('/admin/email/');
            } else {
                Event::fire('admin.log', ['fail', 'Email template id: '.$id,
                    'Errors: '.json_encode($errors + $emailTemplate->getErrors()), ]);
                $view->set('errors', $errors + $emailTemplate->getErrors())
                    ->set('template', $emailTemplate);
            }
        }
    }

    /**
     * Delete existing email template.
     *
     * @before _secured, _admin
     */
    public function delete($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $emailTemplate = EmailModel::first(['id = ?' => (int) $id]);

        if (null === $emailTemplate) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            if ($emailTemplate->getType() == 2 && !$this->isSuperAdmin()) {
                $this->ajaxResponse($this->lang('LOW_PERMISSIONS'), true, 401);
            }
            if ($emailTemplate->delete()) {
                Event::fire('admin.log', ['success', 'Email template id: ' . $id]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log', ['fail', 'Email template id: ' . $id]);
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }
}

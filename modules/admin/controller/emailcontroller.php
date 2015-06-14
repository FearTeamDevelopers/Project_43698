<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;

/**
 * Controller for email templates management and mass email sending
 */
class EmailController extends Controller
{

    /**
     * Show list of email templates
     * 
     * @before _secured, _admin
     */
    public function index()
    {
        $view = $this->getActionView();
        
        if($this->isSuperAdmin()){
            $templates = \Admin\Model\EmailTemplateModel::fetchAll();
        }else{
            $templates = \Admin\Model\EmailTemplateModel::fetchAllCommon();
        }
        
        $view->set('emails', $templates);
    }

    /**
     * Send mass email
     * 
     * @before _secured, _admin
     */
    public function send()
    {
        $view = $this->getActionView();
        
        if($this->isSuperAdmin()){
            $templates = \Admin\Model\EmailTemplateModel::fetchAllActive();
        }else{
            $templates = \Admin\Model\EmailTemplateModel::fetchAllCommonActive();
        }
        
        $view->set('submstoken', $this->_mutliSubmissionProtectionToken())
                ->set('email', null)
                ->set('templates', $templates);
        
        if (RequestMethods::post('submitSendEmail')) {
            if ($this->_checkCSRFToken() !== true &&
                    $this->_checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/email/');
            }
            
            $errors = array();
            $email = new \stdClass();
            $email->type = RequestMethods::post('type');
            $email->subject = RequestMethods::post('subject');
            $email->body = RequestMethods::post('text');
            
            if(empty(RequestMethods::post('singlerecipients')) && empty(RequestMethods::post('grouprecipients'))){
                $errors['recipientlist'] = array('Nejsou vybráni žádní příjemci');
            }
            
            if(empty($errors) && $email->type == 1){
                $recipients = RequestMethods::post('singlerecipients');
                $recipientsArr = explode(',', $recipients);
                
                if($this->_sendEmail($email->body, $email->subject, $recipientsArr)){
                    Event::fire('admin.log', array('success', 'Email sent to: ' . $recipients));
                    $view->successMessage(self::SUCCESS_MESSAGE_11);
                    self::redirect('/admin/email/');
                }else{
                    $view->errorMessage(self::ERROR_MESSAGE_1);
                    self::redirect('/admin/email/');
                }
            }elseif(empty($errors) && $email->type == 2){
                $roles = RequestMethods::post('grouprecipients');
                $users = \App\Model\UserModel::all(array('active = ?' => true, 'deleted = ?' => false, 'role in ?' => $roles), array('email'));
                $recipientsArr = array();
                
                foreach ($users as $user){
                    $recipientsArr[] = $user->getEmail();
                }
                
                if($this->_sendEmail($email->body, $email->subject, $recipientsArr)){
                    Event::fire('admin.log', array('success', 'Email sent to: ' . implode(',', $recipientsArr)));
                    $view->successMessage(self::SUCCESS_MESSAGE_11);
                    self::redirect('/admin/email/');
                }else{
                    $view->errorMessage(self::ERROR_MESSAGE_1);
                    self::redirect('/admin/email/');
                }
            }else{
                Event::fire('admin.log', array('fail', 'Errors: '));
                $view->set('errors', $errors)
                    ->set('submstoken', $this->_revalidateMutliSubmissionProtectionToken())
                    ->set('email', $email);
            }
        }
        
    }

    /**
     * Ajax - Load template into ckeditor
     * 
     * @before _secured, _admin
     */
    public function loadTemplate($id, $lang = 'cs')
    {
        $this->_disableView();
        
        if($lang == 'en'){
            $fieldName = 'bodyEn';
        }else{
            $fieldName = 'body';
        }
        
        $template = \Admin\Model\EmailTemplateModel::fetchCommonActiveByIdAndLang($id, $fieldName);
        
        echo json_encode(array('text' => $template->$fieldName));
        exit;
    }

    /**
     * Create new email template
     * 
     * @before _secured, _admin
     */
    public function add()
    {
        $view = $this->getActionView();

        $view->set('submstoken', $this->_mutliSubmissionProtectionToken())
                ->set('template', null);

        if (RequestMethods::post('submitAddEmailTemplate')) {
            if ($this->_checkCSRFToken() !== true &&
                    $this->_checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/email/');
            }
            
            $emailTemplate = new \Admin\Model\EmailTemplateModel(array(
                'title' => RequestMethods::post('title'),
                'body' => RequestMethods::post('text'),
                'bodyEn' => RequestMethods::post('texten'),
                'type' => RequestMethods::post('type'),
            ));

            if ($emailTemplate->validate()) {
                $id = $emailTemplate->save();

                Event::fire('admin.log', array('success', 'Email template id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/email/');
            } else {
                Event::fire('admin.log', array('fail', 'Errors: '.  json_encode($emailTemplate->getErrors())));
                $view->set('errors', $emailTemplate->getErrors())
                    ->set('submstoken', $this->_revalidateMutliSubmissionProtectionToken())
                    ->set('template', $emailTemplate);
            }
        }
    }

    /**
     * Edit exiting email template
     * 
     * @before _secured, _admin
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $emailTemplate = \Admin\Model\EmailTemplateModel::first(array('id = ?' => (int) $id));

        if (NULL === $emailTemplate) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            $this->_willRenderActionView = false;
            self::redirect('/admin/email/');
        }

        $view->set('template', $emailTemplate);

        if (RequestMethods::post('submitEditEmailTemplate')) {
            if($this->_checkCSRFToken() !== true){
                self::redirect('/admin/email/');
            }
            
            $emailTemplate->title = RequestMethods::post('title');
            $emailTemplate->body = RequestMethods::post('text');
            $emailTemplate->bodyEn = RequestMethods::post('texten');
            $emailTemplate->type = RequestMethods::post('type');
            $emailTemplate->active = RequestMethods::post('active');

            if ($emailTemplate->validate()) {
                $emailTemplate->save();
                
                Event::fire('admin.log', array('success', 'Email template id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/email/');
            } else {
                Event::fire('admin.log', array('fail', 'Email template id: ' . $id,
                    'Errors: '.  json_encode($emailTemplate->getErrors())));
                $view->set('errors', $emailTemplate->getErrors())
                    ->set('template', $emailTemplate);
            }
        }
    }

    /**
     * Delete existing email template
     * 
     * @before _secured, _admin
     */
    public function delete($id)
    {
        $this->_disableView();

        $emailTemplate = \Admin\Model\EmailTemplateModel::first(array('id = ?' => (int)$id));

        if (NULL === $emailTemplate) {
            echo self::ERROR_MESSAGE_2;
        } else {
            if ($emailTemplate->delete()) {
                Event::fire('admin.log', array('success', 'Email template id: ' . $id));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'Email template id: ' . $id));
                echo self::ERROR_MESSAGE_1;
            }
        }
    }

}

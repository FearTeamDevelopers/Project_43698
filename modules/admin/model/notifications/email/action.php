<?php
namespace Admin\Model\Notifications\Email;

use THCFrame\Core\StringMethods;
use Admin\Model\Notifications\Email\EmailTplNotificationConstants as TplNames;
use App\Model\UserModel;
use THCFrame\Model\Model;

/**
 *
 */
class Action extends EmailAbstract
{

    /**
     *
     * @param Model $action
     */
    public function onCreate(Model $action)
    {
        $data = ['{TITLE}' => '<a href="' . $this->host . '/akce/r/' . $action->getUrlKey() . '">' . $action->getTitle() . '</a>',
            '{TEXT}' => StringMethods::prepareEmailText($action->getShortBody()),
        ];

        if ($action->getApproved() && $this->config->new_action_notification) {
            $emailTpl = $this->getEmailTemplate($this->getCreateTemplateName(), $data, $action->getTitle());
            $users = UserModel::getUserEmailsForNotification('getNewActionNotification');

            $this->send($emailTpl, $users);
        }
    }

    /**
     *
     * @param Model $action
     */
    public function onUpdate(Model $action)
    {
        $data = ['{TITLE}' => '<a href="' . $this->host . '/akce/r/' . $action->getUrlKey() . '">' . $action->getTitle() . '</a>',
            '{TEXT}' => StringMethods::prepareEmailText($action->getShortBody()),
        ];

        if ($action->getApproved() && $this->config->update_action_notification) {
            $emailTpl = $this->getEmailTemplate($this->getUpdateTemplateName(), $data, $action->getTitle());
            $users = UserModel::getUserEmailsForNotification('getNewActionNotification');

            $this->send($emailTpl, $users);
        }
    }

    /**
     *
     * @param Model $action
     */
    public function onDelete(Model $action)
    {
        $data = ['{TITLE}' => '<a href="' . $this->host . '/akce/r/' . $action->getUrlKey() . '">' . $action->getTitle() . '</a>',
            '{TEXT}' => StringMethods::prepareEmailText($action->getShortBody()),
        ];

        if ($action->getApproved() && $this->config->delete_action_notification) {
            $emailTpl = $this->getEmailTemplate($this->getDeleteTemplateName(), $data, $action->getTitle());
            $users = UserModel::getUserEmailsForNotification('getNewActionNotification');

            $this->send($emailTpl, $users);
        }
    }

    public function getCreateTemplateName()
    {
        return TplNames::ACTION_NEW_TPL;
    }

    public function getDeleteTemplateName()
    {
        return TplNames::ACTION_DELETE_TPL;
    }

    public function getUpdateTemplateName()
    {
        return TplNames::ACTION_UPDATE_TPL;
    }
}

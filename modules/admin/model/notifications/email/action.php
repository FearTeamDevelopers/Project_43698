<?php
namespace Admin\Model\Notifications\Email;

use THCFrame\Core\StringMethods;
use Admin\Model\Notifications\Email\EmailAbstract;
use Admin\Model\Notifications\Email\EmailTplNotificationConstants as TplNames;
use App\Model\UserModel;

/**
 *
 */
class Action extends EmailAbstract
{

    /**
     *
     * @param \THCFrame\Model\Model $action
     */
    public function onCreate(\THCFrame\Model\Model $action)
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
     * @param \THCFrame\Model\Model $action
     */
    public function onUpdate(\THCFrame\Model\Model $action)
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
     * @param \THCFrame\Model\Model $action
     */
    public function onDelete(\THCFrame\Model\Model $action)
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

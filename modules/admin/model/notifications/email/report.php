<?php
namespace Admin\Model\Notifications\Email;

use THCFrame\Core\StringMethods;
use Admin\Model\Notifications\Email\EmailTplNotificationConstants as TplNames;
use App\Model\UserModel;
use THCFrame\Model\Model;

/**
 *
 */
class Report extends EmailAbstract
{

    /**
     *
     * @param Model $report
     */
    public function onCreate(Model $report)
    {
        $data = ['{TITLE}' => '<a href="' . $this->host . '/reportaze/r/' . $report->getUrlKey() . '">' . $report->getTitle() . '</a>',
            '{TEXT}' => StringMethods::prepareEmailText($report->getShortBody()),
        ];

        if ($report->getApproved() && $this->config->new_report_notification) {
            $emailTpl = $this->getEmailTemplate($this->getCreateTemplateName(), $data, $report->getTitle());
            $users = UserModel::getUserEmailsForNotification('getNewReportNotification');

            $this->send($emailTpl, $users);
        }
    }

    /**
     *
     * @param Model $report
     */
    public function onUpdate(Model $report)
    {
        $data = ['{TITLE}' => '<a href="' . $this->host . '/reportaze/r/' . $report->getUrlKey() . '">' . $report->getTitle() . '</a>',
            '{TEXT}' => StringMethods::prepareEmailText($report->getShortBody()),
        ];

        if ($report->getApproved() && $this->config->update_report_notification) {
            $emailTpl = $this->getEmailTemplate($this->getUpdateTemplateName(), $data, $report->getTitle());
            $users = UserModel::getUserEmailsForNotification('getNewReportNotification');

            $this->send($emailTpl, $users);
        }
    }

    /**
     *
     * @param Model $report
     */
    public function onDelete(Model $report)
    {
        $data = ['{TITLE}' => '<a href="' . $this->host . '/reportaze/r/' . $report->getUrlKey() . '">' . $report->getTitle() . '</a>',
            '{TEXT}' => StringMethods::prepareEmailText($report->getShortBody()),
        ];

        if ($report->getApproved() && $this->config->delete_report_notification) {
            $emailTpl = $this->getEmailTemplate($this->getDeleteTemplateName(), $data, $report->getTitle());
            $users = UserModel::getUserEmailsForNotification('getNewReportNotification');

            $this->send($emailTpl, $users);
        }
    }

    public function getCreateTemplateName()
    {
        return TplNames::REPORT_NEW_TPL;
    }

    public function getDeleteTemplateName()
    {
        return TplNames::REPORT_UPDATE_TPL;
    }

    public function getUpdateTemplateName()
    {
        return TplNames::REPORT_DELETE_TPL;
    }
}

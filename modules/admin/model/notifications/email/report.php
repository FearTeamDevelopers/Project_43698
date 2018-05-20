<?php
namespace Admin\Model\Notifications\Email;

use THCFrame\Core\StringMethods;
use Admin\Model\Notifications\Email\EmailAbstract;
use Admin\Model\Notifications\Email\EmailTplNotificationConstants as TplNames;
use App\Model\UserModel;

/**
 *
 */
class Report extends EmailAbstract
{

    /**
     *
     * @param \THCFrame\Model\Model $report
     */
    public function onCreate(\THCFrame\Model\Model $report)
    {
        $data = ['{TITLE}' => '<a href="' . $this->host . '/reportaze/r/' . $report->getUrlKey() . '">' . $report->getTitle() . '</a>',
            '{TEXT}' => StringMethods::prepareEmailText($report->getShortBody()),
        ];

        if ($report->getApproved() && $this->config->new_report_notification) {
            $emailTpl = $this->getEmailTemplate($this->getCreateTemplateName(), $data, $report->getTitle());
            $users = UserModel::all(['getNewReportNotification = ?' => true], ['email']);

            $this->send($emailTpl, $users);
        }
    }

    /**
     *
     * @param \THCFrame\Model\Model $report
     */
    public function onUpdate(\THCFrame\Model\Model $report)
    {
        $data = ['{TITLE}' => '<a href="' . $this->host . '/reportaze/r/' . $report->getUrlKey() . '">' . $report->getTitle() . '</a>',
            '{TEXT}' => StringMethods::prepareEmailText($report->getShortBody()),
        ];

        if ($report->getApproved() && $this->config->update_report_notification) {
            $emailTpl = $this->getEmailTemplate($this->getUpdateTemplateName(), $data, $report->getTitle());
            $users = UserModel::all(['getNewReportNotification = ?' => true], ['email']);

            $this->send($emailTpl, $users);
        }
    }

    /**
     *
     * @param \THCFrame\Model\Model $report
     */
    public function onDelete(\THCFrame\Model\Model $report)
    {
        $data = ['{TITLE}' => '<a href="' . $this->host . '/reportaze/r/' . $report->getUrlKey() . '">' . $report->getTitle() . '</a>',
            '{TEXT}' => StringMethods::prepareEmailText($report->getShortBody()),
        ];

        if ($report->getApproved() && $this->config->delete_report_notification) {
            $emailTpl = $this->getEmailTemplate($this->getDeleteTemplateName(), $data, $report->getTitle());
            $users = UserModel::all(['getNewReportNotification = ?' => true], ['email']);

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

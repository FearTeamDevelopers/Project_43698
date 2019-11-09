<?php
namespace Admin\Model\Notifications\Email;

use THCFrame\Core\StringMethods;
use Admin\Model\Notifications\Email\EmailTplNotificationConstants as TplNames;
use App\Model\UserModel;
use THCFrame\Model\Model;

/**
 *
 */
class News extends EmailAbstract
{

    /**
     *
     * @param Model $news
     */
    public function onCreate(Model $news)
    {
        $data = ['{TITLE}' => '<a href="' . $this->host . '/novinky/r/' . $news->getUrlKey() . '">' . $news->getTitle() . '</a>',
            '{TEXT}' => StringMethods::prepareEmailText($news->getShortBody()),
        ];

        if ($news->getApproved() && $this->config->new_news_notification) {
            $emailTpl = $this->getEmailTemplate($this->getCreateTemplateName(), $data, $news->getTitle());
            $users = UserModel::getUserEmailsForNotification('getNewNewsNotification');

            $this->send($emailTpl, $users);
        }
    }

    /**
     *
     * @param Model $news
     */
    public function onUpdate(Model $news)
    {
        $data = ['{TITLE}' => '<a href="' . $this->host . '/novinky/r/' . $news->getUrlKey() . '">' . $news->getTitle() . '</a>',
            '{TEXT}' => StringMethods::prepareEmailText($news->getShortBody()),
        ];

        if ($news->getApproved() && $this->config->update_news_notification) {
            $emailTpl = $this->getEmailTemplate($this->getUpdateTemplateName(), $data, $news->getTitle());
            $users = UserModel::getUserEmailsForNotification('getNewNewsNotification');

            $this->send($emailTpl, $users);
        }
    }

    /**
     *
     * @param Model $news
     */
    public function onDelete(Model $news)
    {
        $data = ['{TITLE}' => '<a href="' . $this->host . '/novinky/r/' . $news->getUrlKey() . '">' . $news->getTitle() . '</a>',
            '{TEXT}' => StringMethods::prepareEmailText($news->getShortBody()),
        ];

        if ($news->getApproved() && $this->config->delete_news_notification) {
            $emailTpl = $this->getEmailTemplate($this->getDeleteTemplateName(), $data, $news->getTitle());
            $users = UserModel::getUserEmailsForNotification('getNewNewsNotification');

            $this->send($emailTpl, $users);
        }
    }

    public function getCreateTemplateName()
    {
        return TplNames::NEWS_NEW_TPL;
    }

    public function getDeleteTemplateName()
    {
        return TplNames::NEWS_UPDATE_TPL;
    }

    public function getUpdateTemplateName()
    {
        return TplNames::NEWS_DELETE_TPL;
    }
}

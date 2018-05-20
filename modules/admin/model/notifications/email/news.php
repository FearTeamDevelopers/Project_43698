<?php
namespace Admin\Model\Notifications\Email;

use THCFrame\Core\StringMethods;
use Admin\Model\Notifications\Email\EmailAbstract;
use Admin\Model\Notifications\Email\EmailTplNotificationConstants as TplNames;
use App\Model\UserModel;

/**
 *
 */
class News extends EmailAbstract
{

    /**
     *
     * @param \THCFrame\Model\Model $news
     */
    public function onCreate(\THCFrame\Model\Model $news)
    {
        $data = ['{TITLE}' => '<a href="' . $this->host . '/novinky/r/' . $news->getUrlKey() . '">' . $news->getTitle() . '</a>',
            '{TEXT}' => StringMethods::prepareEmailText($news->getShortBody()),
        ];

        if ($news->getApproved() && $this->config->new_news_notification) {
            $emailTpl = $this->getEmailTemplate($this->getCreateTemplateName(), $data, $news->getTitle());
            $users = UserModel::all(['getNewNewsNotification = ?' => true], ['email']);

            $this->send($emailTpl, $users);
        }
    }

    /**
     *
     * @param \THCFrame\Model\Model $news
     */
    public function onUpdate(\THCFrame\Model\Model $news)
    {
        $data = ['{TITLE}' => '<a href="' . $this->host . '/novinky/r/' . $news->getUrlKey() . '">' . $news->getTitle() . '</a>',
            '{TEXT}' => StringMethods::prepareEmailText($news->getShortBody()),
        ];

        if ($news->getApproved() && $this->config->update_news_notification) {
            $emailTpl = $this->getEmailTemplate($this->getUpdateTemplateName(), $data, $news->getTitle());
            $users = UserModel::all(['getNewNewsNotification = ?' => true], ['email']);

            $this->send($emailTpl, $users);
        }
    }

    /**
     *
     * @param \THCFrame\Model\Model $news
     */
    public function onDelete(\THCFrame\Model\Model $news)
    {
        $data = ['{TITLE}' => '<a href="' . $this->host . '/novinky/r/' . $news->getUrlKey() . '">' . $news->getTitle() . '</a>',
            '{TEXT}' => StringMethods::prepareEmailText($news->getShortBody()),
        ];

        if ($news->getApproved() && $this->config->delete_news_notification) {
            $emailTpl = $this->getEmailTemplate($this->getDeleteTemplateName(), $data, $news->getTitle());
            $users = UserModel::all(['getNewNewsNotification = ?' => true], ['email']);

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

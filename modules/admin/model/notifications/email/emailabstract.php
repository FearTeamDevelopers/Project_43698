<?php
namespace Admin\Model\Notifications\Email;

use Admin\Model\EmailModel;
use Admin\Model\Notifications\NotificationInterface;
use THCFrame\Model\Model;
use THCFrame\Request\RequestMethods;
use THCFrame\Mailer\Mailer;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;

/**
 * 
 */
abstract class EmailAbstract implements NotificationInterface
{

    /** var \Admin\Model\Notifications\NotificationInterface $instance */
    protected static $instance;
    protected $host;
    protected $config;

    /**
     * 
     * @return NotificationInterface
     */
    public static function getInstance(): NotificationInterface
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    private function __construct()
    {
        $this->host = RequestMethods::getServerHost();
        $this->config = Registry::get('configuration');
    }

    /**
     * Prepare and return email template
     * 
     * @param string $templateName
     * @param array $data
     * @param string $emailTitle
     * @return EmailModel
     */
    protected function getEmailTemplate(string $templateName, array $data, string $emailTitle): EmailModel
    {
        $emailTpl = EmailModel::loadAndPrepare($templateName, $data);
        $emailTpl->setSubject($emailTpl->getSubject() . ' - ' . $emailTitle);

        return $emailTpl;
    }

    /**
     * 
     * @param EmailModel $emailTpl
     * @param array $users
     */
    public function send(EmailModel $emailTpl, array $users)
    {
        //TODO: add to queue
        if (!empty($users)) {
            if ($emailTpl !== null) {
                $mailer = new Mailer();
                $mailer->setBody($emailTpl->getBody())
                    ->setSubject($emailTpl->getSubject());

                foreach ($users as $user) {
                    $mailer->setSendTo($user->getEmail());
                }

                $mailer->send(true);
                Event::fire('admin.log', ['success', 'Send new ' . $emailTpl->getTitle() . ' notification to ' . count($users) . ' users']);
            } else {
                Event::fire('admin.log', ['fail', 'Email template not found']);
            }
        }
    }

    abstract public function getCreateTemplateName();

    abstract public function getUpdateTemplateName();

    abstract public function getDeleteTemplateName();

    abstract public function onCreate(Model $object);

    abstract public function onUpdate(Model $object);

    abstract public function onDelete(Model $object);
}

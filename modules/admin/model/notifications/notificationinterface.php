<?php

namespace Admin\Model\Notifications;

use Admin\Model\EmailModel;
use THCFrame\Model\Model;

/**
 *
 */
interface NotificationInterface
{

    public function onCreate(Model $object);

    public function onUpdate(Model $object);

    public function onDelete(Model $object);
    
    public function getCreateTemplateName();

    public function getUpdateTemplateName();

    public function getDeleteTemplateName();
    
    public function send(EmailModel $emailTemplate, array $users);
}

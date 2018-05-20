<?php

namespace Admin\Model\Notifications;

/**
 *
 */
interface NotificationInterface
{

    public function onCreate(\THCFrame\Model\Model $object);

    public function onUpdate(\THCFrame\Model\Model $object);

    public function onDelete(\THCFrame\Model\Model $object);
    
    public function getCreateTemplateName();

    public function getUpdateTemplateName();

    public function getDeleteTemplateName();
    
    public function send(\Admin\Model\EmailModel $emailTemplate, array $users);
}

<?php

THCFrame\Events\Events::fire('plugin.mobiledetect.devicetype.before', []);

require_once 'MobileDetect.php';

$detect = new MobileDetect();
\THCFrame\Registry\Registry::set('mobiledetect', $detect);

THCFrame\Events\Events::fire('plugin.mobiledetect.devicetype.after', []);

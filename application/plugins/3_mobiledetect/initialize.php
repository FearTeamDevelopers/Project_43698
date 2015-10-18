<?php

if (!isset($_SESSION['thc_devicetype'])) {
    require_once 'MobileDetect.php';

    $detect = new MobileDetect();
    \THCFrame\Registry\Registry::set('mobiledetect', $detect);

    if ($detect->isMobile() && !$detect->isTablet()) {
        $deviceType = 'phone';
    } elseif ($detect->isTablet() && !$detect->isMobile()) {
        $deviceType = 'tablet';
    } else {
        $deviceType = 'computer';
    }

    $_SESSION['thc_devicetype'] = $deviceType;
} else {
    $deviceType = $_SESSION['thc_devicetype'];
}

THCFrame\Events\Events::fire('plugin.mobiledetect.devicetype', array($deviceType));

<?php
THCFrame\Events\Events::fire('plugin.browser.initialize.before', []);

require_once 'Browser.php';
$browser = new Browser();

\THCFrame\Registry\Registry::set('browser', $browser);

THCFrame\Events\Events::fire('plugin.browser.initialize.after', []);

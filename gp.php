<?php

require_once 'vendor/autoload.php';

use PhpTabs\PhpTabs;
$tablature = new PhpTabs('exception.gp5');
$tablature->save('exceptiontake2.gp5');

?>

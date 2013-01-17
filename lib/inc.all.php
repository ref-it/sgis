<?php

define('SGISBASE', dirname(dirname(__FILE__)));

require_once SGISBASE.'/config/config.php';
require_once SGISBASE.'/lib/inc.simplesaml.php';
require_once SGISBASE.'/lib/inc.password.php';
require_once SGISBASE.'/lib/inc.db.php';
require_once SGISBASE.'/lib/inc.page.php';
require_once SGISBASE.'/lib/inc.error.php';
require_once SGISBASE.'/externals/phpcaptcha/securimage.php';
require_once SGISBASE.'/lib/ods/OpenDocument_Spreadsheet_Writer.class.php';


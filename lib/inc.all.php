<?php

define('SGISBASE', dirname(dirname(__FILE__)));

require_once SGISBASE.'/config/config.php';
require_once SGISBASE.'/lib/inc.simplesaml.php';
require_once SGISBASE.'/lib/inc.password.php';
require_once SGISBASE.'/lib/inc.db.php';
require_once SGISBASE.'/lib/inc.page.php';
require_once SGISBASE.'/lib/inc.error.php';
require_once SGISBASE.'/lib/inc.nonce.php';
require_once SGISBASE.'/lib/inc.header.php';
require_once SGISBASE.'/lib/inc.crypto.php';
require_once SGISBASE.'/lib/ods/OpenDocument_Spreadsheet_Writer.class.php';
require_once 'XML/RPC2/Client.php';
require_once 'HTTP/Request2.php';
require_once 'Text/Diff.php';
require_once 'Text/Diff/Renderer.php';
require_once 'Text/Diff/Renderer/unified.php';


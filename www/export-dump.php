<?php

global $ADMINGROUP;

require_once "../lib/inc.all.php";

if (isset($_REQUEST["autoExportPW"])) {
  requireExportAutoPW();
} else {
  requireGroup($ADMINGROUP);
}


header('Content-Type: text/javascript; charset=utf8');
printDBDump();


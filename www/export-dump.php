<?php

global $ADMINGROUP;

require_once "../lib/inc.all.php";

if (isset($_REQUEST["autoExportPW"])) {
  requireExportAutoPW();
} else {
  requireGroup($ADMINGROUP);
}

$data = getDBDump();

header('Content-Type: text/javascript; charset=utf8');
echo json_encode($data, JSON_PRETTY_PRINT);


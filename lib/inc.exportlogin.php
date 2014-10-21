<?php

function requireExportAutoPW() {
  global $autoExportPW;

  if (isset($_REQUEST["autoExportPW"]) && md5($_REQUEST["autoExportPW"]) === $autoExportPW)
    return true;

  header('HTTP/1.0 403 Forbidden');
  echo "Wrong password.";
  exit;

}

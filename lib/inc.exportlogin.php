<?php

function requireExportAutoPW() {
  global $autoExportPW;

  if (isset($_REQUEST["autoExportPW"]) && md5($_REQUEST["autoExportPW"]) === $autoExportPW) {
    $fname = dirname(__FILE__)."/../autologin.log";
    file_put_contents($fname, print_r($_SERVER, true));
    return true;
  }

  header('HTTP/1.0 403 Forbidden');
  echo "Wrong password.";
  exit;

}

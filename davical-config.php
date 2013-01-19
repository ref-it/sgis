<?php

/********************************/
/*********** SGIS ***************/
/********************************/

require_once("DavicalAuthPlugin.php");
$c->authenticate_hook = array(
  'call'   => 'SGIS_Check',
  'config' => array(
    'dsn' => "",
    'username' => '',
    'password' => '',
    'prefix'   => 'sgis_',
  )
);
$c->authenticate_hook['optional'] = true;



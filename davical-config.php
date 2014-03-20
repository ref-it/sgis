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
    'prefix'   => 'sgis__', # DB Prefix
    'group'   => 'sgis',
  )
);
$c->authenticate_hook['optional'] = true;



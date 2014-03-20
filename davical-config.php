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

# Should the hook be called on CalDAV access?
# If authenticate_hook['optional'] = true, then not.
# This might be required (=true) if Digest-Authentification should be used.
$c->authenticate_hook['optional'] = false;



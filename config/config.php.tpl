<?php

global $DB_DSN, $DB_USERNAME, $DB_PASSWORD, $DB_PREFIX, $SIMPLESAML, $SIMPLESAMLAUTHSOURCE, $AUTHGROUP, $ADMINGROUP, $rpcKey, $wikiUrl, $CA_file;

$DB_DSN = "FIXME";
$DB_USERNAME = "FIXME";
$DB_PASSWORD = "FIXME";
$DB_PREFIX = "sgis_";
$SIMPLESAML = dirname(dirname(dirname(__FILE__)))."/simplesamlphp-1.10.0";
$SIMPLESAMLAUTHSOURCE = "FIXME";
# permissions required by index.php
$AUTHGROUP = "FIXME";
# admin groups (comma separated)
$ADMINGROUP = "FIXME";
$rpcKey = "FIXME"; # ownCloud shared secret
# dokuwiki exporter
$wikiUrl = "https://sgis:PASSWORD@WIKIHOST"; # + /lib/exe/xmlrpc.php -> valid path
$CA_file = dirname(__FILE__).'/ca.pem';

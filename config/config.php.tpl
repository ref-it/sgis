<?php

global $DB_DSN, $DB_USERNAME, $DB_PASSWORD, $DB_PREFIX, $SIMPLESAML, $SIMPLESAMLAUTHSOURCE, $AUTHGROUP, $ADMINGROUP, $rpcKey, $wikiUrl, $CA_file, $rpcKey2, $sPiBase, $sPiGroupSet, $sPiGroupGet, $sPiUser, $sPiPassword, $sPiCA_file;

$DB_DSN = "FIXME";
$DB_USERNAME = "FIXME";
$DB_PASSWORD = "FIXME";
$DB_PREFIX = "sgis__";
$SIMPLESAML = dirname(dirname(dirname(__FILE__)))."/simplesamlphp";
$SIMPLESAMLAUTHSOURCE = "FIXME";
# permissions required by index.php
$AUTHGROUP = "FIXME";
# admin groups (comma separated)
$ADMINGROUP = "FIXME";
$rpcKey = "FIXME"; # ownCloud shared secret
$rpcKey2 = "FIXME"; # box-services shared secret
# dokuwiki exporter
$wikiUrl = "https://sgis:PASSWORD@WIKIHOST"; # + /lib/exe/xmlrpc.php -> valid path
$CA_file = dirname(__FILE__).'/ca.pem';

$sPiBase = "FIXME"
$sPiGroupSet = "/api/group/%d/members/set";
$sPiGroupGet = "/api/group/%d/members/get";
$sPiUser = "FIXME";
$sPiPassword = "FXIME";
$sPiCA_file = dirname(__FILE__).'/ca.pem';


<?php

global $DB_DSN, $DB_USERNAME, $DB_PASSWORD, $DB_PREFIX, $SIMPLESAML, $SIMPLESAMLAUTHSOURCE, $AUTHGROUP, $ADMINGROUP, $rpcKey, $wikiUrl, $CA_file, $rpcKey2, $sPiBase, $sPiGroupSet, $sPiGroupGet, $sPiUser, $sPiPassword, $sPiCA_file, $autoExportPW, $mailmanSettingModes, $rpcKey3, $REST_API_KEY, $REST_API_IPS, $REST_LOGIN_API_KEY, $REST_LOGIN_API_IPS, $REST_LOGIN_SECRET;
global $unimail, $unildaphost, $unildapbase, $contactTypes;

$DB_DSN = "FIXME";
$DB_USERNAME = "FIXME";
$DB_PASSWORD = "FIXME";
$DB_PREFIX = "sgis__";

require_once "../lib/database/class.DatabaseCore.php"; //load helper function set
intbf\database\DatabaseCore::setConfig([
	'PROVIDER'		=> 'pdo', //mylqli|pdo   ;; pdo not implenented yet
	'HOST' 			=> 'dbhost2',
	'NAME' 			=> 'tu-ilmenau-de_stura',
	'USERNAME' 		=> $DB_USERNAME,
	'PASSWORD' 		=> $DB_PASSWORD,
	'TABLE_PREFIX' 	=> $DB_PREFIX, //(int)tertipa (b)ase (f)ramework
	'CHARSET' 		=> 'utf8',
	'DSN'			=> 'mysql:dbname=[DB_NAME];host=[DB_HOST];charset=[DB_CHARSET]', // required for pdo
]);

define('DB_INSTALL', false);

$SIMPLESAML = dirname(dirname(dirname(__FILE__)))."/simplesamlphp";
$SIMPLESAMLAUTHSOURCE = "FIXME";
# permissions required by index.php
$AUTHGROUP = "FIXME";
# admin groups (comma separated)
$ADMINGROUP = "FIXME";
$rpcKey = "FIXME"; # ownCloud shared secret
$rpcKey2 = "FIXME"; # box-services shared secret
$rpcKey3 = "FIXME"; # box-local shared secret
# dokuwiki exporter
$wikiUrl = "https://sgis:PASSWORD@WIKIHOST"; # + /lib/exe/xmlrpc.php -> valid path
$CA_file = dirname(__FILE__).'/ca.pem';

//normal rest api
$REST_API_KEY = "FIXME_64_LONG_API_KEY"; // length: 64 letters
$REST_API_IPS = ['127.0.0.1']; //# IP whitelist
// login rast api: verify user data, (used by vpn)
$REST_LOGIN_API_KEY = "FIXME_64_LONG_LOGIN_KEY"; // length: 64 letters
$REST_LOGIN_API_IPS = ['127.0.0.1']; //support ipv6
$REST_LOGIN_SECRET = 'FIXME_20_LETTER_SECRET';  // min 20 letters

//PDF CREATOR
$FUI2PDF_APIKEY = "mbObJfJn5mpzJbTsZ8BoJeatqmlsmdy911XipBWR9s3GQpiERH";
define("PDF_CREATOR_APIKEY", $FUI2PDF_APIKEY);
$FUI2PDF_URL = "https://box.stura.tu-ilmenau.de/FUI2PDF2/public/index.php";
define("PDF_CREATOR_URL", $FUI2PDF_URL);
define("PDF_CREATOR_AUTH", base64_encode("user:password"));

//spi
$sPiBase = "FIXME"
$sPiGroupSet = "/api/group/%d/members/set";
$sPiGroupGet = "/api/group/%nd/members/get";
$sPiUser = "FIXME";
$sPiPassword = "FXIME";
$sPiCA_file = dirname(__FILE__).'/ca.pem';
$contactTypes = Array("tel" => "Tel.","xmpp" => "XMPP/Jabber");

# md5(very secret)
$autoExportPW = "...."

$unimail = Array("tu-ilmenau.de","stud.tu-ilmenau.de");
$unildaphost = "imp.tu-ilmenau.de";
$unildapbase = "ou=members,dc=tu-ilmenau,dc=de";

$mailmanSettingModes = [ "set" => "festlegen", "increase-to" => "erhöhen auf", "add" => "Zeile ergänzen", "ignore" => "unverändert lassen" ];

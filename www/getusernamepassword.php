<?php
/**
 * Consent script
 *
 * This script displays a page to the user, which requests that the user
 * authorizes the release of attributes.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */

define(SGISBASE, dirname(dirname(__FILE__)));
require_once SGISBASE.'/externals/password-lib/lib/PasswordLib/PasswordLib.php';

$globalConfig = SimpleSAML_Configuration::getInstance();

SimpleSAML_Logger::info('SGIS - set username / password');

if (!array_key_exists('StateId', $_REQUEST)) {
    throw new SimpleSAML_Error_BadRequest(
        'Missing required StateId query parameter.'
    );
}

$id = $_REQUEST['StateId'];
$state = SimpleSAML_Auth_State::loadState($id, 'sgis:requestusernamepassword');
$errorCode = NULL;

// The user has pressed the yes-button
if (array_key_exists('yes', $_REQUEST)) {
    $person_id = $state["sgis:person_id"];
    $config    = $state["sgis:config"];
    $username  = $_REQUEST["username"];
    $password  = $_REQUEST["password"];
    $pdo = new PDO((string) $config["dsn"], (string) $config["username"], (string) $config["password"]);
    $prefix = $config["prefix"];

    if (!empty($username) && !empty($password)) {
      $pwObj = new PasswordLib\PasswordLib();
      $passwordHash = $pwObj->createPasswordHash($password);

      $query = $pdo->prepare("UPDATE {$prefix}person SET username = ?, password = ? WHERE id = ?");
      $query->execute(Array($username, $passwordHash, $person_id));

      SimpleSAML_Auth_ProcessingChain::resumeProcessing($state);
    } else {
      $errorCode = "fieldmissing";
    }
}
if (array_key_exists('no', $_REQUEST)) {
    SimpleSAML_Auth_ProcessingChain::resumeProcessing($state);
}

// Make, populate and layout consent form
$t = new SimpleSAML_XHTML_Template($globalConfig, 'sgis:usernamepasswordform.php');
$t->data['data'] = array('StateId' => $id);
$t->data['attributes'] = $state["Attributes"];
$t->data['errorcode'] = $errorCode;
$t->show();

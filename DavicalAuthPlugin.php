<?php
/**
* Authenticate against sGIS
*
* @package   sgis
* @subpackage   davical
* @author    Michael Braun <michael-dev@fami-braun.de>
* @copyright Michael Braun
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/

require_once(dirname(__FILE__).'/externals/password-lib/lib/PasswordLib/PasswordLib.php');
require_once("auth-functions.php");

/**
* Check the username / password against the PAM system
*/
function SGIS_Check($username, $password ){
  global $c;

  $config = $c->authenticate_hook['config'];
  $prefix = $config["prefix"];
  $pdo = new PDO($config["dsn"], $config["username"], $config["password"]);
  $username = strtolower($username);

  # check if username is set in sGIS
  $userQuery = $pdo->prepare("SELECT id, name, email, username, password, canLogin FROM {$prefix}person p WHERE username = ?") or die(print_r($pdo->errorInfo(),true));
  $userQuery->execute(Array($username)) or die(print_r($userQuery->errorInfo(),true));
  if ($userQuery->rowCount() == 0) {
    dbg_error_log( "SGIS", "User %s is not a valid username", $username );
    return false;
  }
  $user = $userQuery->fetch(PDO::FETCH_ASSOC) or die(print_r($userQuery->errorInfo(),true));

  # check password
  $passwordHash = $user["password"];
  if (empty($passwordHash)) {
    dbg_error_log( "SGIS", "User %s has no password set", $username );
    return false;
  }
  $pwObj = new PasswordLib\PasswordLib();
  if (!$pwObj->verifyPasswordHash($password, $passwordHash)) {
    dbg_error_log( "SGIS", "User %s has wrong password provided.", $username );
    return false;
  }

  # check canLogin
  $grpQuery = $pdo->prepare("SELECT DISTINCT g.name FROM {$prefix}gruppe g INNER JOIN {$prefix}rel_rolle_gruppe r ON g.id = r.gruppe_id INNER JOIN {$prefix}rel_mitgliedschaft rm ON (rm.rolle_id = r.rolle_id) AND ((rm.von IS NULL) OR (rm.von <= CURRENT_DATE)) AND ((rm.bis IS NULL) OR (rm.bis >= CURRENT_DATE)) WHERE rm.person_id = ? ORDER BY g.name");
  $grpQuery->execute(Array($user["id"])) or die(print_r($grpQuery->errorInfo(),true));
  $grps = $grpQuery->fetchAll(PDO::FETCH_COLUMN);

  if ($user["canLogin"]) {
    $canLogin = !in_array("cannotLogin", $grps);
  } else {
    $canLogin = in_array("canLogin", $grps);
  }

  # if user already exists in db, edit it
  $principal = new Principal('username',$username);
  if ( $principal->Exists() ) {
    $principal->Update( array(
                            'username' => $username,
                            'user_active' => ($canLogin ? 't' : 'f'),
                            'email' => $user["email"],
                            'fullname' => (empty($user["name"]) ? $user["email"] : $user["name"])
                        ));
    sgis_update_groups($principal, $grps);
  }
  if (!$canLogin) {
    dbg_error_log( "SGIS", "User %s is not permitted to login.", $username );
    return false;
  }

  # create principal
  dbg_error_log('SGIS', 'User %s successfully authenticated', $username);
  $principal = new Principal('username',$username);
  if ( !$principal->Exists() ) {
    dbg_error_log('SGIS', 'User %s does not exist in local db, creating', $username);
    $principal->Create( array(
                            'username' => $username,
                            'user_active' => 't',
                            'email' => $user["email"],
                            'fullname' => (empty($user["name"]) ? $user["email"] : $user["name"])
                        ));
    if ( ! $principal->Exists() ) {
      dbg_error_log( "SGIS", "Unable to create local principal for '%s'", $username );
      return false;
    }
    CreateHomeCalendar($username);
    sgis_update_groups($principal, $grps);
  }

  return $principal;
}

function sgis_update_groups($principal, $grps) {
  $username = $principal->username();

  # fetch current db status
  $db_groups = array();
  $db_group_members = array();
  $qry = new AwlQuery( "SELECT g.username AS group_name, member.username AS member_name FROM dav_principal g LEFT JOIN group_member ON (g.principal_id=group_member.group_id) LEFT JOIN dav_principal member  ON (member.principal_id=group_member.member_id) WHERE g.type_id = 3");
  $qry->Exec('SGIS_GRP_SYNC',__LINE__,__FILE__);
  while($db_group = $qry->Fetch()) {
    $db_groups[$db_group->group_name] = $db_group->group_name;
    $db_group_members[$db_group->group_name][] = $db_group->member_name;
  }

  # update
  foreach ($db_groups as $grp) {
    if (in_array($grp, $grps) && !in_array($username, $db_group_members[$grp])) {
      # principal should be member but is not
      $qry = new AwlQuery( "INSERT INTO group_member SELECT g.principal_id AS group_id,u.principal_id AS member_id FROM dav_principal g, dav_principal u WHERE g.username=:group AND u.username=:member",array (':group'=>$grp,':member'=>$username) );
      $qry->Exec('SGIS_GRP_SYNC',__LINE__,__FILE__);
      Principal::cacheDelete('username', $username);
    } elseif (!in_array($grp, $grps) && in_array($username, $db_group_members[$grp])) {
      # principal should not be member but is
      $qry = new AwlQuery( "DELETE FROM group_member USING dav_principal g,dav_principal m WHERE group_id=g.principal_id AND member_id=m.principal_id AND g.username=:group AND m.username=:member",array (':group'=>$grp,':member'=>$username) );
      $qry->Exec('SGIS_GRP_SYNC',__LINE__,__FILE__);
      Principal::cacheDelete('username', $username);
    }
  }

}

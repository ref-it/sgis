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
  try {
    $pdo = new PDO($config["dsn"], $config["username"], $config["password"]);
  } catch (PDOException $e) {
    $c->messages[] = "Verbindung zur sGIS-Datenbank ist fehlgeschlagen: ". $e->getMessage();
    return false;
  }

  $group = $config["group"];
  $username = strtolower($username);

  $principal = new Principal('username',$username);
  if ($principal->Exists()) {
    $db_groups = sgis_get_principal_groups($principal);
    $isSGIS = in_array($group,$db_groups);
  } else {
    $isSGIS = false;
  }

  # check if username is set in sGIS
  $userQuery = $pdo->prepare("SELECT id, name, email, username, password, canLogin FROM {$prefix}person p WHERE username = ?") or die(print_r($pdo->errorInfo(),true));
  $userQuery->execute(Array($username)) or die(print_r($userQuery->errorInfo(),true));
  if ($userQuery->rowCount() == 0) {
    if ($isSGIS) {
      $c->messages[] = "Dieser ehemalige sGIS-Nutzer existiert nicht mehr. Bitte wende dich an den Administrator.";
      dbg_error_log( "SGIS", "User %s is not a valid username", $username );
      $principal->Update(array(
                            'username' => "disabled.".randomstring().".".$username,
                            'user_active' => 'f',
                        ));
    }
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
    if ($isSGIS) {
      dbg_error_log( "SGIS", "User %s has wrong password provided.", $username );
    }
    return false;
  }
  if ($principal->Exists() && !$isSGIS) {
    $c->messages[] = "Ein gleichnamiger Nutzer existiert bereits in der Datenbank, daher ist ein SGIS-Login nicht möglich.";
    return false;
  }

  # check canLogin
  $grpQuery = $pdo->prepare("SELECT DISTINCT g.name FROM {$prefix}gruppe g INNER JOIN {$prefix}rel_rolle_gruppe r ON g.id = r.gruppe_id INNER JOIN {$prefix}rel_mitgliedschaft rm ON (rm.rolle_id = r.rolle_id) AND ((rm.von IS NULL) OR (rm.von <= CURRENT_DATE)) AND ((rm.bis IS NULL) OR (rm.bis >= CURRENT_DATE)) WHERE rm.person_id = ? ORDER BY g.name");
  $grpQuery->execute(Array($user["id"])) or die(print_r($grpQuery->errorInfo(),true));
  $grps = $grpQuery->fetchAll(PDO::FETCH_COLUMN);
  $grps[] = $group;

  if ($user["canLogin"]) {
    $canLogin = !in_array("cannotLogin", $grps);
  } else {
    $canLogin = in_array("canLogin", $grps);
  }

  # if user already exists in db, edit it
  if ( $principal->Exists() ) {
    $principal->Update( array(
                            'username' => $username,
                            'user_active' => ($canLogin ? 't' : 'f'),
                            'email' => $user["email"],
                            'password' => session_salted_sha1($password),
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
  if ( !$principal->Exists() ) {
    dbg_error_log('SGIS', 'User %s does not exist in local db, creating', $username);
    $principal->Create( array(
                            'username' => $username,
                            'user_active' => 't',
                            'email' => $user["email"],
                            'password' => session_salted_sha1($password),
                            'fullname' => (empty($user["name"]) ? $user["email"] : $user["name"])
                        ));
    if ( ! $principal->Exists() ) {
      dbg_error_log( "SGIS", "Unable to create local principal for '%s'", $username );
      return false;
    }
    CreateHomeCalendar($username);
    sgis_update_groups($principal, $grps);
  }

  return new  Principal('username',$username);
}

function sgis_get_principal_groups($principal) {
  $username = $principal->username();

  $db_groups = array();
  $qry = new AwlQuery( "SELECT g.username AS group_name FROM dav_principal g INNER JOIN group_member ON (g.principal_id=group_member.group_id) AND g.type_id =3 INNER JOIN dav_principal member ON (member.principal_id=group_member.member_id) WHERE member.username = :principal", Array(":principal" => $username));
  $qry->Exec('SGIS_GRP_SYNC',__LINE__,__FILE__);
  while($db_group = $qry->Fetch()) {
    $db_groups[$db_group->group_name] = $db_group->group_name;
  }

  return $db_groups;
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
      $c->messages[] = "Nutzer $username zur Gruppe $grp hinzugefügt.";
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

function randomstring($length = 8) {
  $chars = "abcdefghijklmnopqrstuvwxyz
            ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
  srand((double)microtime()*1000000);
  $pass = "";
  for ($i = 0; $i < $length; $i++) {
    $num = rand() % strlen($chars);
    $pass .= substr($chars, $num, 1);
  }
  return $pass;
}


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

class SGIS_AuthPluginDavical {
  private $pdo;
  private $c;
  private $userQuery;
  private $groupQuery;
  private $group;
  private $config;
  private $adminGroup = "admin";

  public function __construct($c) {
    $this->c = $c;
    $this->config = $c->authenticate_hook['config'];
    $this->prefix = $this->config["prefix"];
    $this->group = $this->config["group"];
    try {
      $this->pdo = new PDO($this->config["dsn"], $this->config["username"], $this->config["password"]);
      $this->userQuery = $this->pdo->prepare("SELECT id, name, email, username, password, canLogin FROM {$this->prefix}person p WHERE username = ?") or die(print_r($this->pdo->errorInfo(),true));
      $this->groupQuery = $this->pdo->prepare("SELECT DISTINCT g.name FROM {$this->prefix}gruppe g INNER JOIN {$this->prefix}rel_rolle_gruppe r ON g.id = r.gruppe_id INNER JOIN {$this->prefix}rel_mitgliedschaft rm ON (rm.rolle_id = r.rolle_id) AND ((rm.von IS NULL) OR (rm.von <= CURRENT_DATE)) AND ((rm.bis IS NULL) OR (rm.bis >= CURRENT_DATE)) WHERE rm.person_id = ? ORDER BY g.name");
    } catch (PDOException $e) {
      $c->messages[] = "Verbindung zur sGIS-Datenbank ist fehlgeschlagen: ". $e->getMessage();
      $this->pdo = NULL;
    }
  }


  /** return principal if username, password are sGIS-ok
    * if sgis is unreachable, return false.
    * periodically update all sgis users
    * if correct old password is provided clear davical password cache
    * if correct new password is provided update davical password cache
    */
  public function auth($username, $password) {
    if ($this->pdo === NULL) { return $this->checkDavicalLocalLogin($username, $password); };
    $username = strtolower($username);

    $principal = new Principal('username',$username);

    # check sgis-status in davical
    if ($principal->Exists()) {
      $db_groups = $this->sgis_get_principal_groups($principal);
      $isSGIS = in_array($this->group,$db_groups);
    } else {
      $isSGIS = false;
    }

    # update details
    if ($isSGIS) {
      $this->check_and_update_sgis_principal($principal);
    }

    # check if username is set in sGIS
    $user = $this->get_sgis_user_details($principal);
    if ($user === false) {
      if (!$isSGIS) {
        return $this->checkDavicalLocalLogin($username, $password);
      } else {
        dbg_error_log( "SGIS", "User %s does not exist.", $username );
        return false;
      }
    }

    # check password
    if (!$this->checkPassword($user, $password)) {
      if ($isSGIS) {
        dbg_error_log( "SGIS", "User %s has wrong password provided.", $username );
        if (session_validate_password($password,$principal->password)) {
          # changing the sGIS password should prevent somebody knowing the old password from accessing the data even if the owner did not login into davical again.
          # here the agent provided the correct old password, but not the new sGIS password
          # having fallback authentication set, davical would just let him in
          # so this principal gets password disabled here. user_active cannot be used as periodic cleanup will change it.
          
         $principal->Update( array('password' => ''));
        }
      } else {
        return $this->checkDavicalLocalLogin($username, $password);
      }
      return false;
    }
    if ($principal->Exists() && !$isSGIS) {
      $c->messages[] = "Ein gleichnamiger Nutzer existiert bereits in der Datenbank, daher ist ein SGIS-Login nicht möglich.";
      return false;
    }
    if ( $principal->Exists() ) {
      $principal->Update( array( 'password' => session_salted_sha1($password) ));
    }
  
    # check canLogin
    $canLogin = $user["canLogin"]; 
    if (!$canLogin) {
      dbg_error_log( "SGIS", "User %s is not permitted to login.", $username );
      return false;
    }
  
    # create principal
    dbg_error_log('SGIS', 'User %s successfully authenticated', $username);
    if ( !$principal->Exists() ) {
      dbg_error_log('SGIS', 'User %s does not exist in local db, creating', $username);
      $principal->Create( array('username' => $username, 'password' => session_salted_sha1($password) ));
      if ( ! $principal->Exists() ) {
        dbg_error_log( "SGIS", "Unable to create local principal for '%s'", $username );
        return false;
      }
      $this->check_and_update_sgis_principal($principal);
      CreateHomeCalendar($username);
    }
  
    $this->periodic_cleanup();
  
    return new Principal('username',$username);
  }

  /* check given password against stored password hash in user */
  private function checkPassword($user, $password) {
    $passwordHash = $user["password"];
    if (empty($passwordHash)) {
      dbg_error_log( "SGIS", "User %s has no password set", $user["username"] );
      return false;
    }
    $pwObj = new PasswordLib\PasswordLib();
    return $pwObj->verifyPasswordHash($password, $passwordHash);
  }

  /* cleanup daily */
  private function periodic_cleanup() {
    $sgis_principal = new Principal('username', $this->group);
    if (!$sgis_principal->Exists()) { 
      dbg_error_log('SGIS', 'Group %s does not exist in davical!!', $this->group);
      return;
    }

    $db_modified = strtotime($sgis_principal->modified);
    $timeout = strtotime("-1 day"); # day
    if ($timeout <= $db_modified) {
      return; # data is fresh
    }

    $qry = new AwlQuery( "SELECT member.username AS member_name FROM dav_principal g INNER JOIN group_member ON (g.principal_id=group_member.group_id) INNER JOIN dav_principal member  ON (member.principal_id=group_member.member_id) WHERE g.type_id = 3 AND g.username = :sgis_name", Array(":sgis_name" => $this->group));
    $qry->Exec('SGIS_GRP_SYNC',__LINE__,__FILE__);
    $sgis_users = Array();
    while($db_row = $qry->Fetch()) {
      $user = $db_row->member_name;
      $sgis_users[$user] = $user;
    }
    foreach ($sgis_users as $user) {
      $principal = new Principal('username', $user);
      if (!$principal->Exists()) continue; # should not happen
      if (!$principal->user_active) continue;
      $this->check_and_update_sgis_principal($principal);
    }
    $sgis_principal->Update();
  }

  /* return groups as stored in sgis (+ sgis-group), returns Array('sgis') for non-existing users */
  private function get_sgis_groups(&$principal) {
    $username = $principal->username();
    $this->userQuery->execute(Array($username)) or die(print_r($this->userQuery->errorInfo(),true));
    if ($this->userQuery->rowCount() == 0) {
      $grps = Array();
    } else {
      $userRow = $this->userQuery->fetch(PDO::FETCH_ASSOC) or die(print_r($this->userQuery->errorInfo(),true));
      $this->groupQuery->execute(Array($userRow["id"])) or die(print_r($this->groupQuery->errorInfo(),true));
      $grps = $this->groupQuery->fetchAll(PDO::FETCH_COLUMN);
    }
    $grps[] = $this->group;
    return $grps;
  }

  /* return sgis user row, update canLogin by group membership state */
  private function get_sgis_user_details($principal) {
    $username = $principal->username();

    $this->userQuery->execute(Array($username)) or die(print_r($this->userQuery->errorInfo(),true));
    if ($this->userQuery->rowCount() == 0) {
      return false;
    }
    $userRow = $this->userQuery->fetch(PDO::FETCH_ASSOC) or die(print_r($this->userQuery->errorInfo(),true));
    $grps = $this->get_sgis_groups($principal);

    if ($userRow["canLogin"]) {
      $canLogin = !in_array("cannotLogin", $grps);
    } else {
      $canLogin = in_array("canLogin", $grps);
    }

    $userRow["canLogin"] = $canLogin;

    return $userRow;
  }

  /* check existing davical user (that is in sgis group (check this before calling this method)) if it is still is sgis and update its details */
  private function check_and_update_sgis_principal(&$principal) {
    $username = $principal->username();

    $grps = $this->get_sgis_groups($principal);
    $this->sgis_update_groups($principal, $grps);

    $userRow = $this->get_sgis_user_details($principal);
    if ($userRow === false) {
      dbg_error_log( "SGIS", "cleanup: User %s is not a valid username", $username );
      $principal->Update(array(
                        'username' => "disabled.".$this->randomstring().".".$username,
                        'user_active' => 'f',
                    ));
    } else {
      $principal->Update( array(
                        'username' => $username,
                        'user_active' => ($userRow["canLogin"] ? 't' : 'f'),
                        'email' => $userRow["email"],
                        'fullname' => (empty($userRow["name"]) ? $userRow["email"] : $userRow["name"]),
                        'displayname' => (empty($userRow["name"]) ? $userRow["email"] : $userRow["name"]),
                      ));
    }
  }

  /* get davical groups */
  private function sgis_get_principal_groups(&$principal) {
    $username = $principal->username();
  
    $db_groups = array();
    $qry = new AwlQuery( "SELECT g.username AS group_name FROM dav_principal g INNER JOIN group_member ON (g.principal_id=group_member.group_id) AND g.type_id =3 INNER JOIN dav_principal member ON (member.principal_id=group_member.member_id) WHERE member.username = :principal", Array(":principal" => $username));
    $qry->Exec('SGIS_GRP_SYNC',__LINE__,__FILE__);
    while($db_group = $qry->Fetch()) {
      $db_groups[$db_group->group_name] = $db_group->group_name;
    }
  
    return $db_groups;
  }
 
  /* update davical principal groups + admin role */ 
  private function sgis_update_groups(&$principal, $grps) {
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
        $c->messages[] = "Add $username to group $grp";
        # principal should be member but is not
        $qry = new AwlQuery( "INSERT INTO group_member SELECT g.principal_id AS group_id,u.principal_id AS member_id FROM dav_principal g, dav_principal u WHERE g.username=:group AND u.username=:member",array (':group'=>$grp,':member'=>$username) );
        $qry->Exec('SGIS_GRP_SYNC',__LINE__,__FILE__);
        Principal::cacheDelete('username', $username);
        Principal::cacheDelete('username', $grp);
      } elseif (!in_array($grp, $grps) && in_array($username, $db_group_members[$grp])) {
        # principal should not be member but is
        $qry = new AwlQuery( "DELETE FROM group_member USING dav_principal g,dav_principal m WHERE group_id=g.principal_id AND member_id=m.principal_id AND g.username=:group AND m.username=:member",array (':group'=>$grp,':member'=>$username) );
        $qry->Exec('SGIS_GRP_SYNC',__LINE__,__FILE__);
        Principal::cacheDelete('username', $username);
        Principal::cacheDelete('username', $grp);
      }
    }
  
    # Admin?
    $qry = new AwlQuery( "SELECT role_no FROM roles WHERE role_name='Admin'" );
    $qry->Exec('SGIS_GRP_SYNC',__LINE__,__FILE__);
    $row = $qry->Fetch() or die("DB Fehler ".__LINE__);
    $admin_role_no = $row->role_no;
  
    $qry = new AwlQuery( "SELECT count(*) AS admins FROM role_member WHERE user_no = :user_no AND role_no = :role_no", Array(":user_no" => $principal->user_no(), ":role_no" => $admin_role_no));
    $qry->Exec('SGIS_GRP_SYNC',__LINE__,__FILE__);
    $row = $qry->Fetch() or die("DB Fehler ".__LINE__);
    $isAdmin = ($row->admins > 0);
    if ($isAdmin && !in_array($this->adminGroup, $grps)) {
      # is davical admin but should not be
      $qry = new AwlQuery( "DELETE FROM role_member WHERE user_no = :user_no AND role_no = :role_no",  Array(":user_no" => $principal->user_no(), ":role_no" => $admin_role_no));
      $qry->Exec('SGIS_GRP_SYNC',__LINE__,__FILE__);
      Principal::cacheDelete('username', $username);
    } elseif (!$isAdmin && in_array($this->adminGroup, $grps)) {
      # is not davical admin but should be
      $qry = new AwlQuery( "INSERT INTO role_member (user_no, role_no) VALUES (:user_no, :role_no)",  Array(":user_no" => $principal->user_no(), ":role_no" => $admin_role_no));
      $qry->Exec('SGIS_GRP_SYNC',__LINE__,__FILE__);
      Principal::cacheDelete('username', $username);
    }
  }

  /* return a random ascii string */  
  private static function randomstring($length = 8) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
    srand((double)microtime()*1000000);
    $pass = "";
    for ($i = 0; $i < $length; $i++) {
      $num = rand(0, strlen($chars)-1);
      $pass .= substr($chars, $num, 1);
    }
    return $pass;
  }


  private function checkDavicalLocalLogin($username, $password) {
    if ( $principal = new Principal('username', $username) ) {
      if ( $principal->user_active && session_validate_password( $password, $principal->password ) ) {
        return $principal;
      }
    }
    return false;
  }
}

/**
* Check the username / password against the sGIS system
*/
function SGIS_Check($username, $password ){
  global $c;

  $sgis = new SGIS_AuthPluginDavical($c);
  return $sgis->auth($username, $password);
}


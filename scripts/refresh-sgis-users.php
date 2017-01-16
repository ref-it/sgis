#! /usr/bin/php
<?php

$rpcKey2 = "FIXME";
$url = "https://helfer.stura.tu-ilmenau.de/sgis/rpcrefresh.php";

if (!extension_loaded("curl")) die("missing curl!");
if (!extension_loaded("posix")) die("missing posix!");
if (!extension_loaded("mcrypt")) die("missing mcrypt!");

# 1. get all db local users
$mysqluser = "FIXME";
$mysqlpassword = "FIXME";
$db = new PDO("mysql:dbname=wlanauth;host=141.24.44.151",$mysqluser,$mysqlpassword) or die("db connection failed");
$stmt = $db->prepare("SELECT username FROM person WHERE organization='stura' AND not isadmin") or die(print_r($db->errorInfo(), true));
$stmt->execute() or die(print_r($stmt->errorInfo(), true));
$usernames=$stmt->fetchAll(PDO::FETCH_COLUMN, 0);

# 2. get all local unix information
$sgisgrp = posix_getgrnam("sgis");
if ($sgisgrp === false) {
  exec("addgroup --quiet sgis");
  $sgisgrp = posix_getgrnam("sgis");
  if($sgisgrp === false) die("missing sgis group");
}
$usernames = array_unique(array_map('strtolower',array_merge($usernames, $sgisgrp["members"])));
sort($usernames);

# 3. fetch from SGIS
$nonce = md5(rand());
$reply = sgisRequest(Array("username" => $usernames, "nonce" => $nonce));
if ($reply === false) throw new Exception("SGIS failure for user");
if ($reply["nonce"] !== $nonce) throw new Exception("SGIS failure");
if ($reply["status"] !== "ok") throw new Exception("SGIS failure");
foreach($reply["replies"] as $username => $r) {
  if ($r["status"] !== "oklogin" && $r["status"] != "badlogin") throw new Exception("SGIS failure");
}

# 4. update
$delusersql = Array(
"INSERT INTO log (action, organization, responsible, responsible_isadmin, affects_person, affects_person_isadmin, affects_device, description)
 SELECT 'DELDEVICE', 'stura', p.email, p.isadmin, p.email, p.isadmin, d.mac, 'removed from sgis'
 FROM person p INNER JOIN device d USING (email, organization, isadmin)
 WHERE p.organization='stura' AND NOT p.isadmin AND p.username = ?",
"INSERT INTO log (action, organization, responsible, responsible_isadmin, affects_person, affects_person_isadmin, affects_device, description)
 SELECT 'DELUSER', 'stura', p.email, p.isadmin, p.email, p.isadmin, NULL, 'removed from sgis'
 FROM person p
 WHERE p.organization='stura' AND NOT p.isadmin AND p.username = ?",
"DELETE d FROM person p INNER JOIN device d USING (email, organization, isadmin) WHERE p.organization='stura' AND NOT p.isadmin AND p.username = ?",
"DELETE FROM person WHERE organization='stura' AND NOT isadmin AND username = ?");
$deluserstmt = Array();
foreach ($delusersql as $sql) {
  $deluserstmt[] = $db->prepare($sql) or die("prepare $sql\n".print_r($db->errorInfo(), true));
}

$refreshuserstmt = $db->prepare("UPDATE person SET expiresAt = GREATEST(expiresAt, ?) WHERE (username = ?) AND (organization = 'stura') AND (NOT isadmin)") or die("prepare $stmt\n".print_r($db->errorInfo(), true));

$getdbpwstmt = $db->prepare("SELECT plainpassword FROM person WHERE username = ? AND organization = 'stura'") or die("prepare $stmt\n".print_r($db->errorInfo(), true));

foreach($reply["replies"] as $username => $r) {
  # username needs to match ^[a-z][-a-z0-9_]*\$
  $username = strtolower($username);
  if (!preg_match('/^[a-z][-a-z0-9_]*$/', $username)) continue;

  $unixuser = posix_getpwnam($username);
  if ($unixuser !== false && $unixuser["gid"] != $sgisgrp["gid"]) {
    # oops we have a local user with the same name - that is not from sgis
    # skip this!
    continue;
  }
  if (!isset($r["person"]) || $r["status"] !== "oklogin") {
    # remove user locally if exists
    echo "remove $username\n";
    if ($unixuser !== false) {
      exec('smbpasswd -x '.escapeshellarg($username));
      exec('deluser --quiet '.escapeshellarg($username));
    }
    $db->beginTransaction() or die(print_r($db->errorInfo(), true));
    foreach ($deluserstmt as $stmt) {
      $stmt->execute(Array($username)) or die(print_r($stmt->errorInfo(), true));
    }
    $db->commit() or die(print_r($db->errorInfo(), true));
  } else {
    # user is expected to exist
    if ($unixuser === false) {
      $name = $r["person"]["name"].';'.$r["person"]["email"];
      $name = preg_replace('/[^-a-zA-Z0-9 ;,_@]/','', $name);
      exec('adduser  --home /nonexistent --shell /bin/false --no-create-home --firstuid 10000 --lastuid 20000 --ingroup sgis --disabled-password --disabled-login --gecos '.escapeshellarg($name).' '.escapeshellarg($username));
      exec('smbpasswd -a -n -d '.escapeshellarg($username));
    }
    # update expiry
    $expiresAt = date("Y-m-d", strtotime("+6 months"));
    $refreshuserstmt->execute(Array($expiresAt, $username)) or die(print_r($refreshuserstmt->errorInfo(), true));
    # update groups
    $newgrps = Array("sgis");
    foreach($r["grps"] as $grp) {
      # group name needs to match ^[a-z][-a-z0-9_]*\$
      $grp  = strtolower($grp);
      if (!preg_match('/^[a-z][-a-z0-9_]*$/', $grp)) continue;
      $newgrps[] = "sgis-".$grp;
    }
    $newgrps = array_unique(array_map('strtolower',$newgrps));
    $oldgrps = array_unique(array_map('strtolower',array_merge(explode(" ", exec("id -Gn ".escapeshellarg($username))))));
    $grpsToAdd = array_diff($newgrps, $oldgrps);
    $grpsToRem = array_diff($oldgrps, $newgrps);
    if (count($grpsToAdd) + count($grpsToRem) > 0) {
      echo "$username in groups ".join(",", $newgrps)."\n";
      if (count($grpsToAdd) > 0)
        echo " + ".join(",", $grpsToAdd)."\n";
      if (count($grpsToRem) > 0)
        echo " - ".join(",", $grpsToRem)."\n";
    }
    foreach ($grpsToRem as $grp) {
      if (substr($grp,0,5) !== "sgis-") continue;
      exec("deluser --quiet ".escapeshellarg($username)." ".escapeshellarg($grp));
    }
    foreach ($grpsToAdd as $grp) {
      if (substr($grp,0,5) !== "sgis-") continue;
      if (posix_getgrnam($grp) === false) {
        exec("addgroup --quiet ".escapeshellarg($grp));
        exec("net groupmap add ntgroup=".escapeshellarg($grp)." unixgroup=".escapeshellarg($grp)." type=d");
        if(posix_getgrnam($grp) === false) continue;
      }
      exec("adduser --quiet ".escapeshellarg($username)." ".escapeshellarg($grp));
    }
    # set samba password
    $getdbpwstmt->execute(Array($username)) or die(print_r($getdbpwstmt->errorInfo(), true));
    $pw = $getdbpwstmt->fetchColumn();

    if ($pw !== NULL) {
      $handle = popen("smbpasswd -s ".escapeshellarg($username), "w");
      fwrite($handle, $pw."\n");
      fwrite($handle, $pw."\n");
      pclose($handle);
      exec('smbpasswd -e '.escapeshellarg($username));
    } else {
      exec('smbpasswd -n -d '.escapeshellarg($username));
    }
  }
}


function sgisRequest($request) {
  global $rpcKey2, $url;
  if (!function_exists('curl_init')) return false;
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, Array("login" => encrypt(json_encode($request), $rpcKey2)));
#  curl_setopt($ch, CURLOPT_VERBOSE, true);
  $output = curl_exec($ch);
  curl_close($ch);
  if ($output === false) return false;
  $output = decrypt($output, $rpcKey2);
  if ($output === false) return false;
  return json_decode($output, true);
}

function encrypt( $msg, $k, $base64 = true ) {
    # open cipher module (do not change cipher/mode)
    if ( ! $td = mcrypt_module_open('rijndael-256', '', 'ctr', '') )
        return false;

    $msg = serialize($msg);                   # serialize         
    $iv  = mcrypt_create_iv(32, MCRYPT_RAND);         # create iv

    if ( mcrypt_generic_init($td, $k, $iv) !== 0 )  # initialize buffers
        return false;

    $msg  = mcrypt_generic($td, $msg);              # encrypt       
    $msg  = $iv . $msg;                           # prepend iv                    
    $mac  = pbkdf2($msg, $k, 1000, 32);       # create mac
    $msg .= $mac;                                 # append mac                

    mcrypt_generic_deinit($td);                   # clear buffers         
    mcrypt_module_close($td);                         # close cipher module   

    if ( $base64 ) $msg = base64_encode($msg);      # base64 encode?

    return $msg;                                # return iv+ciphertext+mac      
}

function decrypt( $msg, $k, $base64 = true ) {
    if ( $base64 ) $msg = base64_decode($msg);          # base64 decode?

    # open cipher module (do not change cipher/mode)
    if ( ! $td = mcrypt_module_open('rijndael-256', '', 'ctr', '') )
        return false;

    $iv  = substr($msg, 0, 32);                       # extract iv                
    $mo  = strlen($msg) - 32;                             # mac offset                
    $em  = substr($msg, $mo);                             # extract mac               
    $msg = substr($msg, 32, strlen($msg)-64);             # extract ciphertext
    $mac = pbkdf2($iv . $msg, $k, 1000, 32);    # create mac

    if ( $em !== $mac )                               # authenticate mac                  
        return false;

    if ( mcrypt_generic_init($td, $k, $iv) !== 0 )    # initialize buffers
        return false;

    $msg = mdecrypt_generic($td, $msg);               # decrypt           
    $msg = unserialize($msg);                             # unserialize               

    mcrypt_generic_deinit($td);                       # clear buffers             
    mcrypt_module_close($td);                             # close cipher module       
    return $msg;                                    # return original msg               
}

function pbkdf2( $p, $s, $c, $kl, $a = 'sha256' ) {

    $hl = strlen(hash($a, null, true));     # Hash length
    $kb = ceil($kl / $hl);          # Key blocks to compute
    $dk = '';                             # Derived key               

    # Create key
    for ( $block = 1; $block <= $kb; $block ++ ) {

        # Initial hash for this block
        $ib = $b = hash_hmac($a, $s . pack('N', $block), $p, true);

        # Perform block iterations
        for ( $i = 1; $i < $c; $i ++ )

            # XOR each iterate
            $ib ^= ($b = hash_hmac($a, $b, $p, true));

        $dk .= $ib; # Append iterated block
    }

    # Return derived key of correct length
    return substr($dk, 0, $kl);
}


<?php

/**
 * ownCloud - user_sgis
 *
 * @author Michael Braun
 * @copyright 2014 Michael Braun <michael-dev (at) fami-braun (dot) de>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

use OC\Files\Filesystem;

class OC_USER_SGIS {

    // cached settings
    protected $sgis_url;
    protected $sgis_key;
    protected $group = "sgis";
    protected static $me = NULL;
    protected $testedUsers = Array();

    static private function forceLog($msg) {
        $out = ob_get_contents();
        ob_end_clean();
        echo $msg."<br/>\n";
        flush();
        ob_start();
        echo $out;
    }

    public function __construct() 
    {
        $this->url = OCP\Config::getAppValue('user_sgis', 'sgis_url', '');
        $this->key = OCP\Config::getAppValue('user_sgis', 'sgis_key', '');
        $this->backend = new OC_User_Database();
        self::$me = $this;
    }

    public static function getMe() {
        if (self::$me === NULL) return new self();
        return self::$me;
    }

    /**
    * @brief Check if the password is correct
    * @param $uid The username
    * @param $password The password
    * @returns true/false
    *
    * Check if the password is correct without logging in the user
    */
    public function checkPassword($uid, $password)
    {
        static $recur = 0;
        if ($recur > 0) return false;
        $recur++;

        OCP\Util::writeLog('OC_USER_SGIS', "Entering checkPassword() for UID: $uid", OCP\Util::DEBUG);
        if (OC_User::userExists($uid)) {
            if (OC_Group::inGroup($uid, $this->group)) {
                try {
                    return $this->sgisLoginCheck($uid, $password, true) ? $uid : false;
                } catch (Exception $e) {
                }
            }
            $ret = $this->backend->checkPassword($uid, $password);
        } else {
            $ret = $this->sgisLoginCheck($uid, $password, false) ? $uid : false;
        }
        $recur--;
        return $ret;
    }

    public function userExists($uid) {
        static $recur = 0;
        if ($recur > 0) return false;
        $recur++;

       # OCP\Util::writeLog('OC_USER_SGIS', "userExists() for UID: $uid", OCP\Util::DEBUG);

        try {
global $debugJobs;
if ($debugJobs) {
            if (!$this->backend->userExists($uid)) {
                OCP\Util::writeLog('OC_USER_SGIS', "userExists() for UID: $uid -> backend does not have it", OCP\Util::DEBUG);
            }
            if (!OC_Group::inGroup($uid, $this->group)) {
                OCP\Util::writeLog('OC_USER_SGIS', "userExists() for UID: $uid -> not in group ".$this->group, OCP\Util::DEBUG);
            }
            if (isset($this->testedUsers[$uid])) {
                OCP\Util::writeLog('OC_USER_SGIS', "userExists() for UID: $uid -> recently checked", OCP\Util::DEBUG);
            }
}
            if ($this->backend->userExists($uid) && OC_Group::inGroup($uid, $this->group) && !isset($this->testedUsers[$uid])) {
                OCP\Util::writeLog('OC_USER_SGIS', "userExists() with sGIS for UID: $uid", OCP\Util::DEBUG);
                $nonce = self::randomstring();
                $reply = $this->sgisRequest(Array("username" => $uid, "password" => "", "nonce" => $nonce));
                if ($reply === false) throw new Exception("SGIS failure");
                if ($reply["nonce"] !== $nonce) throw new Exception("SGIS failure");
                if ($reply["status"] !== "oklogin" && $reply["status"] !== "badlogin") throw new Exception("SGIS failure");
                if (!isset($reply["person"])) {
                    OC_User::disableUser($uid);
                }
                $grps = array_merge(Array($this->group),$reply["grps"]);
                $this->update_groups($uid, $grps, false);
                $this->testedUsers[$uid] = true;
            }
        } catch (Exception $e) {
        }
        $recur--;
        return $this->backend->userExists($uid);
    }

    protected function sgisLoginCheck($uid, $password, $exists) {
        $nonce = self::randomstring();

        $reply = $this->sgisRequest(Array("username" => $uid, "password" => $password, "nonce" => $nonce));
        if ($reply === false) throw new Exception("SGIS failure");
        if ($reply["nonce"] !== $nonce) throw new Exception("SGIS failure");
        if ($reply["status"] !== "oklogin" && $reply["status"] !== "badlogin") return false;
        if ($reply["status"] === "oklogin") {
            if ($exists) {
                $this->backend->setPassword($uid, $password);
            } else {
                $this->backend->createUser($uid, $password);
                Filesystem::init($uid, '');
                OC_Group::addToGroup($uid, $this->group);
            }
            if (!OC_User::isEnabled($uid))
                OC_User::enableUser($uid);
        } elseif ($exists && $this->backend->checkPassword($uid, $password)) {
            # incorrect new password combined with correct old password -> erase old password from local backend
            $this->backend->setPassword($uid, self::randomstring());
        }
        if ($exists || ($reply["status"] === "oklogin")) $this->updateUserFromSGIS($uid, $reply);
        return ($reply["status"] === "oklogin");
    }

    public function __call($name, $arguments) {
        static $recur = NULL;
        if ($recur === NULL) $recur = Array();
        if (isset($recur[$name])) return false;
        $recur[$name] = true;

        $ret = call_user_func_array(Array($this->backend, $name), $arguments);

        unset($recur[$name]);
        return $ret;
    }

    # asks SGIS for details
    protected function sgisRequest($request) {
#        OCP\Util::writeLog('OC_USER_SGIS', "sgisRequest(".json_encode($request).")", OCP\Util::DEBUG);
        if (!function_exists('curl_init')) return false;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, Array("login" => self::encrypt(json_encode($request), $this->key)));
        $output = curl_exec($ch);
        curl_close($ch);
        if ($output === false) return false;
        $output = self::decrypt($output, $this->key);
        if ($output === false) return false;
#        OCP\Util::writeLog('OC_USER_SGIS', "sgisRequest(".json_encode($request).") = ".$output, OCP\Util::DEBUG);
        return json_decode($output, true);
    }


    protected function updateUserFromSGIS($uid, $reply) {
        unset($this->todoDisplayName);

        if (!isset($reply["person"])) {
            # user not present in SGIS
            # don't delete it now due to data access
            OC_User::disableUser($uid);
        } else {
            # OwnCloud has only email field and groups
            \OC::$server->getConfig()->setUserValue($uid, "settings", "email", $reply["person"]["email"], NULL);
//            \OC::$server->getConfig()->setUserValue($uid, 'files', 'quota', 10 * 1024 * 1024 * 1024, NULL);
            if ($reply["canLogin"]) {
                if (!OC_User::isEnabled($uid))
                    OC_User::enableUser($uid);
            } else {
                if (OC_User::isEnabled($uid))
                    OC_User::disableUser($uid);
            }
            if (!empty($reply["person"]["name"])) {
                $this->todoUid = $uid;
                $this->todoDisplayName = $reply["person"]["name"];
            }
        }
        # Group management deferred, as addToGroup -> OC_Filesystem-Hook -> Fails due to OC_Failsystem::init not called.
        # fixed by init
#        $this->todoGroups = array_merge(Array($this->group),$reply["grps"]);
        $groups = array_merge(Array($this->group),$reply["grps"]);
        $this->update_groups($uid, $groups);
        #$this->todoUid = $uid;
    }

    public function updateUserFromSGISReally() {
#      if (isset($this->todoUid) && isset($this->todoGroups)) {
#        $uid = $this->todoUid;
#        $grps = $this->todoGroups;
#        $this->update_groups($uid, $grps);
#        unset($this->todoGroups);
#      }
      if (isset($this->todoUid) && isset($this->todoDisplayName)) {
        OC_User::setDisplayName($this->todoUid, $this->todoDisplayName);
        unset($this->todoDisplayName);
      }
      unset($this->todoUid);
    }

    protected function update_groups($uid, $groups, $addToGroups = true) {
        #syslog(LOG_ERR, "sgis-owncloud: $uid ".join(",",$groups)." ".($addToGroups?"+":"-"));
        $old_groups = OC_Group::getUserGroups($uid);
        foreach($old_groups as $group) {
            if (in_array(strtolower($group), array_map('strtolower', $groups))) continue;
            OC_Group::removeFromGroup($uid,$group);
            OCP\Util::writeLog('saml','Removed "'.$uid.'" from the group "'.$group.'"', OCP\Util::DEBUG);
        }
        if ($addToGroups) {
            Filesystem::init($uid, '');
            foreach($groups as $group) {
#echo "add $uid to $group<br>\n";
                if (preg_match( '/[^a-zA-Z0-9 _\.@\-]/', $group)) {
                    OCP\Util::writeLog('saml','Invalid group "'.$group.'", allowed chars "a-zA-Z0-9" and "_.@-" ',OCP\Util::DEBUG);
#echo "add $uid to $group ... INV<br>\n";
                }
                else {
                    if (!OC_Group::groupExists($group)) {
                        OC_Group::createGroup($group);
                        OCP\Util::writeLog('saml','New group created: '.$group, OCP\Util::DEBUG);
#echo "add $uid to $group ... NG<br>\n";
                    } 
                    if (OC_Group::groupExists($group) && !OC_Group::inGroup($uid, $group)) {
                        OC_Group::addToGroup($uid, $group);
                        OCP\Util::writeLog('saml','Added "'.$uid.'" to the group "'.$group.'"', OCP\Util::DEBUG);
#echo "add $uid to $group ... ADD<br>\n";
                    }
                }
#echo "add $uid to $group ... DONE<br>\n";
            }
#die();
        }
    }

    /* return a random ascii string */
    protected static function randomstring($length = 8) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
        srand((double)microtime()*1000000);
        $pass = "";
        for ($i = 0; $i < $length; $i++) {
            $num = rand(0, strlen($chars)-1);
            $pass .= substr($chars, $num, 1);
        }
        return $pass;
    }

    public static function encrypt( $msg, $k, $base64 = true ) {
    
        # open cipher module (do not change cipher/mode)
        if ( ! $td = mcrypt_module_open('rijndael-256', '', 'ctr', '') )
            return false;
    
        $msg = serialize($msg);                   # serialize         
        $iv  = mcrypt_create_iv(32, MCRYPT_RAND);         # create iv
    
        if ( mcrypt_generic_init($td, $k, $iv) !== 0 )  # initialize buffers
            return false;
    
        $msg  = mcrypt_generic($td, $msg);              # encrypt       
        $msg  = $iv . $msg;                           # prepend iv                    
        $mac  = self::pbkdf2($msg, $k, 1000, 32);       # create mac
        $msg .= $mac;                                 # append mac                
    
        mcrypt_generic_deinit($td);                   # clear buffers         
        mcrypt_module_close($td);                         # close cipher module   
    
        if ( $base64 ) $msg = base64_encode($msg);      # base64 encode?
    
        return $msg;                                # return iv+ciphertext+mac      
    }
    
    public static function decrypt( $msg, $k, $base64 = true ) {
        if ( $base64 ) $msg = base64_decode($msg);          # base64 decode?
    
        # open cipher module (do not change cipher/mode)
        if ( ! $td = mcrypt_module_open('rijndael-256', '', 'ctr', '') )
            return false;
    
        $iv  = substr($msg, 0, 32);                       # extract iv                
        $mo  = strlen($msg) - 32;                             # mac offset                
        $em  = substr($msg, $mo);                             # extract mac               
        $msg = substr($msg, 32, strlen($msg)-64);             # extract ciphertext
        $mac = self::pbkdf2($iv . $msg, $k, 1000, 32);    # create mac
    
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
    
    public static function pbkdf2( $p, $s, $c, $kl, $a = 'sha256' ) {
    
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
    
}

# vim:set ts=4 sw=4 expandtab:

?>

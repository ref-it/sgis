<?php

define("SGISBASE", dirname(dirname(dirname(dirname(__FILE__)))));
require_once SGISBASE.'/externals/password-lib/lib/PasswordLib/PasswordLib.php';
require_once SGISBASE.'/lib/Auth/UserPassBaseCookie.php';

/**
 * Verify SGIS password and add SGIS attributes into reply.
 *
 * @package stura-sgis
 * @version $Id$
 */
class sspmod_sgis_Auth_Source_SGIS extends sspmod_sgis_Auth_UserPassBaseCookie {

        /**
         * The database object.
         *
         * @var PDO
         */
        private $pdo;

        /**
        * The database prefix.
         *
         * @var prefix
         */
        private $prefix;

        /**
         * The password verifier object
         *
         * @var PasswordLib
         */
        private $pwObj;

        /**
         * Initialize this filter, parse configuration
         *
         * @param array $config  Configuration information about this filter.
         * @param mixed $reserved  For future use.
         */
        public function __construct($info, $config) {
                parent::__construct($info, $config);

                assert('is_array($config)');

                if (!isset($config['dsn'])) {
                        throw new SimpleSAML_Error_Exception($this->authId . ': Missing required \'dsn\' option.');
                }
                if (!isset($config['username'])) {
                        throw new SimpleSAML_Error_Exception($this->authId . ': Missing required \'username\' option.');
                }
                if (!isset($config['password'])) {
                        throw new SimpleSAML_Error_Exception($this->authId . ': Missing required \'password\' option.');
                }
                if (!isset($config['prefix'])) {
                        throw new SimpleSAML_Error_Exception($this->authId . ': Missing required \'prefix\' option.');
                }

                $this->pdo = new PDO((string) $config["dsn"], (string) $config["username"], (string) $config["password"]);
		$this->prefix = $config['prefix'];
		$this->pwObj = new PasswordLib\PasswordLib();
        }

	// This function receives the username and password the user entered, and is expected to return the attributes of that user. If the username or password is incorrect, it should throw an error saying so.
	function login($username, $password) {
		$prefix = $this->prefix;

		$query = $this->pdo->prepare("SELECT id, canLogin, password, email, username, name FROM {$prefix}person WHERE username = ?");
		if (!$query->execute(Array($username))) throw new SimpleSAML_Error_Exception($this->authId . ': database error.');
		if ($query->rowCount() != 1) {
			// no such user
			throw new SimpleSAML_Error_Error('WRONGUSERPASS');
		}
		$user = $query->fetch(PDO::FETCH_ASSOC);

		$query = $this->pdo->prepare("SELECT DISTINCT g.name FROM {$prefix}gruppe g INNER JOIN {$prefix}rel_rolle_gruppe rrg ON g.id = rrg.gruppe_id INNER JOIN {$prefix}rel_mitgliedschaft rrm ON rrg.rolle_id = rrm.rolle_id AND (rrm.von IS NULL OR rrm.von <= CURRENT_DATE) AND (rrm.bis IS NULL OR rrm.bis >= CURRENT_DATE) WHERE rrm.person_id = ?");
		$query->execute(array($user["id"]));
		$grps = $query->fetchAll( PDO::FETCH_COLUMN, 0 );

		$canLogin = (bool) $user["canLogin"];
		if (($canLogin && in_array("cannotLogin", $grps)) || (!$canLogin && !in_array("canLogin", $grps)))
			throw new SimpleSAML_Error_Error('WRONGUSERPASS');

		$passwordHash = $user["password"];
		if (empty($passwordHash))
			throw new SimpleSAML_Error_Error('WRONGUSERPASS');
		if (!$this->pwObj->verifyPasswordHash($password, $passwordHash))
			throw new SimpleSAML_Error_Error('WRONGUSERPASS');

		# Login ok
                $attributes = Array();
                $attributes["mail"] = Array($user["email"]);
                $attributes["eduPersonPrincipalName"] = Array($user["username"]);
		if (!empty($user["name"])) {
	                $attributes["displayName"] = Array($user["name"]);
		} else {
	                $attributes["displayName"] = Array($user["email"]);
		}
		$query = $this->pdo->prepare("SELECT DISTINCT m.address FROM {$prefix}mailingliste m INNER JOIN {$prefix}rel_rolle_mailingliste rrm ON m.id = rrm.mailingliste_id INNER JOIN {$prefix}rel_mitgliedschaft rm ON rrm.rolle_id = rm.rolle_id AND (rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE) WHERE rm.person_id = ?");
		$query->execute(array($user["id"]));
		$mailinglists = $query->fetchAll( PDO::FETCH_COLUMN, 0 );
                $attributes["mailinglists"] = array_unique($mailinglists);

		$query = $this->pdo->prepare("UPDATE {$prefix}person SET lastLogin = CURRENT_TIMESTAMP WHERE id = ?");
		$query->execute(Array($user["id"]));

		$grps[] = "sgis";
		$grps[] = "user";
                $attributes["groups"] = array_unique($grps);

		return $attributes;
	}

}

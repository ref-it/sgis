<?php

define(SGISBASE, dirname(dirname(dirname(dirname(__FILE__)))));
require_once SGISBASE.'/externals/password-lib/lib/PasswordLib/PasswordLib.php';

/**
 * Verify SGIS password and add SGIS attributes into reply.
 *
 * @package stura-sgis
 * @version $Id$
 */
class sspmod_sgis_Auth_Source_SGIS extends sspmod_core_Auth_UserPassBase {

        /**
         * The database object.
         *
         * @var PDO
         */
        private $pdo;

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

                $this->pdo = new PDO((string) $config["dsn"], (string) $config["username"], (string) $config["password"]);
		$this->pwObj = new PasswordLib\PasswordLib();
        }

	// This function receives the username and password the user entered, and is expected to return the attributes of that user. If the username or password is incorrect, it should throw an error saying so.
	function login($username, $password) {

		$query = $this->pdo->prepare("SELECT id, canLogin, password, email, username FROM sgis_person WHERE username = ?");
		if (!$query->execute(Array($username))) throw new SimpleSAML_Error_Exception($this->authId . ': database error.');
		if ($query->rowCount() != 1) {
			// no such user
			throw new SimpleSAML_Error_Error('WRONGUSERPASS');
		}
		$user = $query->fetch(PDO::FETCH_ASSOC);
		$canLogin = (bool) $user["canLogin"];
		if (!$canLogin)
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

		$query = $this->pdo->prepare("UPDATE sgis_person SET lastLogin = CURRENT_TIMESTAMP WHERE id = ?");
		$query->execute(Array($user["id"]));

		$query = $this->pdo->prepare("SELECT g.name FROM sgis_gruppe g INNER JOIN sgis_rel_rolle_gruppe rrg ON g.id = rrg.gruppe_id INNER JOIN sgis_rel_mitgliedschaft rrm ON rrg.rolle_id = rrm.rolle_id WHERE rrm.person_id = ?");
		$query->execute(array($user["id"]));
		$attributes["groups"] = $query->fetchAll( PDO::FETCH_COLUMN, 0 );
		$attributes["groups"][] = "sgis";

		return $attributes;
	}

}

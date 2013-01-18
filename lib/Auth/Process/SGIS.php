<?php

/**
 * Add SGIS attributes into reply.
 *
 * @package stura-sgis
 * @version $Id$
 */
class sspmod_sgis_Auth_Process_SGIS extends SimpleSAML_Auth_ProcessingFilter {

        /**
        * The database object.
         *
         * @var PDO
         */
        private $pdo;

        /**
         * Initialize this filter, parse configuration
         *
         * @param array $config  Configuration information about this filter.
         * @param mixed $reserved  For future use.
         */
        public function __construct($config, $reserved) {
                parent::__construct($config, $reserved);

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
        }


        /**
         * Apply the SGIS transformation
         *
         * @param array &$request  The current request
         */
        public function process(&$request) {
                assert('is_array($request)');
                assert('array_key_exists("Attributes", $request)');

                $attributes = &$request['Attributes'];
                $mail = $attributes["mail"][0];
                $unirzlogin = $attributes["eduPersonPrincipalName"][0];
                
                $query = $this->pdo->prepare("SELECT id, name, username, canLogin, unirzlogin FROM sgis_person p WHERE p.unirzlogin = ?");
                $query->execute(array($unirzlogin));
                $user = $query->fetchAll(PDO::FETCH_ASSOC);
                $valid = false;
                if (count($user) > 0) {
                  $user = $user[0];
                  $valid = true;
                } else { // new user
                  $query = $this->pdo->prepare("SELECT id, name, username, canLogin, unirzlogin FROM sgis_person p WHERE p.email = ?");
                  $query->execute(array($mail));
                  $user = $query->fetchAll(PDO::FETCH_ASSOC);
                  if (count($user) > 0) {
                    $user = $user[0];
                    if (empty($user["unirzlogin"])) {
                      $query = $this->pdo->prepare("UPDATE sgis_person SET unirzlogin = ? WHERE id = ?");
                      $query->execute(Array($unirzlogin, $user["id"]));
                      $valid = true;
                    }
                  }
                }
                if ($valid) {
                  $query = $this->pdo->prepare("SELECT g.name FROM sgis_gruppe g INNER JOIN sgis_rel_rolle_gruppe rrg ON g.id = rrg.gruppe_id INNER JOIN sgis_rel_mitgliedschaft rrm ON rrg.rolle_id = rrm.rolle_id AND (rrm.von IS NULL OR rrm.von <= CURRENT_DATE) AND (rrm.bis IS NULL OR rrm.bis >= CURRENT_DATE) WHERE rrm.person_id = ?");
                  $query->execute(array($user["id"]));
                  $grps = $query->fetchAll( PDO::FETCH_COLUMN, 0 );
                  $grps[] = "sgis";
                  $valid = (bool) $user["canLogin"];
                  $valid = (($valid && !in_array("cannotLogin",$grps)) || (!$valid && in_array("canLogin",$grps)));
                }
                if ($valid) {
                  if (!empty($user["username"])) {
                    $attributes["eduPersonPrincipalName"] = Array($user["username"]);
                  }
                  if (!empty($user["name"])) {
                    $attributes["displayName"] = Array($user["name"]);
                  }
                  $query = $this->pdo->prepare("UPDATE sgis_person SET lastLogin = CURRENT_TIMESTAMP WHERE id = ?");
                  $query->execute(Array($user["id"]));
                  $attributes["groups"] = array_unique(array_merge($attributes["groups"], $grps));
                }
                if (!isset($attributes["displayName"])) {
                  $attributes["displayName"] = $attributes["eduPersonPrincipalName"];
                }
        }

}


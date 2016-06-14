<?php
global $pdo;
global $DB_DSN, $DB_USERNAME, $DB_PASSWORD, $DB_PREFIX;

$pdo = new PDO($DB_DSN, $DB_USERNAME, $DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8; SET lc_time_names = 'de_DE';"));

# Personen

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}person");
if ($r === false) {
  $pdo->query("CREATE TABLE {$DB_PREFIX}person (
                id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(128) NOT NULL,
                username VARCHAR(128) NULL,
                password VARCHAR(256) NULL,
                unirzlogin VARCHAR(128) NULL,
                lastLogin TIMESTAMP NULL,
                canLogin BOOLEAN NOT NULL DEFAULT 1,
                UNIQUE (username),
                UNIQUE (unirzlogin),
                PRIMARY KEY (id)
               ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));
  require SGISBASE.'/lib/inc.db.person.php';
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}person_email");
if ($r === false) {
  $pdo->beginTransaction();
  $pdo->query("CREATE TABLE {$DB_PREFIX}person_email (
                person_id INT NOT NULL,
                srt INT NOT NULL,
                email VARCHAR(128) NOT NULL,
                PRIMARY KEY (email),
                FOREIGN KEY (person_id) REFERENCES {$DB_PREFIX}person(id) ON DELETE CASCADE
               ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));
  $r = $pdo->query("SELECT email FROM {$DB_PREFIX}person");
  if ($r !== false) {
    $pdo->query("INSERT INTO {$DB_PREFIX}person_email (person_id, srt, email) SELECT id, 1, email FROM {$DB_PREFIX}person");
    $pdo->query("ALTER TABLE {$DB_PREFIX}person DROP COLUMN email");
  }
  $pdo->commit();
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}person_email_primary_ids");
if ($r === false) {
  $pdo->query("CREATE VIEW {$DB_PREFIX}person_email_primary_ids AS
     SELECT person_id, MIN(srt) as srt
       FROM {$DB_PREFIX}person_email
   GROUP BY person_id;")
  or httperror(print_r($pdo->errorInfo(),true));
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}person_email_primary");
if ($r === false) {
  $pdo->query("CREATE OR REPLACE VIEW {$DB_PREFIX}person_email_primary AS
     SELECT person_id, srt, email
       FROM {$DB_PREFIX}person_email NATURAL JOIN {$DB_PREFIX}person_email_primary_ids
   GROUP BY person_id;")
  or httperror(print_r($pdo->errorInfo(),true));
}

# Gremium & Rollen

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}gremium");
if ($r === false) {
  $pdo->query("CREATE TABLE {$DB_PREFIX}gremium (
                id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(128) NOT NULL,
                fakultaet VARCHAR(128) NULL,
                studiengang VARCHAR(128) NULL,
                studiengangabschluss VARCHAR(128) NULL,
                wiki_members VARCHAR(128) NULL,
                wiki_members_table VARCHAR(128) NULL,
                wiki_members_fulltable VARCHAR(128) NULL,
                wiki_members_fulltable2 VARCHAR(128) NULL,
                active BOOLEAN NOT NULL DEFAULT 1,
                PRIMARY KEY(id),
                UNIQUE(name, fakultaet, studiengang, studiengangabschluss)
               ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));
  require SGISBASE.'/lib/inc.db.gremium.php';
}

$r = $pdo->query("SELECT wiki_members_table FROM {$DB_PREFIX}gremium");
if ($r === false) {
  $pdo->query("ALTER TABLE {$DB_PREFIX}gremium ADD COLUMN wiki_members_table VARCHAR(128) NULL;") or httperror(print_r($pdo->errorInfo(),true));
}

$r = $pdo->query("SELECT wiki_members_fulltable FROM {$DB_PREFIX}gremium");
if ($r === false) {
  $pdo->query("ALTER TABLE {$DB_PREFIX}gremium ADD COLUMN wiki_members_fulltable VARCHAR(128) NULL;") or httperror(print_r($pdo->errorInfo(),true));
}

$r = $pdo->query("SELECT wiki_members_fulltable2 FROM {$DB_PREFIX}gremium");
if ($r === false) {
  $pdo->query("ALTER TABLE {$DB_PREFIX}gremium ADD COLUMN wiki_members_fulltable2 VARCHAR(128) NULL;") or httperror(print_r($pdo->errorInfo(),true));
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}rolle");
if ($r === false) {
  $pdo->query("CREATE TABLE {$DB_PREFIX}rolle (
                id INT NOT NULL AUTO_INCREMENT,
                gremium_id INT NOT NULL,
                name VARCHAR(128) NOT NULL,
                active BOOLEAN NOT NULL DEFAULT 1,
                spiGroupId BIGINT NULL DEFAULT NULL,
                numPlatz INT NOT NULL DEFAULT 0,
                wahlDurchWikiSuffix VARCHAR(128) NULL,
                wahlPeriodeDays INT NOT NULL DEFAULT 365,
                PRIMARY KEY(id),
                FOREIGN KEY (gremium_id) REFERENCES {$DB_PREFIX}gremium(id) ON DELETE CASCADE,
                UNIQUE(gremium_id, name),
                INDEX(gremium_id, id)
              ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));

  require SGISBASE.'/lib/inc.db.rolle.php';
}
$r = $pdo->query("SELECT numPlatz FROM {$DB_PREFIX}rolle LIMIT 1");
if ($r === false) {
  $pdo->query("ALTER TABLE {$DB_PREFIX}rolle ADD COLUMN numPlatz INT NOT NULL DEFAULT 0;") or httperror(print_r($pdo->errorInfo(),true));
}
$r = $pdo->query("SELECT wahlDurchWikiSuffix FROM {$DB_PREFIX}rolle LIMIT 1");
if ($r === false) {
  $pdo->query("ALTER TABLE {$DB_PREFIX}rolle ADD COLUMN wahlDurchWikiSuffix VARCHAR(128) NULL;") or httperror(print_r($pdo->errorInfo(),true));
}
$r = $pdo->query("SELECT wahlPeriodeDays FROM {$DB_PREFIX}rolle LIMIT 1");
if ($r === false) {
  $pdo->query("ALTER TABLE {$DB_PREFIX}rolle ADD COLUMN wahlPeriodeDays INT NOT NULL DEFAULT 365;") or httperror(print_r($pdo->errorInfo(),true));
}

# Log

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}log");
if ($r === false) {
  $pdo->query("CREATE TABLE {$DB_PREFIX}log (
                id INT NOT NULL AUTO_INCREMENT,
                action VARCHAR(254) NOT NULL,
                evtime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                responsible VARCHAR(254),
                PRIMARY KEY(id)
               ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}log_property");
if ($r === false) {
  $pdo->query("CREATE TABLE {$DB_PREFIX}log_property (
                id INT NOT NULL AUTO_INCREMENT,
                log_id INT NOT NULL,
                name VARCHAR(128) NOT NULL,
                value LONGTEXT,
                INDEX(log_id),
                INDEX(name),
                INDEX(name, value(256)),
                PRIMARY KEY(id),
                FOREIGN KEY (log_id) REFERENCES {$DB_PREFIX}log(id) ON DELETE CASCADE
              ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));
}

# gesteuerte Objekte

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}mailingliste");
if ($r === false) {
  $pdo->query("CREATE TABLE {$DB_PREFIX}mailingliste (
                id INT NOT NULL AUTO_INCREMENT,
                address VARCHAR(128) NOT NULL,
                password VARCHAR(128) NOT NULL,
                url VARCHAR(128) NULL,
                PRIMARY KEY(id),
                UNIQUE(address)
              ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));
  require SGISBASE.'/lib/inc.db.mailingliste.php';
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}gruppe");
if ($r === false) {
  $pdo->query("CREATE TABLE {$DB_PREFIX}gruppe (
                id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(128) NOT NULL,
                beschreibung VARCHAR(256) NOT NULL,
                UNIQUE(name),
		PRIMARY KEY(id)
              ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));

  require SGISBASE.'/lib/inc.db.gruppe.php';
}

# Mapping Person -> Rolle -> Mailingliste, Gruppe

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}rel_rolle_gruppe");
if ($r === false) {
  $pdo->query("CREATE TABLE {$DB_PREFIX}rel_rolle_gruppe (
                rolle_id INT NOT NULL,
                gruppe_id INT NOT NULL,
                FOREIGN KEY (rolle_id) REFERENCES {$DB_PREFIX}rolle(id) ON DELETE CASCADE,
                FOREIGN KEY (gruppe_id) REFERENCES {$DB_PREFIX}gruppe(id) ON DELETE CASCADE,
                PRIMARY KEY (rolle_id, gruppe_id) ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));
  require SGISBASE.'/lib/inc.db.gruppe-rolle.php';
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}rel_rolle_mailingliste");
if ($r === false) {
  $pdo->query("CREATE TABLE {$DB_PREFIX}rel_rolle_mailingliste (
                rolle_id INT NOT NULL,
                mailingliste_id INT NOT NULL,
                FOREIGN KEY (rolle_id) REFERENCES {$DB_PREFIX}rolle(id) ON DELETE CASCADE,
                FOREIGN KEY (mailingliste_id) REFERENCES {$DB_PREFIX}mailingliste(id) ON DELETE CASCADE,
                PRIMARY KEY (rolle_id, mailingliste_id) ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));
  require SGISBASE.'/lib/inc.db.rolle-mailingliste.php';
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}rel_mitgliedschaft");
if ($r === false) {
  $pdo->query("CREATE TABLE {$DB_PREFIX}rel_mitgliedschaft (
                id INT NOT NULL AUTO_INCREMENT,
                rolle_id INT NOT NULL,
		gremium_id INT NOT NULL,
                person_id INT NOT NULL,
                von DATE NULL,
                bis DATE NULL,
                beschlussAm VARCHAR(256),
                beschlussDurch VARCHAR(256),
                lastCheck DATE NULL,
                kommentar VARCHAR(256),
                FOREIGN KEY (gremium_id) REFERENCES {$DB_PREFIX}gremium(id) ON DELETE CASCADE,
                FOREIGN KEY (gremium_id, rolle_id) REFERENCES {$DB_PREFIX}rolle(gremium_id, id) ON DELETE CASCADE,
                FOREIGN KEY (person_id) REFERENCES {$DB_PREFIX}person(id) ON DELETE CASCADE,
                PRIMARY KEY (id) ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));
  require SGISBASE.'/lib/inc.db.mitgliedschaft.php';
}

$r = $pdo->query("SELECT lastCheck FROM {$DB_PREFIX}rel_mitgliedschaft");
if ($r === false) {
  $pdo->query("ALTER TABLE {$DB_PREFIX}rel_mitgliedschaft ADD COLUMN lastCheck DATE NULL;") or httperror(print_r($pdo->errorInfo(),true));
}

# dataTables view
$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}person_is_active");
if ($r === false) {
  $pdo->query("CREATE VIEW {$DB_PREFIX}person_is_active AS
     SELECT DISTINCT rm.person_id as person_id
       FROM {$DB_PREFIX}rel_mitgliedschaft rm
      WHERE (rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE);")
  or httperror(print_r($pdo->errorInfo(),true));
}

#$pdo->query("DROP VIEW {$DB_PREFIX}person_has_unimail");
$r = $pdo->query("SELECT * FROM {$DB_PREFIX}person_has_unimail");
if ($r === false) {
  $pdo->query("CREATE VIEW {$DB_PREFIX}person_has_unimail AS
     SELECT DISTINCT pe.person_id as person_id
       FROM {$DB_PREFIX}person_email pe
      WHERE email LIKE '%@tu-ilmenau.de';")
  or httperror(print_r($pdo->errorInfo(),true));
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}person_can_login");
if ($r === false) {
  $pdo->query("CREATE VIEW {$DB_PREFIX}person_can_login AS
SELECT DISTINCT p.id as person_id, p.canLogin XOR (rm.gremium_id IS NOT NULL) as canLoginCurrent
       FROM {$DB_PREFIX}person p
            JOIN {$DB_PREFIX}gruppe g ON (p.canLogin OR g.name = 'canLogin') AND (NOT p.canLogin OR g.name = 'cannotLogin')
            LEFT JOIN {$DB_PREFIX}rel_rolle_gruppe rrg ON rrg.gruppe_id = g.id
            LEFT JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON p.id = rm.person_id AND (rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE) AND rrg.rolle_id = rm.rolle_id;")
  or httperror(print_r($pdo->errorInfo(),true));
}

$r = $pdo->query("DROP VIEW {$DB_PREFIX}person_current");
$r = $pdo->query("SELECT hasUniMail FROM {$DB_PREFIX}person_current LIMIT 1");
#$r = $pdo->query("SELECT * FROM {$DB_PREFIX}person_current LIMIT 1");
if ($r === false) {
  $pdo->query("CREATE OR REPLACE VIEW {$DB_PREFIX}person_current AS
SELECT p.*, GROUP_CONCAT(DISTINCT pe.email ORDER BY pe.srt) as email, ap.person_id IS NOT NULL as active, lp.canLoginCurrent as canLoginCurrent, hu.person_id IS NOT NULL as hasUniMail
   FROM {$DB_PREFIX}person p
        LEFT JOIN {$DB_PREFIX}person_email pe ON p.id = pe.person_id
        LEFT JOIN {$DB_PREFIX}person_is_active ap ON ap.person_id = p.id
        LEFT JOIN {$DB_PREFIX}person_can_login lp ON lp.person_id = p.id
        LEFT JOIN {$DB_PREFIX}person_has_unimail hu ON hu.person_id = p.id
GROUP BY p.id;")
  or httperror(print_r($pdo->errorInfo(),true));
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}gremium_has_members");
if ($r === false) {
  $pdo->query("CREATE VIEW {$DB_PREFIX}gremium_has_members AS
SELECT DISTINCT rm.gremium_id FROM {$DB_PREFIX}rel_mitgliedschaft rm WHERE (von IS NULL OR von <= CURRENT_DATE) AND (bis IS NULL OR bis >= CURRENT_DATE)
   ")
  or httperror(print_r($pdo->errorInfo(),true));
}
$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}gremium_has_members_in_inactive_roles");
if ($r === false) {
  $pdo->query("CREATE VIEW {$DB_PREFIX}gremium_has_members_in_inactive_roles AS
SELECT DISTINCT rm.gremium_id FROM {$DB_PREFIX}rel_mitgliedschaft rm INNER JOIN {$DB_PREFIX}rolle r ON r.id = rm.rolle_id WHERE (NOT r.active) AND (von IS NULL OR von <= CURRENT_DATE) AND (bis IS NULL OR bis >= CURRENT_DATE)
   ")
  or httperror(print_r($pdo->errorInfo(),true));
}

$r = $pdo->query("SELECT fullname FROM {$DB_PREFIX}gremium_current limit 1");
if ($r === false) {
  $pdo->query("CREATE OR REPLACE VIEW {$DB_PREFIX}gremium_current AS
    SELECT g.*, (gu.gremium_id IS NOT NULL) as has_members, (gui.gremium_id IS NOT NULL) as has_members_in_inactive_roles, TRIM(CONCAT_WS(' ',g.name,g.fakultaet,g.studiengang,g.studiengangabschluss)) as fullname
      FROM {$DB_PREFIX}gremium g
           LEFT JOIN {$DB_PREFIX}gremium_has_members gu ON gu.gremium_id = g.id
           LEFT JOIN {$DB_PREFIX}gremium_has_members_in_inactive_roles gui ON gui.gremium_id = g.id
   ")
  or httperror(print_r($pdo->errorInfo(),true));
}

$r = $pdo->query("SELECT rolle_wahlPeriodeDays FROM {$DB_PREFIX}rolle_searchable");
if ($r === false) {
  $pdo->query("CREATE OR REPLACE VIEW {$DB_PREFIX}rolle_searchable AS
    SELECT TRIM(CONCAT_WS(' ',r.name,g.name,g.fakultaet,g.studiengang,g.studiengangabschluss)) as fullname,
           r.id as rolle_id, r.name as rolle_name, r.active as rolle_active, r.spiGroupId as rolle_spiGroupId, r.numPlatz as rolle_numPlatz, r.wahlDurchWikiSuffix as rolle_wahlDurchWikiSuffix, r.wahlPeriodeDays as rolle_wahlPeriodeDays,
           g.id as gremium_id, g.name as gremium_name, g.fakultaet as gremium_fakultaet, g.studiengang as gremium_studiengang, g.studiengangabschluss as gremium_studiengangabschluss, g.wiki_members as wiki_members, g.wiki_members_table as wiki_members_table, g.wiki_members_fulltable as wiki_members_fulltable, g.wiki_members_fulltable2 as wiki_members_fulltable2, g.active as gremium_active,
           r.id as id, (r.active AND g.active) as active
      FROM {$DB_PREFIX}gremium g
           INNER JOIN {$DB_PREFIX}rolle r ON r.gremium_id = g.id
   ")
  or httperror(print_r($pdo->errorInfo(),true));
}

$r = $pdo->query("SELECT rolle_wahlPeriodeDays FROM {$DB_PREFIX}rolle_searchable_mailingliste");
if ($r === false) {
  $pdo->query("CREATE OR REPLACE VIEW {$DB_PREFIX}rolle_searchable_mailingliste AS
    SELECT TRIM(CONCAT_WS(' ',r.name,g.name,g.fakultaet,g.studiengang,g.studiengangabschluss)) as fullname,
           r.id as rolle_id, r.name as rolle_name, r.active as rolle_active, r.spiGroupId as rolle_spiGroupId, r.numPlatz as rolle_numPlatz, r.wahlDurchWikiSuffix as rolle_wahlDurchWikiSuffix, r.wahlPeriodeDays as rolle_wahlPeriodeDays,
           g.id as gremium_id, g.name as gremium_name, g.fakultaet as gremium_fakultaet, g.studiengang as gremium_studiengang, g.studiengangabschluss as gremium_studiengangabschluss, g.wiki_members as wiki_members, g.wiki_members_table as wiki_members_table, g.wiki_members_fulltable as wiki_members_fulltable, g.wiki_members_fulltable2 as wiki_members_fulltable2, g.active as gremium_active,
           r.id as id, (r.active AND g.active) as active,
           m.id as mailingliste_id,
           (rrm.rolle_id IS NOT NULL) AS in_rel
      FROM {$DB_PREFIX}gremium g
           INNER JOIN {$DB_PREFIX}rolle r ON r.gremium_id = g.id
           JOIN {$DB_PREFIX}mailingliste m
           LEFT JOIN {$DB_PREFIX}rel_rolle_mailingliste rrm ON rrm.rolle_id = r.id AND rrm.mailingliste_id = m.id
   ")
  or httperror(print_r($pdo->errorInfo(),true));
}

function dbQuote($string , $parameter_type = NULL ) {
  global $pdo;
  if ($parameter_type === NULL)
    return $pdo->quote($string);
  else
    return $pdo->quote($string, $parameter_type);
}

function logThisAction() {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}log (action, responsible) VALUES (?, ?)");
  $query->execute(Array($_REQUEST["action"], getUsername())) or httperror(print_r($query->errorInfo(),true));
  $logId = $pdo->lastInsertId();
  foreach ($_REQUEST as $key => $value) {
    $key = "request_$key";
    logAppend($logId, $key, $value);
  }
  return $logId;
}

function logAppend($logId, $key, $value) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}log_property (log_id, name, value) VALUES (?, ?, ?)");
  if (is_array($value)) $value = print_r($value, true);
  $query->execute(Array($logId, $key, $value)) or httperror(print_r($query->errorInfo(),true));
}

function getPersonDetailsById($id) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT p.*, GROUP_CONCAT(DISTINCT pe.email ORDER BY pe.srt) as email FROM {$DB_PREFIX}person p LEFT JOIN {$DB_PREFIX}person_email pe ON p.id = pe.person_id WHERE p.id = ? GROUP BY p.id");
  $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
  if ($query->rowCount() == 0) return false;
  return $query->fetch(PDO::FETCH_ASSOC);
}

function getPersonDetailsByMail($mail) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("
SELECT p.*, GROUP_CONCAT(DISTINCT pe1.email ORDER BY pe1.srt) as email
  FROM {$DB_PREFIX}person p
       LEFT JOIN {$DB_PREFIX}person_email pe1 ON p.id = pe1.person_id
       LEFT JOIN {$DB_PREFIX}person_email pe2 ON p.id = pe2.person_id
 WHERE pe2.email LIKE ?
 GROUP BY p.id");
  $query->execute(Array($mail)) or httperror(print_r($query->errorInfo(),true));
  if ($query->rowCount() == 0) return false;
  return $query->fetch(PDO::FETCH_ASSOC);
}

function getPersonDetailsByUsername($username) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("
SELECT p.*, GROUP_CONCAT(DISTINCT pe.email ORDER BY pe.srt) as email
  FROM {$DB_PREFIX}person p
       LEFT JOIN {$DB_PREFIX}person_email pe ON pe.person_id = p.id
 WHERE username LIKE ?
GROUP BY p.id");
  $query->execute(Array($username)) or httperror(print_r($query->errorInfo(),true));
  if ($query->rowCount() == 0) return false;
  return $query->fetch(PDO::FETCH_ASSOC);
}

function getPersonRolle($personId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT rm.id AS id, g.id AS gremium_id, g.name as gremium_name, g.fakultaet as gremium_fakultaet, g.studiengang as gremium_studiengang, g.studiengangabschluss as gremium_studiengangabschluss, g.wiki_members as gremium_wiki_members, g.wiki_members_table as gremium_wiki_members_table, g.wiki_members_fulltable as gremium_wiki_members_fulltable, g.wiki_members_fulltable as gremium_wiki_members_fulltable2, r.id as rolle_id, r.name as rolle_name, rm.von as von, rm.bis as bis, rm.beschlussAm as beschlussAm, rm.beschlussDurch as beschlussDurch, rm.kommentar as kommentar, rm.lastCheck as lastCheck, ((rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE)) as active FROM {$DB_PREFIX}gremium g INNER JOIN {$DB_PREFIX}rolle r ON g.id = r.gremium_id INNER JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON rm.rolle_id = r.id AND rm.gremium_id = g.id WHERE rm.person_id = ? ORDER BY g.name, g.fakultaet, g.studiengang, g.studiengangabschluss, r.name");
  $query->execute(Array($personId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getPersonGruppe($personId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT g.* FROM {$DB_PREFIX}gruppe g INNER JOIN {$DB_PREFIX}rel_rolle_gruppe r ON g.id = r.gruppe_id INNER JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON (rm.rolle_id = r.rolle_id) AND ((rm.von IS NULL) OR (rm.von <= CURRENT_DATE)) AND ((rm.bis IS NULL) OR (rm.bis >= CURRENT_DATE)) WHERE rm.person_id = ? ORDER BY g.name");
  $query->execute(Array($personId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getPersonMailingliste($personId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT m.* FROM {$DB_PREFIX}mailingliste m INNER JOIN {$DB_PREFIX}rel_rolle_mailingliste r ON m.id = r.mailingliste_id INNER JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON rm.rolle_id = r.rolle_id AND (rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE) WHERE rm.person_id = ? ORDER BY RIGHT(m.address, LENGTH(m.address) - POSITION( '@' in m.address)), LEFT(m.address, POSITION( '@' in m.address))");
  $query->execute(Array($personId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function setPersonUsername($personId, $username) {
  global $pdo, $DB_PREFIX;
  # username needs to match ^[a-z][-a-z0-9_]*\$
  $username  = preg_replace('/^[^a-z]*/', '', preg_replace('/[^-a-z0-9_]/', '', strtolower($username)));
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}person SET username = ? WHERE id = ?");
  return $query->execute(Array($username, $personId)) or httperror(print_r($query->errorInfo(),true));
}

function setPersonPassword($personId, $password) {
  global $pdo, $DB_PREFIX, $pwObj;
  if (empty($password)) {
    $passwordHash = NULL;
  } else {
    $passwordHash = @$pwObj->createPasswordHash($password);
  }
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}person SET password = ? WHERE id = ?");
  return $query->execute(Array($passwordHash, $personId)) or httperror(print_r($query->errorInfo(),true));
}

function getMailinglisten() {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT * FROM {$DB_PREFIX}mailingliste m ORDER BY RIGHT(m.address, LENGTH(m.address) - POSITION( '@' in m.address)), LEFT(m.address, POSITION( '@' in m.address))");
  $query->execute() or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getMailinglisteById($mlId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT * FROM {$DB_PREFIX}mailingliste m WHERE id = ?");
  $query->execute([$mlId]) or httperror(print_r($query->errorInfo(),true));
  return $query->fetch(PDO::FETCH_ASSOC);
}

function dbMailinglisteInsert($address, $url, $password) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("INSERT {$DB_PREFIX}mailingliste (address, url, password) VALUES ( ?, ?, ?)");
  $ret = $query->execute(Array($address, $url, $password)) or httperror(print_r($query->errorInfo(),true));
  if ($ret === false)
    return $ret;
  return $pdo->lastInsertId();
}

function dbMailinglisteUpdate($id, $address, $url, $password) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}mailingliste SET address = ?, url = ?, password = ? WHERE id = ?");
  return $query->execute(Array($address, $url, $password, $id)) or httperror(print_r($query->errorInfo(),true));
}

function dbMailinglisteDelete($id) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("DELETE FROM {$DB_PREFIX}mailingliste WHERE id = ?");
  return $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
}

function getMailinglisteRolle($mlId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT g.id AS gremium_id, g.name as gremium_name, g.fakultaet as gremium_fakultaet, g.studiengang as gremium_studiengang, g.studiengangabschluss as gremium_studiengangabschluss, g.wiki_members as gremium_wiki_members, g.wiki_members_table as gremium_wiki_members_table, g.wiki_members_fulltable as gremium_wiki_members_fulltable, g.wiki_members_fulltable as gremium_wiki_members_fulltable2, r.id as rolle_id, r.name as rolle_name FROM {$DB_PREFIX}gremium g INNER JOIN {$DB_PREFIX}rolle r ON g.id = r.gremium_id INNER JOIN {$DB_PREFIX}rel_rolle_mailingliste rm ON rm.rolle_id = r.id WHERE rm.mailingliste_id = ? ORDER BY g.name, g.fakultaet, g.studiengang, g.studiengangabschluss, r.name");
  $query->execute(Array($mlId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function dbMailinglisteDropRolle($mlId, $rolleId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("DELETE FROM {$DB_PREFIX}rel_rolle_mailingliste WHERE mailingliste_id = ? AND rolle_id = ?");
  return $query->execute(Array($mlId, $rolleId)) or httperror(print_r($query->errorInfo(),true));
}

function dbMailinglisteInsertRolle($mlId, $rolleId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}rel_rolle_mailingliste (mailingliste_id, rolle_id) VALUES (?, ?)");
  return $query->execute(Array($mlId, $rolleId)) or httperror(print_r($query->errorInfo(),true));
}

function getGremiumById($gremiumId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT g.* FROM {$DB_PREFIX}gremium g WHERE g.id = ?");
  $query->execute(Array($gremiumId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetch(PDO::FETCH_ASSOC);
}

function getRolleByGremiumId($gremiumId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT r.* FROM {$DB_PREFIX}rolle r WHERE r.gremium_id = ?");
  $query->execute(Array($gremiumId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getRolleById($rolleId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT r.* FROM {$DB_PREFIX}rolle r WHERE r.id = ?");
  $query->execute(Array($rolleId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetch(PDO::FETCH_ASSOC);
}

function getAlleRolle() {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT g.id AS gremium_id, g.name as gremium_name, g.fakultaet as gremium_fakultaet, g.studiengang as gremium_studiengang, g.studiengangabschluss as gremium_studiengangabschluss, g.wiki_members as gremium_wiki_members, g.wiki_members_table as gremium_wiki_members_table, g.wiki_members_fulltable as gremium_wiki_members_fulltable, g.wiki_members_fulltable2 as gremium_wiki_members_fulltable2, g.active as gremium_active, r.id as rolle_id, r.name as rolle_name, r.active as rolle_active, r.spiGroupId as rolle_spiGroupId, r.numPlatz as rolle_numPlatz, r.wahlDurchWikiSuffix as rolle_wahlDurchWikiSuffix, r.wahlPeriodeDays as rolle_wahlPeriodeDays, (rm.id IS NOT NULL) as rolle_hat_mitglied FROM {$DB_PREFIX}gremium g LEFT JOIN {$DB_PREFIX}rolle r LEFT JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON rm.rolle_id = r.id AND (rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE) ON g.id = r.gremium_id ORDER BY g.name, g.fakultaet, g.studiengang, g.studiengangabschluss, g.id, r.name, r.id");
  $query->execute(Array()) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getAllePerson() {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT p.*, GROUP_CONCAT(DISTINCT pe.email ORDER BY pe.srt) as email, (rm.id IS NOT NULL) AS active FROM {$DB_PREFIX}person p LEFT JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON p.id = rm.person_id AND (rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE) LEFT JOIN {$DB_PREFIX}person_email pe ON p.id = pe.person_id GROUP BY p.id ORDER BY p.name");
  $query->execute(Array()) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function dbPersonMerge($person_id, $target_id) {
  global $pdo, $DB_PREFIX;
  $pdo->beginTransaction() or httperror(print_r($query->errorInfo(),true));

  $query = $pdo->prepare("UPDATE {$DB_PREFIX}rel_mitgliedschaft SET person_id = ? WHERE person_id = ?");
  $query->execute([$target_id, $person_id]) or httperror(print_r($query->errorInfo(),true));

  $query = $pdo->prepare("UPDATE {$DB_PREFIX}person_email SET person_id = ? WHERE person_id = ?");
  $query->execute([$target_id, $person_id]) or httperror(print_r($query->errorInfo(),true));

  $query = $pdo->prepare("DELETE FROM {$DB_PREFIX}person WHERE id = ?");
  $query->execute([$person_id]) or httperror(print_r($query->errorInfo(),true));

  $pdo->commit() or httperror(print_r($query->errorInfo(),true));

  return true;
}

function dbPersonDelete($id) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("DELETE FROM {$DB_PREFIX}person WHERE id = ?");
  return $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
}

function dbPersonDisable($id) {
  global $pdo, $DB_PREFIX;
  # disable logins
  $pdo->beginTransaction() or httperror(print_r($pdo->errorInfo(),true));
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}person SET canLogin = 0 WHERE id = ?");
  $ret1 = $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
  # terminate memberships
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}rel_mitgliedschaft SET bis = subdate(current_date, 1) WHERE person_id = ? AND (bis IS NULL OR bis >= CURRENT_DATE)");
  $ret2 = $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
  $ret3 = $pdo->commit() or httperror(print_r($pdo->errorInfo(),true));
  return $ret1 && $ret2 && $ret3;
}

function dbPersonUpdate($id,$name,$emails,$unirzlogin,$username,$password,$canlogin) {
  global $pdo, $DB_PREFIX, $pwObj;
  if (empty($name)) $name = NULL;
  if (empty($unirzlogin)) $unirzlogin = NULL;
  if (empty($username)) $username = NULL;

  $numEmail = 0; $tmp = [];
  foreach ($emails as $i => $email) {
    $email = strtolower(trim($email));
    $emails[$i] = $email;

    if ($email == "") continue;

    $numEmail++;
    if (!isValidEmail($email)) {
      httperror("Ungültige eMail-Adresse: ".htmlspecialchars($email));
      return false;
    }

    $tmp[] = $email;
  }
  if ($numEmail == 0) {
    httperror("Ungültige eMail-Adresse");
    return false;
  }
  $emails = array_unique($tmp);

  $pdo->beginTransaction() or httperror(print_r($pdo->errorInfo(),true));
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}person SET name = ?, unirzlogin = ?, username = ?, canLogin = ? WHERE id = ?");
  $ret1 = $query->execute([$name, $unirzlogin, $username, $canlogin, $id]) or httperror(print_r($query->errorInfo(),true));
  if (empty($password)) {
    $ret2 = true;
  } else {
    $passwordHash = @$pwObj->createPasswordHash($password);
    $query = $pdo->prepare("UPDATE {$DB_PREFIX}person SET password = ? WHERE id = ?");
    $ret2 = $query->execute(Array($passwordHash, $id)) or httperror(print_r($query->errorInfo(),true));
  }
  $query = $pdo->prepare("SELECT srt, email FROM {$DB_PREFIX}person_email WHERE person_id = ?");
  $ret3 = $query->execute([$id]) or httperror(print_r($query->errorInfo(),true));
  if ($ret3 !== false) {
    $tmp = $query->fetchAll(PDO::FETCH_ASSOC);
    $cemails = [];
    foreach ($tmp as $r) {
      $cemails[$r["srt"]] = $r["email"];
    }
  }
  foreach ($cemails as $i => $email) {
    if (!$ret3) continue;
    if (isset($emails[$i]) && ($emails[$i] == $email)) continue;
    $query = $pdo->prepare("DELETE FROM {$DB_PREFIX}person_email WHERE person_id = ? AND email = ? AND srt = ?");
    $ret3 = $query->execute([$id, $email, $i]) or httperror(print_r($query->errorInfo(),true));
  }
  foreach ($emails as $i => $email) {
    if (!$ret3) continue;
    if (isset($cemails[$i]) && ($cemails[$i] == $email)) continue;
    $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}person_email (person_id, srt, email) VALUE (?, ?, ?)");
    $ret3 = $query->execute([$id, $i, $email]) or httperror(print_r($query->errorInfo(),true));
  }
  if (!($ret1 && $ret2 && $ret3)) {
    $pdo->rollback();
    httperror("Failed");
  }
  $ret4 = $pdo->commit() or httperror(print_r($pdo->errorInfo(),true));
  return $ret1 && $ret2 && $ret3 && $ret4;
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL)
        && preg_match('/@.+\./', $email);
}

function dbPersonInsert($name,$emails,$unirzlogin,$username,$password,$canlogin, $quiet=false) {
  global $pdo, $DB_PREFIX, $pwObj;
  if (empty($name)) $name = NULL;
  if (empty($unirzlogin)) $unirzlogin = NULL;
  if (empty($username)) $username = NULL;
  if (empty($password)) { $passwordHash = NULL;  } else { $passwordHash = @$pwObj->createPasswordHash($password); }
  if (!is_array($emails)) $emails = [$emails];
  $numEmail = 0;
  foreach ($emails as $i => $email) {
    $email = strtolower(trim($email));
    $emails[$i] = $email;
    if ($email == "") continue;
    $numEmail++;
    if (!isValidEmail($email)) {
      httperror("Ungültige eMail-Adresse");
      return false;
    }
  }
  if ($numEmail == 0) {
    httperror("Ungültige eMail-Adresse");
    return false;
  }
  $emails = array_unique($emails);

  $ret = $pdo->beginTransaction();
  if (!$ret && !$quiet) { httperror(print_r($pdo->errorInfo(),true)); }
  if ($ret === false) {
    httperror("DB ERROR: CANNOT START TRANSACTION");
    return $ret;
  }

  $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}person (name, unirzlogin, username, password, canLogin) VALUES (?, ?, ?, ?, ?)");
  $ret = $query->execute(Array($name, $unirzlogin, $username, $passwordHash, $canlogin));
  if (!$ret && !$quiet) { httperror(print_r($query->errorInfo(),true)); }
  if ($ret === false) {
    $pdo->rollback();
    return $ret;
  }
  $person_id = $pdo->lastInsertId();

  $i = 0;
  foreach ($emails as $email) {
    if ($email == "") continue;

    $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}person_email (person_id, srt, email) VALUES (?, ?, ?)");
    $ret = $query->execute([$person_id, $i, $email]);
    if (!$ret && !$quiet) { httperror(print_r($query->errorInfo(),true)); }
    if ($ret === false) {
      $pdo->rollback();
      return $ret;
    }

    $i++;
  }

  $ret = $pdo->commit();
  if (!$ret && !$quiet) { httperror(print_r($pdo->errorInfo(),true)); }
  if ($ret === false) {
    return $ret;
  }
  return $person_id;
}

function dbPersonInsertRolle($person_id,$rolle_id,$von,$bis,$beschlussAm,$beschlussDurch,$lastCheck,$kommentar) {
  global $pdo, $DB_PREFIX;
  if (empty($von)) $von = NULL;
  if (empty($bis)) $bis = NULL;
  if (empty($beschlussAm)) $beschlussAm = NULL;
  if (empty($beschlussDurch)) $beschlussDurch = NULL;
  if (empty($lastCheck)) $lastCheck = NULL;
  if (empty($kommentar)) $kommentar = NULL;
  if ($von !== NULL && $bis !== NULL) {
    $query = $pdo->prepare("SELECT cast(? AS DATE) <= cast(? AS DATE) AS valid");
    $query->execute(Array($von, $bis)) or httperror (print_r($query->errorInfo(),true));
    $validDates = (bool) $query->fetchColumn();
    if (!$validDates) {
      httperror("Ungültige Bereichsangabe (von > bis)");
      return false;
    }
  }
  $query = $pdo->prepare("SELECT gremium_id FROM {$DB_PREFIX}rolle WHERE id = ?");
  $query->execute(Array($rolle_id)) or httperror (print_r($query->errorInfo(),true));
  $gremium_id = $query->fetchColumn();
  $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}rel_mitgliedschaft (person_id, rolle_id, gremium_id, von, bis, beschlussAm, beschlussDurch, lastCheck, kommentar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
  return $query->execute(Array($person_id,$rolle_id,$gremium_id,$von,$bis,$beschlussAm,$beschlussDurch,$lastCheck,$kommentar)) or httperror(print_r($query->errorInfo(),true));
}

function dbPersonUpdateRolle($id, $person_id,$rolle_id,$von,$bis,$beschlussAm,$beschlussDurch,$lastCheck,$kommentar) {
  global $pdo, $DB_PREFIX;
  if (empty($von)) $von = NULL;
  if (empty($bis)) $bis = NULL;
  if (empty($beschlussAm)) $beschlussAm = NULL;
  if (empty($beschlussDurch)) $beschlussDurch = NULL;
  if (empty($lastCheck)) $lastCheck = NULL;
  if (empty($kommentar)) $kommentar = NULL;
  if ($von !== NULL && $bis !== NULL) {
    $query = $pdo->prepare("SELECT cast(? as DATE) <= cast(? as DATE) AS valid");
    $query->execute(Array($von, $bis)) or httperror (print_r($query->errorInfo(),true));
    $validDates = (bool) $query->fetchColumn();
    if (!$validDates) {
      httperror("Ungültige Bereichsangabe (von > bis)");
      return false;
    }
  }

  $query = $pdo->prepare("SELECT gremium_id FROM {$DB_PREFIX}rolle WHERE id = ?");
  $query->execute(Array($rolle_id)) or httperror (print_r($query->errorInfo(),true));
  $gremium_id = $query->fetchColumn();
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}rel_mitgliedschaft SET person_id = ?, rolle_id = ?, gremium_id = ?, von = ?, bis = ?, beschlussAm = ?, beschlussDurch = ?, lastCheck = ?, kommentar = ? WHERE id = ?");
  return $query->execute(Array($person_id,$rolle_id,$gremium_id,$von,$bis,$beschlussAm,$beschlussDurch,$lastCheck,$kommentar,$id)) or httperror(print_r($query->errorInfo(),true));
}

function dbPersonDeleteRolle($id) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("DELETE FROM {$DB_PREFIX}rel_mitgliedschaft WHERE id = ?");
  return $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
}

function dbPersonDisableRolle($id, $bis = NULL) {
  global $pdo, $DB_PREFIX;
  if (empty($bis)) $bis = date("Y-m-d", strtotime("yesterday"));
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}rel_mitgliedschaft SET bis = STR_TO_DATE(?, '%Y-%m-%d') WHERE id = ? AND (bis IS NULL OR bis > STR_TO_DATE(?, '%Y-%m-%d'))");
  return $query->execute(Array($bis,$id,$bis)) or httperror(print_r($query->errorInfo(),true));
}

function getGruppeRolle($grpId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT g.id AS gremium_id, g.name as gremium_name, g.fakultaet as gremium_fakultaet, g.studiengang as gremium_studiengang, g.studiengangabschluss as gremium_studiengangabschluss, g.wiki_members as gremium_wiki_members, g.wiki_members_table as gremium_wiki_members_table, g.wiki_members_fulltable as gremium_wiki_members_fulltable, g.wiki_members_fulltable2 as gremium_wiki_members_fulltable2, r.id as rolle_id, r.name as rolle_name FROM {$DB_PREFIX}gremium g INNER JOIN {$DB_PREFIX}rolle r ON g.id = r.gremium_id INNER JOIN {$DB_PREFIX}rel_rolle_gruppe rg ON rg.rolle_id = r.id WHERE rg.gruppe_id = ? ORDER BY g.name, g.fakultaet, g.studiengang, g.studiengangabschluss, r.name");
  $query->execute(Array($grpId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getGruppeById($grpId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT * FROM {$DB_PREFIX}gruppe WHERE id = ?");
  $query->execute(Array($grpId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetch(PDO::FETCH_ASSOC);
}

function getAlleGruppe() {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT * FROM {$DB_PREFIX}gruppe ORDER BY name");
  $query->execute(Array()) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function dbGruppeInsert($name, $beschreibung) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("INSERT {$DB_PREFIX}gruppe (name, beschreibung) VALUES ( ?, ?)");
  $ret = $query->execute(Array($name, $beschreibung)) or httperror(print_r($query->errorInfo(),true));
  if ($ret === false)
    return $ret;
  return $pdo->lastInsertId();
}

function dbGruppeUpdate($id, $name, $beschreibung) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}gruppe SET name = ?, beschreibung = ? WHERE id = ?");
  return $query->execute(Array($name, $beschreibung, $id)) or httperror(print_r($query->errorInfo(),true));
}

function dbGruppeDelete($id) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("DELETE FROM {$DB_PREFIX}gruppe WHERE id = ?");
  return $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
}

function dbGruppeDropRolle($grpId, $rolleId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("DELETE FROM {$DB_PREFIX}rel_rolle_gruppe WHERE gruppe_id = ? AND rolle_id = ?");
  return $query->execute(Array($grpId, $rolleId)) or httperror(print_r($query->errorInfo(),true));
}

function dbGruppeInsertRolle($grpId, $rolleId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}rel_rolle_gruppe (gruppe_id, rolle_id) VALUES (?, ?)");
  return $query->execute(Array($grpId, $rolleId)) or httperror(print_r($query->errorInfo(),true));
}

function dbGremiumInsert($name, $fakultaet, $studiengang, $studiengangabschluss, $wiki_members, $wiki_members_table, $wiki_members_fulltable, $active, $wiki_members_fulltable2) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("INSERT {$DB_PREFIX}gremium (name, fakultaet, studiengang, studiengangabschluss, wiki_members, wiki_members_table, wiki_members_fulltable, active, wiki_members_fulltable2) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $ret = $query->execute(Array($name, $fakultaet, $studiengang, $studiengangabschluss, $wiki_members, $wiki_members_table, $wiki_members_fulltable, $active, $wiki_members_fulltable2)) or httperror(__FILE__.":".__LINE__." ".print_r($query->errorInfo(),true));
  if ($ret === false)
    return $ret;
  return $pdo->lastInsertId();
}

function dbGremiumUpdate($id, $name, $fakultaet, $studiengang, $studiengangabschluss, $wiki_members, $wiki_members_table, $wiki_members_fulltable, $active, $wiki_members_fulltable2) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}gremium SET name = ?, fakultaet = ?, studiengang = ?, studiengangabschluss = ?, wiki_members = ?, wiki_members_table = ?, wiki_members_fulltable = ?, active = ?, wiki_members_fulltable = ? WHERE id = ?");
  return $query->execute(Array($name, $fakultaet, $studiengang, $studiengangabschluss, $wiki_members, $wiki_members_table, $wiki_members_fulltable, $active, $wiki_members_fulltable2, $id)) or httperror(print_r($query->errorInfo(),true));
}

function dbGremiumDelete($id) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("DELETE FROM {$DB_PREFIX}gremium WHERE id = ?");
  return $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
}

function dbGremiumDisable($id) {
  global $pdo, $DB_PREFIX;
  $pdo->beginTransaction() or httperror(print_r($pdo->errorInfo(),true));
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}gremium SET active = 0 WHERE id = ?");
  $ret1 = $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
  # terminate memberships
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}rel_mitgliedschaft SET bis = subdate(current_date, 1) WHERE gremium_id = ? AND (bis IS NULL OR bis >= CURRENT_DATE)");
  $ret2 = $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
  $ret3 = $pdo->commit() or httperror(print_r($pdo->errorInfo(),true));
  return $ret1 && $ret2 && $ret3;
}

function dbGremiumInsertRolle($gremium_id, $name, $active, $spiGroupId, $numPlatz, $wahlDurchWikiSuffix, $wahlPeriodeDays) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}rolle (gremium_id, name, active, spiGroupId, numPlatz, wahlDurchWikiSuffix, wahlPeriodeDays) VALUES ( ?, ?, ?, ?, ?, ?, ?)");
  $ret = $query->execute(Array($gremium_id, $name, $active, $spiGroupId, $numPlatz, $wahlDurchWikiSuffix, $wahlPeriodeDays)) or httperror(print_r($query->errorInfo(),true));
  if ($ret === false)
    return $ret;
  return $pdo->lastInsertId();
}

function dbGremiumUpdateRolle($id, $name, $active, $spiGroupId, $numPlatz, $wahlDurchWikiSuffix, $wahlPeriodeDays) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}rolle SET name = ?, active = ?, spiGroupId = ?, numPlatz = ?, wahlDurchWikiSuffix = ?, wahlPeriodeDays = ? WHERE id = ?");
  return $query->execute(Array($name, $active, $spiGroupId, $numPlatz, $wahlDurchWikiSuffix, $wahlPeriodeDays, $id)) or httperror(print_r($query->errorInfo(),true));
}

function dbGremiumDeleteRolle($id) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("DELETE FROM {$DB_PREFIX}rolle WHERE id = ?");
  return $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
}

function dbGremiumDisableRolle($id) {
  global $pdo, $DB_PREFIX;
  $pdo->beginTransaction() or httperror(print_r($pdo->errorInfo(),true));
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}rolle SET active = 0 WHERE id = ?");
  $ret1 = $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
  # terminate memberships
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}rel_mitgliedschaft SET bis = subdate(current_date, 1) WHERE rolle_id = ? AND (bis IS NULL OR bis >= CURRENT_DATE)");
  $ret2 = $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
  $ret3 = $pdo->commit() or httperror(print_r($pdo->errorInfo(),true));
  return $ret1 && $ret2 && $ret3;
}

function getRolleMailinglisten($rolleId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT m.* FROM {$DB_PREFIX}mailingliste m INNER JOIN {$DB_PREFIX}rel_rolle_mailingliste rm ON rm.mailingliste_id = m.id WHERE rm.rolle_id = ? ORDER BY RIGHT(m.address, LENGTH(m.address) - POSITION( '@' in m.address)), LEFT(m.address, POSITION( '@' in m.address))");
  $query->execute(Array($rolleId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getRolleGruppen($rolleId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT grp.* FROM {$DB_PREFIX}gruppe grp INNER JOIN {$DB_PREFIX}rel_rolle_gruppe rgrp ON rgrp.gruppe_id = grp.id WHERE rgrp.rolle_id = ? ORDER BY grp.name");
  $query->execute(Array($rolleId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getRollePersonen($rolleId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT p.*, GROUP_CONCAT(DISTINCT pe.email ORDER BY pe.srt) as email, rp.id AS rel_id, rp.von, rp.bis, rp.beschlussAm, rp.beschlussDurch, rp.lastCheck, rp.kommentar, ((rp.von <= CURRENT_DATE OR rp.von IS NULL) AND (rp.bis >= CURRENT_DATE OR rp.bis IS NULL)) AS active FROM {$DB_PREFIX}person p LEFT JOIN {$DB_PREFIX}person_email pe ON pe.person_id = p.id INNER JOIN {$DB_PREFIX}rel_mitgliedschaft rp ON rp.person_id = p.id WHERE rp.rolle_id = ? GROUP BY rp.id ORDER BY name");
  $query->execute(Array($rolleId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getActiveMitgliedschaftByMail($email, $rolleId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT rm.* FROM {$DB_PREFIX}person p INNER JOIN {$DB_PREFIX}person_email pe ON pe.person_id = p.id INNER JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON rm.person_id = p.id WHERE email = ? AND rm.rolle_id = ? AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE) AND (rm.von IS NULL OR rm.von <= CURRENT_DATE) ORDER BY rm.id");
  $query->execute(Array($email, $rolleId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getAllMitgliedschaft() {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT g.name as gremium_name, g.fakultaet as gremium_fakultaet, g.studiengang as gremium_studiengang, g.studiengangabschluss as gremium_studiengangabschluss, r.name as rolle_name, email as person_email, p.name as person_name, p.username as person_username, rm.von as von, rm.bis as bis, rm.beschlussAm as beschlussAm, rm.beschlussDurch as beschlussDurch, rm.lastCheck, rm.kommentar as kommentar, ((rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE)) AS aktiv FROM {$DB_PREFIX}person p LEFT JOIN {$DB_PREFIX}person_email pe ON pe.person_id = p.id INNER JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON rm.person_id = p.id INNER JOIN {$DB_PREFIX}gremium g ON g.id = rm.gremium_id INNER JOIN {$DB_PREFIX}rolle r ON r.id = rm.rolle_id ORDER BY g.name, g.id, r.name, r.id, RIGHT(email, LENGTH(email) - POSITION( '@' in email)), LEFT(email, POSITION( '@' in email))");
  $query->execute() or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getMitgliedschaftById($rel_id) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT rm.* FROM {$DB_PREFIX}rel_mitgliedschaft rm WHERE id = ?");
  $query->execute([$rel_id]) or httperror(print_r($query->errorInfo(),true));
  return $query->fetch(PDO::FETCH_ASSOC);
}

function getMailinglistePerson($mlId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT pe.email FROM {$DB_PREFIX}person p INNER JOIN {$DB_PREFIX}person_email_primary pe ON p.id = pe.person_id INNER JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON rm.person_id = p.id AND (rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE) INNER JOIN {$DB_PREFIX}rel_rolle_mailingliste rrm ON rm.rolle_id = rrm.rolle_id AND rrm.mailingliste_id = ? ORDER BY email");
  $query->execute(Array($mlId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_COLUMN);
}

function getMailinglistePersonDetails($mlId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT p.*, GROUP_CONCAT(DISTINCT pe.email ORDER BY srt) as email FROM {$DB_PREFIX}person p LEFT JOIN {$DB_PREFIX}person_email_primary pe ON pe.person_id = p.id INNER JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON rm.person_id = p.id AND (rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE) INNER JOIN {$DB_PREFIX}rel_rolle_mailingliste rrm ON rm.rolle_id = rrm.rolle_id AND rrm.mailingliste_id = ? GROUP BY p.name, p.id");
  $query->execute(Array($mlId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

# deprecated
function getGruppePerson($grpId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT pe.email FROM {$DB_PREFIX}person p INNER JOIN {$DB_PREFIX}person_email_primary pe ON pe.person_id = p.id INNER JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON rm.person_id = p.id AND (rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE) INNER JOIN {$DB_PREFIX}rel_rolle_gruppe rrg ON rm.rolle_id = rrg.rolle_id AND rrg.gruppe_id = ? ORDER BY email");
  $query->execute(Array($grpId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_COLUMN);
}

function getGruppePersonDetails($grpId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT p.*, GROUP_CONCAT(DISTINCT pe.email ORDER BY pe.srt) as email FROM {$DB_PREFIX}person p LEFT JOIN {$DB_PREFIX}person_email pe ON pe.person_id = p.id INNER JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON rm.person_id = p.id AND (rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE) INNER JOIN {$DB_PREFIX}rel_rolle_gruppe rrg ON rm.rolle_id = rrg.rolle_id AND rrg.gruppe_id = ? GROUP BY p.id ORDER BY email");
  $query->execute(Array($grpId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getDBDump() {
  global $pdo, $DB_PREFIX;
  $tables = Array("person" => "id",
                  "person_email" => "person_id, email",
                  "gruppe" => "id",
                  "gremium" => "id",
                  "log" => "id",
                  "log_property" => "id",
                  "mailingliste" => "id",
                  "rel_mitgliedschaft" => "id",
                  "rel_rolle_gruppe" => "rolle_id, gruppe_id",
                  "rel_rolle_mailingliste" => "rolle_id, mailingliste_id",
                  "rolle" => "id",
                 );
  $ret = Array();
  foreach ($tables as $t => $s) {
    $query = $pdo->prepare("SELECT * FROM {$DB_PREFIX}{$t} ORDER BY {$s}");
    $query->execute(Array()) or httperror(print_r($query->errorInfo(),true));
    $ret[$t] = $query->fetchAll(PDO::FETCH_ASSOC);
  }
  ksort($ret);
  return $ret;
}

# vim: set expandtab tabstop=8 shiftwidth=8 :


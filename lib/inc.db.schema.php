<?php

$DB_VERSION=filemtime(__FILE__);
$r = $pdo->query("SELECT id FROM {$DB_PREFIX}version WHERE id > ".$DB_VERSION);
if ($r !== false && count($r->fetchAll()) > 0) return;

# Personen
$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}person");
if ($r === false) {
	$pdo->query("CREATE TABLE {$DB_PREFIX}person (
					id INT NOT NULL AUTO_INCREMENT,
					name VARCHAR(128) NOT NULL,
					username VARCHAR(128) NULL,
					password VARCHAR(256) NULL,
					image INT NULL DEFAULT NULL,
					unirzlogin VARCHAR(128) NULL,
					lastLogin TIMESTAMP NULL,
					canLogin BOOLEAN NOT NULL DEFAULT 1,
					UNIQUE (username),
					UNIQUE (unirzlogin),
					PRIMARY KEY (id)
				) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));
	require SGISBASE.'/lib/inc.db.person.php';
} else {
	$r->fetchAll();
}

$r = $pdo->query("SELECT wikiPage FROM {$DB_PREFIX}person LIMIT 1");
if ($r === false) {
	$pdo->query("ALTER TABLE {$DB_PREFIX}person ADD COLUMN wikiPage VARCHAR(256) NULL DEFAULT NULL");
} else {
	$r->fetchAll();
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
	} else {
	$r->fetchAll();
	}

	$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}person_email_primary_ids");
	if ($r === false) {
	$pdo->query("CREATE VIEW {$DB_PREFIX}person_email_primary_ids AS
		SELECT person_id, MIN(srt) as srt
		FROM {$DB_PREFIX}person_email
	GROUP BY person_id;")
	or httperror(print_r($pdo->errorInfo(),true));
} else {
	$r->fetchAll();
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}person_email_primary");
if ($r === false) {
	$pdo->query("CREATE OR REPLACE VIEW {$DB_PREFIX}person_email_primary AS
		SELECT person_id, srt, email
		FROM {$DB_PREFIX}person_email NATURAL JOIN {$DB_PREFIX}person_email_primary_ids
	GROUP BY person_id;")
	or httperror(print_r($pdo->errorInfo(),true));
} else {
	$r->fetchAll();
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}person_contact");
if ($r === false) {
	$pdo->query("CREATE TABLE {$DB_PREFIX}person_contact (
					id INT NOT NULL AUTO_INCREMENT,
					person_id INT NOT NULL,
					type VARCHAR(64) NOT NULL,
					details VARCHAR(128) NOT NULL,
					fromWiki BOOLEAN NOT NULL DEFAULT 0,
					active BOOLEAN NOT NULL DEFAULT 1,
					PRIMARY KEY (id),
					UNIQUE(person_id, type, details, fromWiki),
					INDEX(person_id, type),
					FOREIGN KEY (person_id) REFERENCES {$DB_PREFIX}person(id) ON DELETE CASCADE
				) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));
} else {
        $r->fetchAll();
}

# Gremium & Rollen

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}gremium");
if ($r === false) {
	$pdo->query("CREATE TABLE {$DB_PREFIX}gremium (
					id INT NOT NULL AUTO_INCREMENT,
					name VARCHAR(128) NOT NULL,
					fakultaet VARCHAR(128) NULL,
					studiengang VARCHAR(128) NULL,
					studiengang_english VARCHAR(128) NULL,
					studiengang_short VARCHAR(128) NULL,
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
} else {
        $r->fetchAll();
}

$r = $pdo->query("SELECT wiki_members_table FROM {$DB_PREFIX}gremium");
if ($r === false) {
	$pdo->query("ALTER TABLE {$DB_PREFIX}gremium ADD COLUMN wiki_members_table VARCHAR(128) NULL;") or httperror(print_r($pdo->errorInfo(),true));
} else {
        $r->fetchAll();
}

$r = $pdo->query("SELECT wiki_members_fulltable FROM {$DB_PREFIX}gremium");
if ($r === false) {
	$pdo->query("ALTER TABLE {$DB_PREFIX}gremium ADD COLUMN wiki_members_fulltable VARCHAR(128) NULL;") or httperror(print_r($pdo->errorInfo(),true));
} else {
	$r->fetchAll();
}

$r = $pdo->query("SELECT wiki_members_fulltable2 FROM {$DB_PREFIX}gremium");
if ($r === false) {
	$pdo->query("ALTER TABLE {$DB_PREFIX}gremium ADD COLUMN wiki_members_fulltable2 VARCHAR(128) NULL;") or httperror(print_r($pdo->errorInfo(),true));
} else {
	$r->fetchAll();
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
					wiki_members VARCHAR(128) NULL,
					wiki_members_roleAsColumnTable VARCHAR(128) NULL,
					wiki_members_roleAsColumnTableExtended VARCHAR(128) NULL,
					wiki_members_roleAsMasterTable VARCHAR(128) NULL,
					wiki_members_roleAsMasterTableExtended VARCHAR(128) NULL,
					PRIMARY KEY(id),
					FOREIGN KEY (gremium_id) REFERENCES {$DB_PREFIX}gremium(id) ON DELETE CASCADE,
					UNIQUE(gremium_id, name),
					INDEX(gremium_id, id)
				) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));

	require SGISBASE.'/lib/inc.db.rolle.php';
} else {
	$r->fetchAll();
}
$r = $pdo->query("SELECT numPlatz FROM {$DB_PREFIX}rolle LIMIT 1");
if ($r === false) {
	$pdo->query("ALTER TABLE {$DB_PREFIX}rolle ADD COLUMN numPlatz INT NOT NULL DEFAULT 0;") or httperror(print_r($pdo->errorInfo(),true));
} else {
	$r->fetchAll();
}
$r = $pdo->query("SELECT wahlDurchWikiSuffix FROM {$DB_PREFIX}rolle LIMIT 1");
if ($r === false) {
	$pdo->query("ALTER TABLE {$DB_PREFIX}rolle ADD COLUMN wahlDurchWikiSuffix VARCHAR(128) NULL;") or httperror(print_r($pdo->errorInfo(),true));
} else {
	$r->fetchAll();
}
$r = $pdo->query("SELECT wahlPeriodeDays FROM {$DB_PREFIX}rolle LIMIT 1");
if ($r === false) {
	$pdo->query("ALTER TABLE {$DB_PREFIX}rolle ADD COLUMN wahlPeriodeDays INT NOT NULL DEFAULT 365;") or httperror(print_r($pdo->errorInfo(),true));
} else {
	$r->fetchAll();
}
$r = $pdo->query("SELECT wiki_members_roleAsColumnTable FROM {$DB_PREFIX}rolle");
if ($r === false) {
	$pdo->query("ALTER TABLE {$DB_PREFIX}rolle ADD COLUMN wiki_members_roleAsColumnTable VARCHAR(128) NULL;") or httperror(print_r($pdo->errorInfo(),true));
} else {
	$r->fetchAll();
}
$r = $pdo->query("SELECT wiki_members_roleAsColumnTableExtended FROM {$DB_PREFIX}rolle");
if ($r === false) {
	$pdo->query("ALTER TABLE {$DB_PREFIX}rolle ADD COLUMN wiki_members_roleAsColumnTableExtended VARCHAR(128) NULL;") or httperror(print_r($pdo->errorInfo(),true));
} else {
	$r->fetchAll();
}
$r = $pdo->query("SELECT wiki_members_roleAsMasterTable FROM {$DB_PREFIX}rolle");
if ($r === false) {
	$pdo->query("ALTER TABLE {$DB_PREFIX}rolle ADD COLUMN wiki_members_roleAsMasterTable VARCHAR(128) NULL;") or httperror(print_r($pdo->errorInfo(),true));
} else {
	$r->fetchAll();
}
$r = $pdo->query("SELECT wiki_members_roleAsMasterTableExtended FROM {$DB_PREFIX}rolle");
if ($r === false) {
	$pdo->query("ALTER TABLE {$DB_PREFIX}rolle ADD COLUMN wiki_members_roleAsMasterTableExtended VARCHAR(128) NULL;") or httperror(print_r($pdo->errorInfo(),true));
} else {
        $r->fetchAll();
}
$r = $pdo->query("SELECT wiki_members FROM {$DB_PREFIX}rolle");
if ($r === false) {
	$pdo->query("ALTER TABLE {$DB_PREFIX}rolle ADD COLUMN wiki_members VARCHAR(128) NULL;") or httperror(print_r($pdo->errorInfo(),true));
} else {
	$r->fetchAll();
}
#$r = $pdo->query("UPDATE {$DB_PREFIX}rolle SET wiki_members = ':sgis:mitglieder:gewaehltenkonvent#2 Beratende Mitglieder'");

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
} else {
	$r->fetchAll();
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
} else {
	$r->fetchAll();
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
} else {
	$r->fetchAll();
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}mailingliste_mailman");
if ($r === false) {
	$pdo->query("CREATE TABLE {$DB_PREFIX}mailingliste_mailman (
					id INT NOT NULL AUTO_INCREMENT,
					mailingliste_id INT NULL,
					url VARCHAR(128) NOT NULL,
					field VARCHAR(128) NOT NULL,
					priority INT NOT NULL DEFAULT 100,
					mode VARCHAR(128) NOT NULL DEFAULT 'set',
					value TEXT NOT NULL,
					PRIMARY KEY(id),
					FOREIGN KEY (mailingliste_id) REFERENCES {$DB_PREFIX}mailingliste(id) ON DELETE CASCADE,
					UNIQUE (mailingliste_id, url, field, priority) ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));
	require SGISBASE.'/lib/inc.db.mailingliste-mailman.php';
} else {
	$r->fetchAll();
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
} else {
        $r->fetchAll();
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
} else {
	$r->fetchAll();
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
} else {
	$r->fetchAll();
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
} else {
	$r->fetchAll();
}

$r = $pdo->query("SELECT lastCheck FROM {$DB_PREFIX}rel_mitgliedschaft");
if ($r === false) {
	$pdo->query("ALTER TABLE {$DB_PREFIX}rel_mitgliedschaft ADD COLUMN lastCheck DATE NULL;") or httperror(print_r($pdo->errorInfo(),true));
} else {
	$r->fetchAll();
}

# dataTables view
$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}person_is_active");
if ($r === false) {
	$pdo->query("CREATE VIEW {$DB_PREFIX}person_is_active AS
		SELECT DISTINCT rm.person_id as person_id
		FROM {$DB_PREFIX}rel_mitgliedschaft rm
		WHERE (rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE);")
	or httperror(print_r($pdo->errorInfo(),true));
} else {
        $r->fetchAll();
}

#$pdo->query("DROP VIEW {$DB_PREFIX}person_has_unimail");
$r = $pdo->query("SELECT * FROM {$DB_PREFIX}person_has_unimail");
if ($r === false) {
	$pdo->query("CREATE VIEW {$DB_PREFIX}person_has_unimail AS
		SELECT DISTINCT pe.person_id as person_id
		FROM {$DB_PREFIX}person_email pe
		WHERE email LIKE '%@tu-ilmenau.de';")
	or httperror(print_r($pdo->errorInfo(),true));
} else {
	$r->fetchAll();
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
} else {
	$r->fetchAll();
}

#$r = $pdo->query("DROP VIEW {$DB_PREFIX}person_current");
$r = $pdo->query("SELECT wikiPage FROM {$DB_PREFIX}person_current LIMIT 1");
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

        $pdo->query("DROP TABLE {$DB_PREFIX}person_current_mat"); # ignore error if person_current_mat does not exist
} else {
	$r->fetchAll();
}

#$pdo->query("DROP TABLE {$DB_PREFIX}person_current_mat"); # ignore error if person_current_mat does not exist

$r = $pdo->query("SELECT * FROM {$DB_PREFIX}person_current_mat LIMIT 1");
if ($r === false) {
        $pdo->query("CREATE TABLE {$DB_PREFIX}person_current_mat
	SELECT * FROM {$DB_PREFIX}person_current")
	or httperror(print_r($pdo->errorInfo(),true));
        $pdo->query("ALTER TABLE {$DB_PREFIX}person_current_mat ADD CONSTRAINT PRIMARY KEY (id)") or httperror(print_r($pdo->errorInfo(),true));
        $pdo->query("ALTER TABLE {$DB_PREFIX}person_current_mat ADD INDEX (active)") or httperror(print_r($pdo->errorInfo(),true));
        $pdo->query("ALTER TABLE {$DB_PREFIX}person_current_mat ADD INDEX (canLoginCurrent)") or httperror(print_r($pdo->errorInfo(),true));
} else {
	$r->fetchAll();
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}gremium_has_members");
if ($r === false) {
	$pdo->query("CREATE VIEW {$DB_PREFIX}gremium_has_members AS
	SELECT DISTINCT rm.gremium_id FROM {$DB_PREFIX}rel_mitgliedschaft rm WHERE (von IS NULL OR von <= CURRENT_DATE) AND (bis IS NULL OR bis >= CURRENT_DATE)
	")
	or httperror(print_r($pdo->errorInfo(),true));
} else {
	$r->fetchAll();
}
$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}gremium_has_members_in_inactive_roles");
if ($r === false) {
	$pdo->query("CREATE VIEW {$DB_PREFIX}gremium_has_members_in_inactive_roles AS
	SELECT DISTINCT rm.gremium_id FROM {$DB_PREFIX}rel_mitgliedschaft rm INNER JOIN {$DB_PREFIX}rolle r ON r.id = rm.rolle_id WHERE (NOT r.active) AND (von IS NULL OR von <= CURRENT_DATE) AND (bis IS NULL OR bis >= CURRENT_DATE)
	")
	or httperror(print_r($pdo->errorInfo(),true));
} else {
	$r->fetchAll();
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
} else {
	$r->fetchAll();
}

$r = $pdo->query("SELECT rolle_wiki_members FROM {$DB_PREFIX}rolle_searchable");
if ($r === false) {
	$pdo->query("CREATE OR REPLACE VIEW {$DB_PREFIX}rolle_searchable AS
		SELECT TRIM(CONCAT_WS(' ',r.name,g.name,g.fakultaet,g.studiengang,g.studiengangabschluss)) as fullname,
			r.id as rolle_id, r.name as rolle_name, r.active as rolle_active, r.spiGroupId as rolle_spiGroupId, r.numPlatz as rolle_numPlatz, r.wahlDurchWikiSuffix as rolle_wahlDurchWikiSuffix, r.wahlPeriodeDays as rolle_wahlPeriodeDays, r.wiki_members_roleAsColumnTable as rolle_wiki_members_roleAsColumnTable, r.wiki_members_roleAsColumnTableExtended as rolle_wiki_members_roleAsColumnTableExtended, r.wiki_members_roleAsMasterTable as rolle_wiki_members_roleAsMasterTable, r.wiki_members_roleAsMasterTableExtended as rolle_wiki_members_roleAsMasterTableExtended, r.wiki_members as rolle_wiki_members,
			g.id as gremium_id, g.name as gremium_name, g.fakultaet as gremium_fakultaet, g.studiengang as gremium_studiengang, g.studiengangabschluss as gremium_studiengangabschluss, g.wiki_members as wiki_members, g.wiki_members_table as wiki_members_table, g.wiki_members_fulltable as wiki_members_fulltable, g.wiki_members_fulltable2 as wiki_members_fulltable2, g.active as gremium_active,
			r.id as id, (r.active AND g.active) as active
		FROM {$DB_PREFIX}gremium g
			INNER JOIN {$DB_PREFIX}rolle r ON r.gremium_id = g.id
	")
	or httperror(print_r($pdo->errorInfo(),true));
} else {
	$r->fetchAll();
}

$r = $pdo->query("SELECT rolle_wiki_members FROM {$DB_PREFIX}rolle_searchable_mailingliste");
if ($r === false) {
	$pdo->query("CREATE OR REPLACE VIEW {$DB_PREFIX}rolle_searchable_mailingliste AS
		SELECT TRIM(CONCAT_WS(' ',r.name,g.name,g.fakultaet,g.studiengang,g.studiengangabschluss)) as fullname,
			r.id as rolle_id, r.name as rolle_name, r.active as rolle_active, r.spiGroupId as rolle_spiGroupId, r.numPlatz as rolle_numPlatz, r.wahlDurchWikiSuffix as rolle_wahlDurchWikiSuffix, r.wahlPeriodeDays as rolle_wahlPeriodeDays, r.wiki_members_roleAsColumnTable as rolle_wiki_members_roleAsColumnTable, r.wiki_members_roleAsColumnTableExtended as rolle_wiki_members_roleAsColumnTableExtended, r.wiki_members_roleAsMasterTable as rolle_wiki_members_roleAsMasterTable, r.wiki_members_roleAsMasterTableExtended as rolle_wiki_members_roleAsMasterTableExtended, r.wiki_members as rolle_wiki_members,
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
} else {
	$r->fetchAll();
}

# Version des DB Schemas
$r = $pdo->exec("DELETE FROM {$DB_PREFIX}version");
if ($r === false) {
	$pdo->query("CREATE TABLE {$DB_PREFIX}version (
					id INT NOT NULL,
					PRIMARY KEY (id)
				) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));
}
$pdo->exec("INSERT INTO {$DB_PREFIX}version (id) VALUES (".$DB_VERSION.")")
	or httperror(print_r($pdo->errorInfo(),true));

# vim: set expandtab tabstop=8 shiftwidth=8 :

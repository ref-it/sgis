<?php
global $pdo;
global $DB_DSN, $DB_USERNAME, $DB_PASSWORD, $DB_PREFIX;

try {
        $pdo = new PDO($DB_DSN, $DB_USERNAME, $DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8, lc_time_names = 'de_DE';"));
} catch (Exception $e) {
        die("Datenbankverbindung fehlgeschlagen. Server überlastet? Ursache: ".$e->getMessage());
}

$pdo->exec("SET NAMES utf8;");

$pdo->exec("SET lc_time_names = 'de_DE';");

if ((defined('DB_INSTALL') && DB_INSTALL) || isset($_REQUEST["__DB_INSTALL"])) {
        require dirname(__FILE__)."/inc.db.schema.php";
}

require dirname(__FILE__)."/inc.db.api.php";

#header("X-Trace-".basename(__FILE__)."-".__LINE__.": ".round((microtime(true) - $time_start)*1000,2)."ms");

# vim: set expandtab tabstop=8 shiftwidth=8 :

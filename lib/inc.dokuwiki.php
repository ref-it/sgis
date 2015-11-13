<?php

/**
 * Remove unwanted chars from ID
 *
 * Cleans a given ID to only use allowed characters. Accented characters are
 * converted to unaccented ones
 *
 */
function cleanID($raw_id){
    $id = trim((string)$raw_id);
    $id = strtolower($id);

    $id = strtr($id,';/','::');

    //clean up
    $id = preg_replace('#:+#',':',$id);
    $id = trim($id,':._-');
    $id = preg_replace('#:[:\._\-]+#',':',$id);
    $id = preg_replace('#[:\._\-]+:#',':',$id);

    return($id);
}


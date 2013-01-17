<?php

global $ADMINGROUP;

require_once "../lib/inc.all.php";
requireGroup($ADMINGROUP);

$file = tempnam(SGISBASE.'/tmp', 'export-ods');

$objWriter = new OpenDocument_Spreadsheet_Writer($file);
$objWriter->startDoc();
$objWriter->startSheet('Mitgliedschaften');

$data = getAllMitgliedschaft();
$keys = array_keys($data[0]);
foreach ($keys as $i => $key) {
  $objWriter->addCell($i, $key, 'string');
}
$objWriter->saveRow();
foreach ($data as $row) {
  foreach ($keys as $i => $key) {
    $value = $row[$key];
    $type = "string";
    $objWriter->addCell($i++, $value, $type);
  }
  $objWriter->saveRow();
}
$objWriter->endSheet();
$objWriter->endDoc();
$objWriter->saveOds();

header("Content-Type: application/vnd.oasis.opendocument.spreadsheet");
header("Content-Disposition: attachment; filename=\"sgis.ods\"");
readfile($file);


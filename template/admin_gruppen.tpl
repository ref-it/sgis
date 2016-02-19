<?php

$metadata = [
#  "id" => "ID",
  "name" => "Name",
  "beschreibung" => "Beschreibung",
 ];

?>

<?php
 $obj = "gruppe";
 $obj_editable = true;
 $obj_smallpageinate = false;
 $obj_selectable = false;
 require dirname(__FILE__)."/admin_table.tpl";

// vim: set filetype=php:

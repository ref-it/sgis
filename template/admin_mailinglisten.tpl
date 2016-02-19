<?php

$metadata = [
#  "id" => "ID",
  "address" => "Adresse",
  "url" => "URL",
 ];

?>

<?php
 $obj = "mailingliste";
 $obj_editable = true;
 $obj_smallpageinate = false;
 $obj_selectable = false;
 require dirname(__FILE__)."/admin_table.tpl";

// vim: set filetype=php:

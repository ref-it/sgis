<?php

$metadata = [
#  "id" => "ID",
  "name" => "Name",
  "email" => "eMail",
  "unirzlogin" => "Uni",
  "username" => "sGIS",
  "lastLogin" => "Login",
  "canLogin" => "<small>Sperre</small>",
  "active" => "<small>aktiv</small>",
 ];

?>

<div class="panel panel-default">
 <div class="panel-heading">Filter <span class="visible-xs-inline">: Personen anzeigen</span></div>
 <div class="panel-body">
  <div class="hidden-xs col-sm-4">
    Personen anzeigen:
  </div>
  <div class="col-xs-12 col-sm-4">
   <select class="selectpicker tablefilter" data-column="<?php echo 1 + array_search("canLogin", array_keys($metadata)); ?>">
    <option value="0">Nur gesperrte</option>
    <option value="1" selected>Nur ungesperrte</option>
    <option value="">Alle (Sperre)</option>
   </select>
  </div>
  <div class="col-xs-12 col-sm-4">
   <select class="selectpicker tablefilter" data-column="<?php echo 1 + array_search("active", array_keys($metadata)); ?>">
    <option value="1">Nur aktive</option>
    <option value="0">Nur inaktive</option>
    <option value="" selected>Alle (aktiv)</option>
   </select>
  </div>
 </div> <!-- panel-body -->
</div> <!-- panel -->

<?php
 $obj = "person";
 require dirname(__FILE__)."/admin_table.tpl";

// vim: set filetype=php:

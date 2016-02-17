<?php

$metadata = [
#  "id" => "ID",
  "name" => "Gremium",
  "fakultaet" => "Fak.",
  "studiengang" => "Fach",
  "studiengangabschluss" => "Abschluss",
#  "wiki_members" => "Wiki-Prefix",
  "active" => "Aktiv",
  "has_members" => "<small>Mitglieder</small>",
  "has_members_in_inactive_roles" => "<small>Mitglieder in inaktiven Rollen</small>",
 ];

?>

<div class="panel panel-default">
 <div class="panel-heading">Filter <span class="visible-xs-inline">: Gremien anzeigen</span></div>
 <div class="panel-body">
  <div class="hidden-xs col-sm-3">
    Gremien anzeigen:
  </div>
  <div class="col-xs-12 col-sm-3">
   <select class="selectpicker tablefilter" data-column="<?php echo 1 + array_search("has_members", array_keys($metadata)); ?>">
    <option value="1">Nur mit Mitgliedern</option>
    <option value="0">Nur ohne Mitglieder</option>
    <option value="" selected>Alle</option>
   </select>
  </div>
  <div class="col-xs-12 col-sm-3">
   <select class="selectpicker tablefilter" data-column="<?php echo 1 + array_search("has_members_in_inactive_roles", array_keys($metadata)); ?>">
    <option value="1">Nur mit Mitgliedern in deaktivierten Rollen</option>
    <option value="0">Nur ohne Mitglieder in deaktivierten Rollen</option>
    <option value="" selected>Alle</option>
   </select>
  </div>
  <div class="col-xs-12 col-sm-3">
   <select class="selectpicker tablefilter" data-column="<?php echo 1 + array_search("active", array_keys($metadata)); ?>">
    <option value="1" selected>Nur aktive</option>
    <option value="0">Nur inaktive</option>
    <option value="">Alle</option>
   </select>
  </div>
 </div> <!-- panel-body -->
</div> <!-- panel -->

<?php
 $obj = "gremium";
 require dirname(__FILE__)."/admin_table.tpl";

// vim: set filetype=php:

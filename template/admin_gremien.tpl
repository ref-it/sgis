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
   <select class="selectpicker tablefilter" data-column="has_members:name">
    <option value="1">Nur mit Mitgliedern</option>
    <option value="0">Nur ohne Mitglieder</option>
    <option value="" selected>Alle (Mitglieder)</option>
   </select>
  </div>
  <div class="col-xs-12 col-sm-3">
   <select class="selectpicker tablefilter" data-column="has_members_in_inactive_roles:name">
    <option value="1">Nur mit Mitgliedern in deaktivierten Rollen</option>
    <option value="0">Nur ohne Mitglieder in deaktivierten Rollen</option>
    <option value="" selected>Alle (Mitglieder in deaktivierten Rollen)</option>
   </select>
  </div>
  <div class="col-xs-12 col-sm-3">
   <select class="selectpicker tablefilter" data-column="active:name">
    <option value="1" selected>Nur aktive</option>
    <option value="0">Nur inaktive</option>
    <option value="">Alle (aktiv)</option>
   </select>
  </div>
 </div> <!-- panel-body -->
</div> <!-- panel -->

<?php
 $obj = "gremium";
 $obj_editable = true;
 $obj_smallpageinate = false;
 $obj_selectable = false;
 require dirname(__FILE__)."/admin_table.tpl";

// vim: set filetype=php:

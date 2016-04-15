<?php

$gremium = getGremiumById($_REQUEST["gremium_id"]);
if ($gremium === false) die("Invalid Id");
$rollen = getRolleByGremiumId($gremium["id"]);

?>

<form method="POST" action="<?php echo $_SERVER["PHP_SELF"];?>" enctype="multipart/form-data" class="ajax">
<input type="hidden" name="id" value="<?php echo $gremium["id"];?>"/>
<input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>

<div class="panel panel-default">
 <div class="panel-heading">
  Gremium <?php echo htmlspecialchars($gremium["name"]); ?>
 </div>
 <div class="panel-body">

<div class="form-horizontal" role="form">

<?php

foreach ([
  "id" => "ID",
  "name" => "Name",
  "fakultaet" => "Fakultät",
  "studiengang" => "Studiengang",
  "studiengangabschluss" => "Stg.-Abschluss",
  "wiki_members" => "Wiki-Seite für Mitglieder",
  "wiki_members_table" => "Wiki-Seite für Mitglieder (Tabellenform)",
  "wiki_members_fulltable" => "Wiki-Seite für Mitglieder (Tabellenform für mehrere Gremiennamen)",
  "active" => "Gremium existent/aktiv?",
 ] as $key => $desc):

?>

  <div class="form-group">
    <label class="control-label col-sm-3"><?php echo htmlspecialchars($desc); ?></label>
    <div class="col-sm-9">
      <div class="form-control">
      <?php
        switch($key) {
          case "active":
            echo htmlspecialchars($gremium["$key"] ? "ja" : "nein");
            break;
          default:
            echo htmlspecialchars($gremium["$key"]);
            break;
        }
      ?>
      </div>
    </div>
  </div>

<?php

endforeach;

?>

</div> <!-- form -->

 </div>
 <div class="panel-footer">
<div class="form-horizontal" role="form">
  <div class="form-group">
    <label class="control-label col-sm-2" for="action">Aktion</label>
    <div class="col-sm-10">
      <select class="form-control" name="action" size="1">
        <option value="gremium.disable" selected="selected">Gremium deaktivieren</option>
        <option value="gremium.delete">Datensatz löschen</option>
      </select>
    </div>
  </div>
</div> <!-- form -->
     <input type="submit" name="submit" value="Löschen" class="btn btn-danger"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="self.close();" class="btn btn-default"/>
 </div>
</div>

</form> <!-- form -->

<?php
$gremienrollen_edit = false;
require "../template/gremienrollenliste.tpl";

// vim:set filetype=php:

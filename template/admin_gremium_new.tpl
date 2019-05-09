<form action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST" enctype="multipart/form-data" class="ajax">
<input type="hidden" name="action" value="gremium.insert"/>
<input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>

<div class="panel panel-default">
 <div class="panel-heading">
  Neues Gremium
 </div>
 <div class="panel-body">

<div class="form-horizontal" role="form">

<?php

foreach ([
  "name" => "Name",
  "fakultaet" => "Fakultät",
  "studiengang" => "Studiengang",
  "studiengang_short" => "Studiengang (kurz) (Tutor)",
  "studiengang_english" => "Studiengang (Englisch) (Tutor)",
  "matrikel" => "Matrikel (Tutor)",
  "studiengangabschluss" => "Abschluss (Bachelor, Master, ...)",
  "wiki_members" => "Wiki-Seite für alle Mitglieder",
  "wiki_members_table" => "Wiki-Seite für aktuelle Mitglieder (Tabellenform)",
  "wiki_members_fulltable" => "Wiki-Seite für aktuelle Mitglieder (Tabellenform für mehrere Gremiennamen)",
  "wiki_members_fulltable2" => "Wiki-Seite für aktuelle Mitglieder (Tabellenform für mehrere Gremiennamen)",
  "active" => "Gremium existent/aktiv?",
 ] as $key => $desc):

?>

  <div class="form-group">
    <label for="<?php echo htmlspecialchars($key); ?>" class="control-label col-sm-3"><?php echo htmlspecialchars($desc); ?></label>
    <div class="col-sm-9">

      <?php
        switch($key) {
          case "wiki_members":
          case "wiki_members_table":
          case "wiki_members_fulltable":
          case "wiki_members_fulltable2":
?>         <input class="form-control" type="text" name="<?php echo htmlspecialchars($key); ?>" value="">
           <i>(Wenn gesetzt beginnt immer mit :sgis:mitglieder:)</i>
<?php
            break;
          case"active":
?>         <select name="active" size="1" class="selectpicker" data-width="fit">
              <option value="1" selected="selected">Ja, derzeit existent</option>
              <option value="0" >Nein, derzeit nicht existent</option>
           </select><?php
            break;
          default:
?>         <input class="form-control" type="text" name="<?php echo htmlspecialchars($key); ?>" value=""><?php
        }
      ?>
    </div>
  </div>

<?php

endforeach;

?>

</div> <!-- form -->

 </div>
 <div class="panel-footer">
     <input type="submit" name="submit" value="Speichern" class="btn btn-primary"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="self.close();" class="btn btn-default"/>
 </div>
</div>

</form>

<?php

// vim:set filetype=php:

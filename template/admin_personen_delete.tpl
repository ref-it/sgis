<?php

$person = getPersonDetailsById($_REQUEST["person_id"]);
if ($person === false) die("Invalid Id");
$gremien = getPersonRolle($person["id"]);

$contactDetails = getPersonContactDetails($person["id"]);

$vals = explode(",", $person["email"]);
$otherpersons = [];
foreach ($vals as $val) {
  $r = verify_tui_mail($val);
  if ($r !== false && isset($r["mail"])) {
    foreach ($r["mail"] as $othermail) {
      $otherperson = getPersonDetailsByMail($othermail);
      if ($otherperson !== false && $person["id"] != $otherperson["id"]) {
        $otherpersons[] = $otherperson;
      }
    }
  }
}

?>

<form method="POST" action="<?php echo $_SERVER["PHP_SELF"];?>" enctype="multipart/form-data" class="ajax">
<input type="hidden" name="id" value="<?php echo $person["id"];?>"/>
<input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>

<div class="panel panel-default">
 <div class="panel-heading">
  <?php echo htmlspecialchars($person["name"]); ?>
 </div>
 <div class="panel-body">

<div class="form-horizontal" role="form">

<?php

foreach ([
  "id" => "ID",
  "name" => "Name",
  "email" => "eMail",
  "_contactDetails" => "Kontaktdaten",
  "username" => "Login-Name",
  "password" => "Login-Password",
  "unirzlogin" => "UniRZ-Login",
  "lastLogin" => "letztes Login",
  "canLogin" => "Login erlaubt?",
  "wikiPage" => "Wiki-Seite zur Person",
 ] as $key => $desc):

?>

  <div class="form-group">
    <label class="control-label col-sm-2"><?php echo htmlspecialchars($desc); ?></label>
    <div class="col-sm-10">
      <?php
        switch($key) {
          case "_contactDetails":
            if (count($contactDetails) > 0) {
?>
      <div class="row">
        <div class="col-sm-2"><b><center>Typ</center></b></div>
        <div class="col-sm-10"><b><center>Erreichbar unter</center></b></div>
      </div> <!-- row -->
<?php         foreach ($contactDetails as $c) { ?>
      <div class="row">
        <div class="col-sm-2">
          <div class="form-control"><?php echo htmlspecialchars(contactType2Str($c["type"])); ?></div>
        </div>
        <div class="col-sm-10">
          <div class="form-control contactDetails <?php if (!$c["active"] && $c["fromWiki"]) echo "inactive"; else echo "active"; ?>"><?php echo htmlspecialchars($c["details"]); ?></div>
        </div>
      </div> <!-- row -->
<?php         }
            } else {
?>            <i>Keine</i><?php
}
            break;
          case "password":
?>      <div class="form-control"> <?php
            echo (empty($person["$key"]) ? "nicht gesetzt" : "gesetzt");
?>      </div><?php
            break;
          case "canLogin":
?>      <div class="form-control"> <?php

            $grps = Array();
            foreach (getPersonGruppe($person["id"]) as $grp) {
              $grps[] = $grp["name"];
            }
            if ($person[$key]) {
              $canLogin = !in_array("cannotLogin", $grps);
            } else {
              $canLogin = in_array("canLogin", $grps);
            }

            if ($person[$key] && !$canLogin) {
              echo "grundsätzlich ja, aber derzeit gesperrt.";
            }
            else if (!$person[$key] && $canLogin) {
              echo "grundsätzlich nicht, aber derzeit erlaubt.";
            }
            else {
              echo htmlspecialchars($person["$key"] ? "ja" : "nein");
            }
?>      </div><?php
            break;
          case "wikiPage":
?>      <div class="form-control"> <?php
            echo htmlspecialchars($person["$key"]);
?>           <i>(Wenn gesetzt beginnt immer mit ":person:" .)</i><?php
?>      </div><?php
            break;
          default:
?>      <div class="form-control"> <?php
            echo htmlspecialchars($person["$key"]);
?>      </div><?php
            break;
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
<div class="form-horizontal" role="form">
  <div class="form-group">
    <label class="control-label col-sm-2" for="action">Aktion</label>
    <div class="col-sm-10">
      <select class="form-control" name="action" size="1">
        <option value="person.disable" selected="selected">Person deaktivieren</option>
        <option value="person.delete">Datensatz löschen</option>
<?php

foreach ($otherpersons as $otherperson):

?>
        <option value="person.merge.<?php echo $otherperson["id"]; ?>">Rollen + eMail nach Person <?php echo htmlspecialchars($otherperson["name"]); ?> ( # <?php echo $otherperson["id"]; ?> ) verschieben + Datensatz löschen</option>
<?php

endforeach;

?>

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

$gremienmitgliedschaften_edit = false;
$gremienmitgliedschaften_link = true;
$gremienmitgliedschaften_allByDefault = false;
$gremienmitgliedschaften_comment = true;
require "../template/gremienmitgliedschaften.tpl";

// vim:set filetype=php:

<?php

$person = getPersonDetailsById($_REQUEST["person_id"]);
if ($person === false) die("invalid id");

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

<form action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST" enctype="multipart/form-data" class="ajax">
<input type="hidden" name="id" value="<?php echo $person["id"];?>"/>
<input type="hidden" name="action" value="person.update"/>
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
    <label for="<?php echo htmlspecialchars($key); ?>" class="control-label col-sm-2"><?php echo htmlspecialchars($desc); ?></label>
    <div class="col-sm-10">

      <?php
        switch($key) {
          case "_contactDetails":
?>
      <div class="row">
        <div class="col-sm-2"><b><center>Typ</center></b></div>
        <div class="col-sm-8"><b><center>Erreichbar unter</center></b></div>
        <div class="col-sm-2">
          <b><center>
            Status
            <i class="fa fa-external-link-square" title="Bei Übernahme aus Wiki"></i>
          </center></b>
        </div>
      </div> <!-- row -->
<?php       foreach ($contactDetails as $c) { ?>
      <div class="row">
        <input type="hidden" name="_contactDetails_id[]" value="<?php echo htmlspecialchars($c["id"]); ?>">
        <div class="col-sm-2">
          <div class="form-control"><?php echo htmlspecialchars(contactType2Str($c["type"])); ?></div>
          <input type="hidden" name="_contactDetails_type[]" value="<?php echo htmlspecialchars($c["type"]); ?>">
        </div>
        <div class="col-sm-8">
          <input type="hidden" name="_contactDetails_fromWiki[]" value="<?php echo htmlspecialchars($c["fromWiki"]); ?>">
<?php     if ($c["fromWiki"]) { /* nicht editierbar */ ?>
            <div class="form-control contactDetails <?php if (!$c["active"]) echo "inactive"; else echo "active"; ?>"><?php echo htmlspecialchars($c["details"]); ?></div>
            <input type="hidden" name="_contactDetails_details[]" value="<?php echo htmlspecialchars($c["details"]); ?>">
<?php     } else { /* editierbar */ ?>
            <input class="form-control" id="_contactDetails_details<?php echo htmlspecialchars($c["id"]);?>" type="text" name="_contactDetails_details[]" value="<?php echo htmlspecialchars($c["details"]); ?>" placeholder="Telefonnummer o.ä.">
<?php     } ?>
        </div>
        <div class="col-sm-2"><?php
              if ($c["fromWiki"]) { /* kann nur deaktiviert werden */
?>
              <select class="form-control" size="1" name="_contactDetails_active[]" data-width="fit">
                  <option value="1" <?php if ($c["active"]) { echo " selected=\"selected\" "; } ?> >gültig</option>
                  <option value="0" <?php if (!$c["active"]) { echo " selected=\"selected\" "; } ?> >ungültig</option>
                </select>
<?php         } else { /* kann nur gelöscht werden, indem emtpy(details) */
?>              <input type="hidden" name="_contactDetails_active[]" value="1">
                <a href="#" title="Zum Löschen Textfeld leeren" class="btn btn-default" onClick="return clearValue('_contactDetails_details<?php echo htmlspecialchars($c["id"]);?>');"><i class="fa fa-fw fa-trash"></i></a>
 <?php
              } ?>
        </div>
      </div> <!-- row -->
<?php       } ?>
      <!-- NEUE ZEILE -->
      <div class="row new-row-replicate">
        <input type="hidden" name="_contactDetails_id[]" value="-1">
        <div class="col-sm-2">
          <!-- SELECT TYPE / NEW TYPE -->
          <select class="selectpicker" data-width="fit" name="_contactDetails_type[]">
<?php      foreach ($contactTypes as $ctid => $ctstr) { ?>
             <option value="<?php echo htmlspecialchars($ctid);?>"><?php echo htmlspecialchars($ctstr); ?></option>
<?php      } ?>
          </select>
        </div>
        <div class="col-sm-8"><input class="form-control" type="text" name="_contactDetails_details[]" value="" placeholder="Telefonnummer o.ä."></div>
        <div class="col-sm-2">
          <input type="hidden" name="_contactDetails_fromWiki[]" value="0">
          <input type="hidden" name="_contactDetails_active[]" value="1">
        </div>
      </div> <!-- row -->
<?php
            break;
          case "password":
?>          <input class="form-control" type="password" name="<?php echo htmlspecialchars($key); ?>" value=""><?php
            break;
          case"lastLogin":
?>          <div class="form-control"><?php echo htmlspecialchars($person[$key]); ?></div><?php
            break;
          case"canLogin":
?>         <select name="canlogin" size="1" class="selectpicker" data-width="fit">
              <option value="1" <?php  if ($person["canLogin"]) echo "selected=\"selected\""; ?>>erlaubt, außer während zur Gruppe cannotLogin zugehörig</option>
              <option value="0" <?php  if (!$person["canLogin"]) echo "selected=\"selected\""; ?>>nicht erlaubt, außer während zur Gruppe canLogin zugehörig</option>
           </select><?php
            break;
          case "id":
?>         <div class="form-control"><?php echo htmlspecialchars($person[$key]); ?></div><?php
            break;
          case "email":
           $vals = explode(",", $person[$key]);
           $vals[] = "";
           foreach ($vals as $val) {
?>         <input class="form-control" type="text" name="<?php echo htmlspecialchars($key); ?>[]" value="<?php echo htmlspecialchars($val); ?>"><?php
           }
            break;
          case "wikiPage":
?>         <input class="form-control" type="text" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($person[$key]); ?>" placeholder=":person:name"><?php
?>           <i>(Wenn gesetzt beginnt immer mit ":person:" .)</i><?php
            break;
          default:
?>         <input class="form-control" type="text" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($person[$key]); ?>"><?php
        }
      ?>
    </div>
  </div>

<?php

endforeach;

?>

</div> <!-- form -->

<?php

foreach ($otherpersons as $otherperson) {
  echo "Siehe auch: <a href=\"?tab=person.edit&amp;person_id=".$otherperson["id"]."\">".htmlspecialchars($otherperson["name"])."</a><br/>\n";
}

?>
 </div>
 <div class="panel-footer">
     <input type="submit" name="submit" value="Speichern" class="btn btn-primary"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="self.close();" class="btn btn-default"/>
     <a href="?tab=person.delete&amp;person_id=<?php echo $person["id"];?>" class="btn btn-default pull-right">Löschen</a>
 </div>
</div>

</form>

<?php

$gremienmitgliedschaften_edit = true;
$gremienmitgliedschaften_link = true;
$gremienmitgliedschaften_allByDefault = false;
$gremienmitgliedschaften_comment = true;
require ("../template/gremienmitgliedschaften.tpl");

// vim:set filetype=php:

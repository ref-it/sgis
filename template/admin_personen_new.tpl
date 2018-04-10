<form action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST" enctype="multipart/form-data" class="ajax">
<input type="hidden" name="action" value="person.insert"/>
<input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>

<div class="panel panel-default">
 <div class="panel-heading">
  Neue Person
 </div>
 <div class="panel-body">

<div class="form-horizontal" role="form">

<?php

foreach ([
  "email"      => "eMail",
  "name"       => "Name",
  "_contactDetails" => "Kontaktdaten",
  "username"   => "Login-Name",
  "password"   => "Login-Password",
  "unirzlogin" => "UniRZ-Login",
  "canLogin"   => "Login erlaubt?",
  "csv"        => "Datei importieren",
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
        <div class="col-sm-10"><b><center>Erreichbar unter</center></b></div>
      </div> <!-- row -->
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
        <div class="col-sm-10"><input class="form-control" type="text" name="_contactDetails_details[]" value="" placeholder="Telefonnummer o.ä."></div>
        <input type="hidden" name="_contactDetails_fromWiki[]" value="0">
        <input type="hidden" name="_contactDetails_active[]" value="1">
      </div> <!-- row -->
<?php
            break;
          case "password":
?>          <input class="form-control" type="password" name="<?php echo htmlspecialchars($key); ?>" value=""><?php
            break;
          case "canLogin":
?>         <select name="canlogin" size="1" class="selectpicker" data-width="fit">
              <option value="1" selected="selected">erlaubt, außer während zur Gruppe cannotLogin zugehörig</option>
              <option value="0">nicht erlaubt, außer während zur Gruppe canLogin zugehörig</option>
           </select><?php
            break;
          case "csv":
?>
           <input class="file" type="file" name="<?php echo htmlspecialchars($key); ?>" accept="text/comma-separated-values" class="file" data-show-preview="false">
<?php
            break;
          case "email":
?>         <input class="form-control" type="text" name="<?php echo htmlspecialchars($key); ?>[]" value="" placeholder="@tu-ilmenau.de" onChange="checkMail(this.value, this.form, '<?php echo $nonce; ?>');"><?php
            break;
          case "wikiPage":
?>         <input class="form-control" type="text" name="<?php echo htmlspecialchars($key); ?>" value="" placeholder=":person:name"><?php
            break;
          default:
?>         <input class="form-control" type="text" name="<?php echo htmlspecialchars($key); ?>" value=""><?php
        }
        switch($key) {
          case "username":
?>
           <i> (darf nicht geändert werden, nur Kleinbuchstaben, Zahlen, Bindestrich und Unterstrich erlaubt, muss mit einem Buchstaben beginnen) </i><br/>
<?php
          case "password":
?>
           <i> (optional, wird vom Nutzer beim ersten Uni-Login vom Nutzer festgelegt)</i>
<?php
            break;
          case "unirzlogin":
?>
           <i> (optional, wird ggf. automatisch ergänzt)</i>
<?php
            break;
          case "email":
?>
           <i>Die e-Mail-Adresse wird zur Identifikation des Uni-Logins benutzt. Daher bitte die @tu-ilmenau.de-Adresse nutzen!</i>
<?php
            break;
          case "canLogin":
?>
           <br/>
           <i>(nicht erlaubt für Dummy-eMail-Adressen auf Mailinglisten und sonstige Ausnahmen nutzen)</i>
<?php
            break;
          case "csv":
?>
           <i>(optional, Trennung durch Komma, Texttrenner: ", Spalten: Name, eMail, RZ-Login erste Zeile ist Kopfzeile, "Login-Erlaubt"-Wert wird aus Eingabe übernommen.)</i>
<?php
            break;
          case "wikiPage":
?>           <i>(Wenn gesetzt beginnt immer mit ":person:" .)</i><?php
            break;
          default:
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

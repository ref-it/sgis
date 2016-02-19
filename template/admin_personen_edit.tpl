<?php

$person = getPersonDetailsById($_REQUEST["person_id"]);
$gremien = getPersonRolle($person["id"]);

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
  "username" => "Login-Name",
  "password" => "Login-Password",
  "unirzlogin" => "UniRZ-Login",
  "lastLogin" => "letztes Login",
  "canLogin" => "Login erlaubt?",
 ] as $key => $desc):

?>

  <div class="form-group">
    <label class="control-label col-sm-2"><?php echo htmlspecialchars($desc); ?></label>
    <div class="col-sm-10">

      <?php
        switch($key) {
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
require ("../template/gremienmitgliedschaften.tpl");

// vim:set filetype=php:

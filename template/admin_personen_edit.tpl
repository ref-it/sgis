<?php

$person = getPersonDetailsById($_REQUEST["person_id"]);
$gremien = getPersonRolle($person["id"]);

?>
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
      <div class="form-control">
      <?php
        switch($key) {
          case "password":
            echo (empty($person["$key"]) ? "nicht gesetzt" : "gesetzt");
            break;
          case "canLogin":

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
            break;
          default:
            echo htmlspecialchars($person["$key"]);
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
 </div>
</div>

<?php

require ("../template/gremienmitgliedschaften.tpl");

// vim:set filetype=php:

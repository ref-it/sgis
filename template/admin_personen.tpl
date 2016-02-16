<?php

# WANT: http://bootsnipp.com/snippets/featured/panel-table-with-filters-per-column
# + pagination
# + fast on smartphone
# + responsive

# see https://datatables.net/examples/styling/bootstrap.html

$alle_personen = getAllePerson();

$metadata = [
  "id" => "ID",
  "name" => "Name",
  "email" => "eMail",
  "unirzlogin" => "UniRZ-Login",
  "username" => "Benutzername",
  "lastLogin" => "letztes Login",
  "canLogin" => "Login erlaubt?",
  "active" => "aktuell Gremienaktiv?",
 ];

?>
<table class="table table-striped">
 <thead>
  <tr>
<?php
foreach (array_values($metadata) as $headline):
?>
   <th><?php echo htmlentities($headline); ?></th>
<?php
endforeach;
?>
  </tr>
 </thead>
 <tbody>
<?php
foreach ($alle_personen as $person):
?>
  <tr>
<?php
foreach (array_keys($metadata) as $key):
?>
     <td>
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
          case "active":
              echo htmlspecialchars($person["$key"] ? "ja" : "nein");
            break;
          default:
            echo htmlspecialchars($person["$key"]);
            break;
         }
?></td>
<?php
endforeach; # fields
?>
   </tr>
<?php
endforeach; # personen
?>
 </tbody>
</table>

<?php

// vim: set filetype=php:

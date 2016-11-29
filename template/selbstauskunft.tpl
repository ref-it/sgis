<?php

global $attributes, $logoutUrl, $ADMINGROUP, $nonce;

require "../template/header.tpl";

?>
<nav class="navbar navbar-default" role="navigation">
  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="#">
        <span class="hidden-xs">Selbstauskunft Studentisches Gremieninformationssystem (sGIS)</span>
        <span class="visible-xs-inline">sGIS Selbstauskunft</span>
      </a>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav navbar-right">
        <li><a href="#" class="showtab" data-tab="person">Person</a></li>
        <li><a href="#" class="showtab" data-tab="gremium">Gremienmitgliedschaften</a></li>
        <li><a href="#" class="showtab" data-tab="gruppe">Gruppenrechte</a></li>
        <li><a href="#" class="showtab" data-tab="mailingliste">Mailinglisten</a></li>
        <li><a href="#" class="showtab" data-tab="pwaendern">Nutzername und Passwort ändern</a></li>
      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>

<script>
$(function() {
  $( "div.sgistab" ).hide();
  $( ".showtab" ).on('click.sgis', function() {
    $( "div.sgistab" ).hide();
    $tabname = $(this).data('tab');
    $( "#" + $tabname).show();
  });
  $( "#person").show();
});
</script>

<?php
if ($somebodyElse):
?>
 <div class="panel panel-default">
  <div class="panel-heading"><?php echo htmlspecialchars($person["name"]); ?></div>
 </div>
<?php
endif;
?>

<?php
if (isset($_REQUEST["src"]) && $_REQUEST["src"] == "pwchange" && ((int) $_REQUEST["success"])):
?>
<div class="alert alert-success fade in">
  <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
  <b>Das Passwort und/oder Nutzername wurde(n) erfolgreich geändert.
</div>
<?php
endif;

if (isset($_REQUEST["src"]) && $_REQUEST["src"] == "pwchange" && (!(int) $_REQUEST["success"])):
?>
<div class="alert alert-danger fade in">
  <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
  <b>Das Passwort und/oder Nutzername konnte(n) nicht geändert werden.
</div>

<?php
endif;
?>

<div id="person" class="sgistab">
<div class="panel panel-default">
<div class="panel-heading">Person</div>
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

 if ($key == "email") {
   $vals = explode(",", $person[$key]);
 } else {
   $vals = [$person[$key]];
 }

 foreach ($vals as $val) {

?>

  <div class="form-group">
    <label class="control-label col-sm-2"><?php echo htmlspecialchars($desc); ?></label>
    <div class="col-sm-10">
      <div class="form-control">
      <?php
        switch($key) {
          case "password":
            echo (empty($val) ? "nicht gesetzt" : "gesetzt");
            break;
          case "canLogin":

            $grps = Array();
            foreach ($gruppen as $grp) {
              $grps[] = $grp["name"];
            }
            if ($val) {
              $canLogin = !in_array("cannotLogin", $grps);
            } else {
              $canLogin = in_array("canLogin", $grps);
            }

            if ($val && !$canLogin) {
              echo "grundsätzlich ja, aber derzeit gesperrt.";
            }
            else if (!$val && $canLogin) {
              echo "grundsätzlich nicht, aber derzeit erlaubt.";
            }
            else {
              echo htmlspecialchars($val ? "ja" : "nein");
            }
            break;
          default:
            echo htmlspecialchars($val);
            break;
        }
      ?>
      </div>
    </div>
  </div>

<?php

 }

endforeach;

?>

</div> <!-- form -->
Angehörige der TU Ilmenau können E-Mail-Weiterleitungen auf <a href="https://webmail.tu-ilmenau.de/smartsieve/">Webmail der TU Ilmenau</a> konfigurieren.

</div></div> <!-- panel -->
</div>

<div id="gremium" class="sgistab">
 <h3 class="printonly">
  Auskunft über <?php echo htmlspecialchars($person["name"]); ?><br/>
 </h3>
 <h4 class="printonly">
  <?php echo explode(",",htmlspecialchars($person["email"]))[0]; ?>
 </h4>
<?php
 $gremienmitgliedschaften_edit = false;
 $gremienmitgliedschaften_link = false;
 $gremienmitgliedschaften_allByDefault = true;
$gremienmitgliedschaften_comment = false;
 require "../template/gremienmitgliedschaften.tpl";
?>
</div>

<div id="gruppe" class="sgistab">

<div class="panel panel-default">
<div class="panel-heading">Gruppenrechte</div>
<div class="panel-body">

<?php
if (count($gruppen) == 0):
?><i>Keine Gruppen.</i><?php
else:
?><ul class="list-group"><?php
foreach($gruppen as $gruppe):
?> <li class="list-group-item"><?php echo htmlspecialchars($gruppe["name"]); ?></li>
<?php
endforeach;
?></ul><?php
endif;
?>

</div></div> <!-- panel -->

</div>

<div id="mailingliste" class="sgistab">

<div class="panel panel-default">
<div class="panel-heading">Mailinglisten</div>
<div class="panel-body">

<?php
if (count($mailinglisten) == 0):
?><i>Keine Mailinglisten.</i><?php
else:
?><ul class="list-group"><?php
foreach($mailinglisten as $mailingliste):
?> <li class="list-group-item"> <?php
if (!empty($mailingliste["url"])) echo "<a href=\"".htmlspecialchars($mailingliste["url"])."\">";
echo htmlspecialchars($mailingliste["address"]); 
if (!empty($mailingliste["url"])) echo "</a>";
?></li>
<?php
endforeach;
?></ul><?php
endif;
?>

  </div></div> <!-- panel -->

</div>

<div id="pwaendern" class="sgistab">

<div class="panel panel-default">
<div class="panel-heading">Nutzername und Passwort ändern</div>
<div class="panel-body">

Bitte geben deine neuen Zugangsdaten für das sGIS ein:

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="POST" class="form-horizontal" role="form">

  <div class="form-group">
    <label class="control-label col-sm-2" for="username">Nutzername:</label>
    <div class="col-sm-10">
      <input class="form-control" type="text" name="username" value="<?php echo htmlspecialchars($person["username"]);?>" <?php if (!empty($person["username"]))echo " readonly=readonly "; ?> placeholder="Nutzername festlegen"/>
      <br/><i>Der Nutzername kann nur einmalig eingestellt werden (d.h. wenn noch nicht gesetzt).</i>
    </div>
  </div>

  <div class="form-group">
    <label class="control-label col-sm-2" for="password">Passwort:</label>
    <div class="col-sm-10">
      <input class="form-control" type="password" name="password" value="" placeholder="Passwort eingeben" required/>
    </div>
  </div>

  <div class="form-group">
    <label class="control-label col-sm-2" for="password2"><nobr>Passwort (Wiederholung):</nobr></label>
    <div class="col-sm-10">
      <input class="form-control" type="password" name="password2" value="" placeholder="Passwortwiederholung eingeben" required/>
    </div>
  </div>

<input type="hidden" name="action" value="pwchange"/>
<input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>

<div class="pull-right">
<input type="submit" name="submit" value="Speichern" class="btn btn-primary"/>
<input type="reset" name="reset" value="Abbruch" class="btn btn-danger"/>
</div>

</form>

</div></div> <!-- panel -->

</div>

<?php

require "../template/footer.tpl";

// vim:set filetype=php:

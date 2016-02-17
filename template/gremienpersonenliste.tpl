<div class="panel panel-default">
<div class="panel-heading">Personen</div>
<div class="panel-body">

<table class="table table-striped">
<tr><th>Name</th><th>eMail</th><th class="hidden-xs">Zeitraum</th><th class="hidden-xs">Beschluss</th><th class="hidden-xs">Kommentar</th></tr>
<?php
$hasInactive = false;
if (count($personen) == 0):
?>
<tr><td colspan="5"><i>Keine Personen.</td></tr>
<?php
else:
foreach($personen as $person):
?>
<tr
<?php
if (!$person["active"]):
  $hasInactive = true;
?>
  class="rollepersoninactive"
<?php
endif;
?>
>
 <td>
  <a target="_blank" href="?tab=person.edit&amp;person_id=<?php echo $person["id"]; ?>">
  <?php echo htmlspecialchars($person["name"]);?>
 </a>
</td>
 <td>
  <a href="mailto:<?php echo htmlspecialchars($person["email"]); ?>">
  <?php echo htmlspecialchars($person["email"]);?>
 </a>
</td>
 <td class="hidden-xs"><?php
            if (empty($person["von"]) && empty($person["bis"])) {
             echo "keine Angabe";
            } elseif (empty($person["von"])) {
             echo "bis ".$person["bis"];
            } elseif (empty($person["bis"])) {
             echo "seit ".$person["von"];
            } else {
             echo htmlspecialchars($person["von"])." - ".$person["bis"];
            }
            ?></td>
 <td class="hidden-xs"><?php echo htmlspecialchars($person["beschlussAm"])." ".htmlspecialchars($person["beschlussDurch"]); ?></td>
 <td class="hidden-xs"><div class="kommentar"><?php echo str_replace("\n","<br/>",htmlspecialchars($person["kommentar"]));?></div></td>
</tr>
<?php
endforeach;
endif;
?>
</table>

<?php if ($hasInactive): ?>
<label class="checkbox">
  <input data-toggle="toggle" type="checkbox" id="rollepersontoggle"> Inaktive Einträge anzeigen
</label>
<?php endif; ?>

  </div> </div> <!--panel -->


<script>
$("#rollepersontoggle").on("change.rolleperson", function() {
  if ($(this).is(":checked")) {
    $("tr.rollepersoninactive").show();
  } else {
    $("tr.rollepersoninactive").hide();
  }
});
$("#rollepersontoggle").trigger("change");
</script>

<?php

// vim:set filetype=php:


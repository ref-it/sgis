<div class="panel panel-default">
<div class="panel-heading">Gremienmitgliedschaften</div>
<div class="panel-body">

<table class="table table-striped">
<tr><th>Tätigkeit</th><th>Zeitraum</th><th class="hidden-xs">Beschluss</th></tr>
<?php
$hasInactiveAssignments = false;
if (count($gremien) == 0):
?>
<tr><td colspan="3"><i>Keine Gremienmitgliedschaften.</td></tr>
<?php
else:
foreach($gremien as $gremium):
?>
<tr
<?php
if (!$gremium["active"]):
  $hasInactiveAssignments = true;
?>
  class="gremiumrolleinactive"
<?php
endif;
?>
>
 <td><?php echo htmlspecialchars($gremium["rolle_name"]);?> in 
 <nobr><?php

   echo htmlspecialchars($gremium["gremium_name"])." ";

  if (!empty($gremium["gremium_studiengang"])) {
   echo htmlspecialchars($gremium["gremium_studiengang"])." ";
  }

  if (!empty($gremium["gremium_studiengangabschluss"])) {
    echo " (".htmlspecialchars($gremium["gremium_studiengangabschluss"]).") ";
  }

  if (!empty($gremium["gremium_fakultaet"])) {
   echo " Fak. ".htmlspecialchars($gremium["gremium_fakultaet"])." ";
  }

?></nobr></td>
 <td>
<?php
  if (empty($gremium["von"]) && empty($gremium["bis"])) {
    echo "keine Angabe";
  } elseif (empty($gremium["von"])) {
    echo "bis ".$gremium["bis"];
  } elseif (empty($gremium["bis"])) {
    echo "seit ".$gremium["von"];
  } else {
    echo htmlspecialchars($gremium["von"])." - ".$gremium["bis"];
  }
?>
 </td>
 <td class="hidden-xs">
<?php
   echo htmlspecialchars($gremium["beschlussAm"])." ".htmlspecialchars($gremium["beschlussDurch"]);
?>
 </td>
</tr>
<?php
endforeach;
endif;
?>
</table>

<?php if ($hasInactiveAssignments): ?>
<label class="checkbox">
  <input checked data-toggle="toggle" type="checkbox" id="gremiumrolletoggle"> Inaktive Zuordnungen anzeigen
</label>
<?php endif; ?>

  </div> </div> <!--panel -->


<script>
$("#gremiumrolletoggle").on("change.gremiumrolle", function() {
  if ($(this).is(":checked")) {
    $("tr.gremiumrolleinactive").show();
  } else {
    $("tr.gremiumrolleinactive").hide();
  }
});
$("#gremiumrolletoggle").trigger("change");
</script>

<?php

// vim:set filetype=php:


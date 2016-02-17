<div class="panel panel-default">
<div class="panel-heading">Gruppen</div>
<div class="panel-body">

<table class="table table-striped">
<tr><th>Name</th><th>Beschreibung</th></tr>
<?php
if (count($gruppen) == 0):
?>
<tr><td colspan="2"><i>Keine Gruppen.</td></tr>
<?php
else:
foreach($gruppen as $gruppe):
?>
<tr>
 <td>
  <a target="_blank" href="?tab=gruppe.edit&amp;gruppe_id=<?php echo $gruppe["id"]; ?>">
  <?php echo htmlspecialchars($gruppe["name"]);?>
 </a>
</td>
 <td><?php echo htmlspecialchars($gruppe["beschreibung"]);?></td>
</tr>
<?php
endforeach;
endif;
?>
</table>

  </div> </div> <!--panel -->


<?php

// vim:set filetype=php:


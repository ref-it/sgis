<div class="panel panel-default">
<div class="panel-heading">Mailinglisten</div>
<div class="panel-body">

<table class="table table-striped">
<tr><th></th><th>Adresse</th><th class="hidden-xs">Webseite</th></tr>
<?php
if (count($mailinglisten) == 0):
?>
<tr><td colspan="3"><i>Keine Mailinglisten.</td></tr>
<?php
else:
foreach($mailinglisten as $mailingliste):
?>
<tr>
 <td>
  <a target="_blank" href="?tab=mailingliste.edit&amp;mailingliste_id=<?php echo $mailingliste["id"]; ?>"><i class="fa fa-pencil"></i></a>
 </td>
 <td>
  <a href="mailto:<?php echo $mailingliste["address"]; ?>">
  <?php echo htmlspecialchars($mailingliste["address"]);?>
 </a>
</td>
 <td class="hidden-xs">
  <a target="_blank" href="<?php echo $mailingliste["url"]; ?>">
 <?php echo htmlspecialchars($mailingliste["url"]);?>
 </a>
 </td>
</tr>
<?php
endforeach;
endif;
?>
</table>

  </div> </div> <!--panel -->


<?php

// vim:set filetype=php:


<?php

# WANT: http://bootsnipp.com/snippets/featured/panel-table-with-filters-per-column
# + pagination
# + fast on smartphone
# + responsive

# see https://datatables.net/examples/styling/bootstrap.html

$metadata = [
  "id" => "ID",
  "name" => "Name",
  "email" => "eMail",
  "unirzlogin" => "Uni",
  "username" => "sGIS",
  "lastLogin" => "Login",
  "canLogin" => "Sperre",
  "active" => "aktiv",
 ];

?>

<div class="panel panel-default">
 <div class="panel-heading">Filter <span class="visible-xs-inline">: Personen anzeigen</span></div>
 <div class="panel-body">
  <div class="hidden-xs col-sm-4">
    Personen anzeigen:
  </div>
  <div class="col-xs-12 col-sm-4">
   <select class="selectpicker tablefilter" data-column="7">
    <option value="0">Nur gesperrte</option>
    <option value="1">Nur ungesperrte</option>
    <option value="" selected>Alle</option>
   </select>
  </div>
  <div class="col-xs-12 col-sm-4">
   <select class="selectpicker tablefilter" data-column="8">
    <option value="1">Nur aktive</option>
    <option value="0">Nur inaktive</option>
    <option value="" selected>Alle</option>
   </select>
  </div>
 </div> <!-- panel-body -->
</div> <!-- panel -->

<table id="mainpersontable" class="display" width="100%" cellspacing="0">

 <thead>
  <tr><th>Aktion</th>
<?php
foreach (array_values($metadata) as $i => $headline):
?>
   <th><?php
    if ($i >= 6) echo "<small>";
    echo htmlentities($headline);
    if ($i >= 6) echo "</small>";
?>
   </th>
<?php
endforeach;
?>
  </tr>
 </thead>
 <tbody>
 </tbody>
</table>

<script>
$(function() {
    $('#mainpersontable').DataTable( {
       "order": [[ 1, "asc" ]],
       "stateSave": true,
       "responsive": true,
       "processing": true,
       "serverSide": true,
       "deferRender": true,
       "ajax": {
            "url": <?php echo json_encode($_SERVER["PHP_SELF"]); ?>,
            "type": "POST",
            "data": function ( d ) {
                d.nonce = <?php echo json_encode($nonce); ?>;
                d.action = "person.table";
                // d.custom = $('#myInput').val();
                // etc
            },
        },
        "language": {
          "url": "js/dataTables.german.lang.json"
//        "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/German.json"
        },
        "columns": [
            { "data": "id",
              "render":  function ( data, type, full, meta ) {
                var p1 = $("<a/>").attr("target","_blank").attr("href","?tab=person.delete&person_id=" + encodeURIComponent(full.id)).text("[X]").wrap("<div>").parent().html();
                var p2 = $("<a/>").attr("target","_blank").attr("href","?tab=person.edit&person_id=" + encodeURIComponent(full.id)).text("[E]").wrap("<div>").parent().html();
                var p3 = $("<a/>").attr("target","_blank").attr("href","index.php?mail=" + encodeURIComponent(full.email)).text("[D]").wrap("<div>").parent().html();
                return p1+" "+p2+" "+p3;
              },
              "orderable": false,
              "searchable": false,
            },
<?php
foreach (array_keys($metadata) as $field):
?>
            { "data": <?php echo json_encode($field); ?> },
<?php
endforeach;
?>
        ],
    } );
    $('.tablefilter').on('change.tablefilter', function () {
      var $table = $('#mainpersontable').DataTable();
      var colIdx = $(this).data('column');
      var flt = $(this).val();
      $table
        .columns( colIdx )
        .search( flt )
        .draw();
     });
    $('.tablefilter').trigger('change');
} );
</script>

<?php

// vim: set filetype=php:

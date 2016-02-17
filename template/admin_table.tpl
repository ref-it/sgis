<table id="main<?php echo $obj; ?>table" class="display" width="100%" cellspacing="0">

 <thead>
  <tr><th>Aktion</th>
<?php
foreach (array_values($metadata) as $i => $headline):
?>
   <th><?php echo $headline; ?></th>
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
    $('#main<?php echo $obj; ?>table').DataTable( {
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
                d.action = "<?php echo $obj;?>.table";
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
                var p1 = $("<a/>").attr("target","_blank").attr("href","?tab=<?php echo $obj; ?>.delete&<?php echo $obj; ?>_id=" + encodeURIComponent(full.id)).text("[X]").wrap("<div>").parent().html();
                var p2 = $("<a/>").attr("target","_blank").attr("href","?tab=<?php echo $obj; ?>.edit&<?php echo $obj; ?>_id=" + encodeURIComponent(full.id)).text("[E]").wrap("<div>").parent().html();
<?php if ($obj == "person"): ?>
                var p3 = $("<a/>").attr("target","_blank").attr("href","index.php?mail=" + encodeURIComponent(full.email)).text("[D]").wrap("<div>").parent().html();
                return p1+" "+p2+" "+p3;
<?php else: ?>
                return p1+" "+p2;
<?php endif; ?>
              },
              "orderable": false,
              "searchable": false,
            },
<?php
foreach (array_keys($metadata) as $i => $field):
?>
            {
              "data": <?php echo json_encode($field); ?>,
<?php if($i == 0): ?>
              "render":  function ( data, type, full, meta ) {
                var p = $("<a/>").attr("target","_blank").attr("href","?tab=<?php echo $obj; ?>.edit&<?php echo $obj; ?>_id=" + encodeURIComponent(full.id)).text(data).wrap("<div>").parent().html();
                return p;
              },
<?php elseif($field == "email"): ?>
              "render":  function ( data, type, full, meta ) {
                var p = $("<a/>").attr("href","mailto:"+data).text(data).wrap("<div>").parent().html();
                return p;
              },
<?php endif; ?>
            },
<?php
endforeach;
?>
        ],
    } );
    $('.tablefilter').on('change.tablefilter', function () {
      var $table = $('#main<?php echo $obj; ?>table').DataTable();
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

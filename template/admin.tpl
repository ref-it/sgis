<nav class="navbar navbar-default">
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
        <span class="hidden-xs">Verwaltung studentisches Gremieninformationssystem (sGIS)</span>
        <span class="visible-xs-inline">sGIS Verwaltung</span>
      </a>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav navbar-right">
        <li><a href="?tab=person">Personen</a></li>
        <li><a href="?tab=gremium">Gremien und Rollen</a></li>
        <li><a href="?tab=gruppe">Gruppen</a></li>
        <li><a href="?tab=mailingliste">Mailinglisten</a></li>
        <li><a href="?tab=export">Export</a></li>
        <li><a href="?tab=help">Hilfe</a></li>
      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>


<!-- Modal -->
<div class="modal fade" id="waitDialog" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Bitte warten...</h4>
      </div>
      <div class="modal-body">
        <p>Bitte warten, die Daten werden verarbeitet. Dies kann einen Moment dauern.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>

  </div>
</div>

<script type="text/javascript">
function xpAjaxErrorHandler (jqXHR, textStatus, errorThrown) {
      $("#waitDialog").modal("hide");
      alert(textStatus + "\n" + errorThrown + "\n" + jqXHR.responseText);
};
$(function () {
  $( "form" ).submit(function (ev) {
    var action = $(this).attr("action");
    if ($(this).find("input[name=action]").length + $(this).find("select[name=action]").length == 0) { return true; }
    var close = $(this).find("input[type=reset]");
    var data = new FormData(this);
    data.append("ajax", 1);
    $("#waitDialog").modal("show");
    $.ajax({
      url: action,
      data: data,
      cache: false,
      contentType: false,
      processData: false,
      type: "POST"
    })
    .success(function (values, status, req) {
       $("#waitDialog").modal("hide");
       if (typeof(values) == "string") {
         alert(values);
         return;
       }
       var txt;
       if (values.ret) {
         txt = "Die Daten wurden erfolgreich gespeichert.";
       } else {
         txt = "Die Daten konnten nicht gespeichert werden.";
       }
       if (values.msgs && values.msgs.length > 0) {
           txt = values.msgs.join("\n")+"\n"+txt;
       }
       alert(txt);
       if (values.ret) {
        self.opener.location.reload();
        self.close();
       }
     })
    .error(xpAjaxErrorHandler);
    return false;
   });
});
</script>

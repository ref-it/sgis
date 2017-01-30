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
<div class="modal fade" id="please-wait-dlg" tabindex="-1" role="dialog" aria-labelledby="please-wait-label">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="please-wait-label">Bitte warten</h4>
      </div>
      <div class="modal-body">
        Bitte warten, die Daten werden verarbeitet. Dies kann einen Moment dauern.
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="server-message-dlg" tabindex="-1" role="dialog" aria-labelledby="server-message-label">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="server-message-label">Antwort vom Server</h4>
      </div>
      <div class="modal-body" id="server-message-content">
        Und die Lösung lautet..
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="server-question-dlg" tabindex="-1" role="dialog" aria-labelledby="server-question-label">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="server-question-label">Antwort vom Server</h4>
      </div>
      <div class="modal-body" id="server-question-content">
        Und die Lösung lautet..
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Ok</button>
        <button type="button" class="btn btn-primary" id="server-question-close-window">Fenster schließen</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="confirm-delete-dlg" tabindex="-1" role="dialog" aria-labelledby="confirm-delete-label">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="confirm-delete-label">Soll dieser Datensatz wirklich gelöscht werden?</h4>
      </div>
      <div class="modal-body" id="confirm-delete-content">
        Wollen Sie diesen Datensatz wirklich löschen?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Abbruch</button>
        <button type="button" class="btn btn-danger" id="confirm-delete-btn">Datensatz löschen</button>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
function xpAjaxErrorHandler (jqXHR, textStatus, errorThrown) {
      $("#please-wait-dlg").modal("hide");

      $("#server-message-label").text("Es ist ein Server-Fehler aufgetreten");
      var $smc = $("#server-message-content");
      $smc.empty();
      $("#server-message-content").empty();
      var $smcp = $('<pre>').appendTo( $smc ).text(textStatus + "\n" + errorThrown + "\n" + jqXHR.responseText);
      $("#server-message-dlg").modal("show");
};
$(function () {
  $( "form.ajax" ).submit(function (evt) {
    return handleSubmitForm($(this), evt, false);
  });
});
function handleSubmitForm($form, evt, isConfirmed) {
  var action = $form.attr("action");
  if ($form.find(":input[name=action]").length == 0) { return true; }
  if (!isConfirmed && $form.find(":input[name=action]").val().substr(-6) == "delete") {
    evt.preventDefault();
    $("#confirm-delete-btn").off("click");
    $("#confirm-delete-btn").on("click.dosubmit", function (evt) {
      $("#confirm-delete-dlg").modal("hide");
      handleSubmitForm($form, evt, true);
    });
    $("#confirm-delete-dlg").modal("show");
    return false;
  }
  var data = new FormData($form[0]);
  data.append("ajax", 1);
  $("#please-wait-dlg").modal("show");
  $.ajax({
    url: action,
    data: data,
    cache: false,
    contentType: false,
    processData: false,
    type: "POST"
  })
  .done(function (values, status, req) {
     $("#please-wait-dlg").modal("hide");
     if (typeof(values) == "string") {
       $("#server-message-label").text("Es ist ein Server-Fehler aufgetreten");
       var $smc = $("#server-message-content");
       $smc.empty();
       $("#server-message-content").empty();
       var $smcp = $('<pre>').appendTo( $smc ).text(values);
       $("#server-message-dlg").modal("show");
       return;
     }
     var txt;
     var txtHeadline;
     if (values.ret) {
       txt = "";
       txtHeadline = "Die Daten wurden erfolgreich gespeichert.";
     } else {
       txt = "Die Daten konnten nicht gespeichert werden.";
       txtHeadline = "Die Daten konnten nicht gespeichert werden.";
     }
     if (values.msgs && values.msgs.length > 0) {
         txt = values.msgs.join("\n")+"\n"+txt;
     }
     if (values.ret && txt != "") {
       if (self.opener) {
         self.opener.location.reload();
       }
       $("#server-question-label").text(txtHeadline);
       var $smc = $("#server-question-content");
       $smc.empty();
       $("#server-question-content").empty();
       var $smcu = $('<ul/>').appendTo( $smc );
       for (var i = 0; i < values.msgs.length; i++) {
         var msg = (values.msgs[i]);
         $('<li/>').text(msg).appendTo( $smcu );
       }
       if (values.forceClose) {
         $('#server-question-dlg').find("*[data-dismiss=\"modal\"]").hide();
       } else {
         $('#server-question-dlg').find("*[data-dismiss=\"modal\"]").show();
       }
       $("#server-question-close-window").off("click");
       $("#server-question-close-window").on("click", function(evt) {
         if (!values.target) {
           if (self.opener) {
             self.opener.focus();
           }
           self.close();
         } else {
           self.location.href = values.target;
         }
       });
       $("#server-question-dlg").off('hidden.bs.modal');
       $("#server-question-dlg").on('hidden.bs.modal', function (e) {
         if (values.forceClose) {
           $("#server-question-close-window").triggerHandler("click");
         } else {
           if (values.target) {
             window.open(values.target);
           }
         }
       });
       $("#server-question-dlg").modal("show");

     } else if (values.ret) { // txt is empty
       if (!values.target) {
         if (self.opener) {
           self.opener.focus();
         }
         self.close();
       } else { // values.target
         self.location.href = values.target;
       }
     } else { // !values.ret
      $("#server-message-label").text(txtHeadline);
      var $smc = $("#server-message-content");
      $smc.empty();
      $("#server-message-content").empty();
      var $smcu = $('<ul/>').appendTo( $smc );
      for (var i = 0; i < values.msgs.length; i++) {
          var msg = (values.msgs[i]);
          $('<li/>').text(msg).appendTo( $smcu );
      }
      $("#server-message-dlg").modal("show");
     }
   })
  .fail(xpAjaxErrorHandler);
  if (evt)
    evt.preventDefault();
  return false;
}
</script>

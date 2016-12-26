$(function() {
  $('.datepicker').datepicker({ dateFormat: 'yy-mm-dd' });
  $(".checkAll").on('click.sgis', function () {
    var cls = $(this).data('class');
    $("."+cls).prop('checked', $(this).prop('checked'));
  });
  $(".new-row-replicate *").on('focus.sgis-new-row-replicate', function () {
    var $r = $(this).parents(".new-row-replicate");
    var $sp = $r.find('.selectpicker');
    $sp.selectpicker('destroy');
    $sp.addClass("selectpicker");
    var $n = $r.clone(true);
    $r.find("*").off('focus.sgis-new-row-replicate');
    $n.insertAfter($r);
    $n.find('.selectpicker').selectpicker({});
    $r.find('.selectpicker').selectpicker({});
  });
});

function checkMail(email, form, nonce) {
  $(form.elements["email[]"]).filter(".extra-mail").remove();

  if (email == "") {
    $(form.elements["email[]"]).removeClass("danger");
    $(form.elements["email[]"]).removeClass("success");
    return;
  }

  $.ajax({
    type: "POST",
    url: "admin.php",
    data: {"action": "verify.email", "nonce": nonce, "email": email},
  }).done(function (data) {
   if (data === false) {
    $(form.elements["email[]"]).addClass("danger");
    $(form.elements["email[]"]).removeClass("success");
   } else {
     $(form.elements["email[]"]).removeClass("danger");
     $(form.elements["email[]"]).addClass("success");

     if (data.sn && data.givenName && form.elements["name"].value == "") {
       form.elements["name"].value = data.givenName + " " + data.sn;
     }

     if (data.mail.length > 1) {
       var e = $(form.elements["email[]"]);
       var e = $(form.elements["email[]"]);
       for (i=0; i < data.mail.length; i++) {
         if (data.mail[i] == email) continue;
         $("<input name=\"email[]\" readonly=\"readonly\" class=\"form-control extra-mail\">").val(data.mail[i]).insertAfter(e);
       }
     }
   }
  });
}

function clearValue(id) {
  $('#'+id).val('');
  return false;
}


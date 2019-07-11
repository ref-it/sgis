 
(function(){
	const role_min = parseInt($('.role_min').text());
	const role_max = parseInt($('.role_max').text());
	const role_old_tutor = parseInt($('.old_tutoren_id').val());
	const date_current = $('input[name="von"]').val()
	const action_url = $('input#url').val();
	
	const max_running = 5; // maximum parallel post requests
	
	let _dataset = null;
	let _dataset_extra = null;
	
	const $i_rid = $('.i_add_role_id');
	const $i_umail = $('.i_add_usermail');
	const $dlistw = $('.data_list');
	const $btn_add = $('button.btn_add_usermail');
	const $btn_submit = $('button.btn_data_submit');
	const $paste_area = $('.paste_area');
	
	$i_rid.on('keydown', function (e) {
		if (e.which === 13) {
			$i_umail.focus();
		}
	});
	
	$i_umail.on('keydown', function (e) {
		if (e.which === 13) {
			$btn_add.click();
		}
	});
	
	$btn_add.on('click', function(){
		if ($i_rid.val().trim() != '' || $i_umail.val().trim() != ''){
			let divb = document.createElement('div');
			divb.className="transmittdata form-group";
			
			let div1 = document.createElement('div');
			div1.contentEditable=true;
			div1.className="rid col-sm-2";
			div1.innerText=$i_rid.val().trim();
			divb.appendChild(div1);
			
			let div2 = document.createElement('div');
			div2.contentEditable=true;
			div2.className="umail col-sm-5";
			div2.innerText=$i_umail.val().trim();
			divb.appendChild(div2);
			
			let div3 = document.createElement('div');
			div3.className="info col-sm-4";
			div3.innerText='';
			divb.appendChild(div3);
			
			let btn1 = document.createElement('button');
			btn1.type="button";
			btn1.className="btn_remove_transmittdata btn btn-danger form-control";
			btn1.innerHTML='<i class="fa fa-fw fa-ban"></i>';
			
			let div4 = document.createElement('div');
			div4.className="btns col-sm-1";
			div4.appendChild(btn1);
			divb.appendChild(div4);

			$dlistw.append(divb);
			
			//remove event listener
			$(btn1).on('click', function(){
				$(this).closest('.transmittdata').animate({height: 0, overflow: 'hidden', opacity: 0}, 400, function(){ $(this).remove(); });
			});

			$i_rid.val('');
			$i_umail.val('');
			$btn_submit.hide();
		} else {
			//highlight
			$i_rid.effect('highlight', {}, 500);
			$i_umail.effect('highlight', {}, 500);
		}
	});
	
	$('.btn_data_empty').on('click', function(){
		$('.transmittdata').each(function(i,e){
			$(e).animate({height: 0, overflow: 'hidden', opacity: 0}, 400, function(){ $(this).remove(); });
		});
		$btn_submit.hide();
	});
	
	let user_data = null;
	
	const isInt = function(n){
		return Number(n) == n && n % 1 === 0;
	}
	const checkIsValidEmail = function (email) {
		const regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})$/;
		return regex.test(email);
	}
	const checkIsValidTUIEmail = function (email) {
		const regex = /^([a-zA-Z0-9_.+-])+\@tu\-ilmenau\.de$/;
		return regex.test(email);
	}

	const check_content = function(){
		let success = true;
		let known_mails = [];
		user_data = [];
		
		$('.transmittdata').each(function(i,e){
			let $info = $(e).find('.info');
			let $irid = $(e).find('.rid');
			let $iumail = $(e).find('.umail');
			if ($info.hasClass('bg-danger')) $info.removeClass('bg-danger');
			if ($info.hasClass('bg-warning')) $info.removeClass('bg-warning');

			// check id is integer
			if ($irid.text().trim() == '' || !isInt($irid.text().trim()) ){
				$info.addClass('bg-danger');
				$info.text('Invalid id: no integer');
				success = false;
				return;
			}
			let rid = parseInt($irid.text().trim());
			
			//set title
			if (""+rid in rolle_data){
				let t = rolle_data[''+rid]['name'];
				if (""+rolle_data[''+rid]['gid'] in gremuim_data){
					let g = gremuim_data[""+rolle_data[''+rid]['gid']];
					t = g['f'] + ' - ' + g['stg'] + ' - ' + g['type'] + ' - ' + t;
				}
				e.title = t;
			} else {
				e.title = '';
			}
			
			// check id in range
			if (rid < role_min){
				$info.addClass('bg-danger');
				$info.text('Invalid id range: too low');
				success = false;
				return;
			}
			if (rid > role_max){
				$info.addClass('bg-danger');
				$info.text('Invalid id range: too high');
				success = false;
				return;
			}
			
			// check mail is mail and not empty
			if ($iumail.text().trim() == '' || !checkIsValidEmail($iumail.text().trim()) ){
				$info.addClass('bg-danger');
				$info.text('Invalid email address');
				success = false;
				return;
			}
			let umail = $iumail.text().trim();
			if (!checkIsValidTUIEmail(umail) ){
				$info.addClass('bg-danger');
				$info.text('Invalid TUI email address');
				success = false;
				return;
			}
			
			// check duplicates
			if(known_mails.indexOf(umail) != -1)
			{  
				$info.addClass('bg-warning');
				$info.text('Duplicate tutor email address');
			}
			
			// insert into datasets
			known_mails.push(umail);
			user_data.push({
				rid: rid,
				umail: umail
			});
		});
		
		if (user_data.length == 0){
			success = false;
		}
		
		if (!success) {
			$dlistw.effect('highlight', {}, 500);
		} else {
			update_blank_dataset();
		}

		return success;
	}
	
	$('.btn_data_check').on('click', function(){
		if (check_content()) $btn_submit.show();
		else $btn_submit.hide();
	});
	
	// automatically add
	const autoAddLine = function (id, mail){
		$i_rid.val(id);
		$i_umail.val(mail);
		$btn_add.click();
	}
	
	const escapeHtml = function (text) {
		return text
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;")
			.replace(/'/g, "&#039;");
	}
	
	const handle_paste = function(e){
		// get data
		let clipboard;
        let cliptext = '';
		let rows = null;

        if (window.clipboardData && window.clipboardData.getData) {
			clipboard = window.clipboardData;
			cliptext = clipboard.getData('Text');
		} else if (e.clipboardData && e.clipboardData.getData) {
			clipboard = e.clipboardData;
			cliptext = clipboard.getData('text/plain');
		} else {
			clipboard = e.originalEvent.clipboardData;
			cliptext = clipboard.getData('text/plain');
		}
		
		rows = cliptext.split('\n');
		let error = false;
		//cols in rows
		for (i = 0; i < rows.length; i++) {
			rows[i] = rows[i].split('\t');
			if (rows[i].length != 2 && !(rows[i].length == 1 && rows[i][0] == '') ){
				error = true;
			}
		}
		
		if (error){
			
			let modal = document.createElement('div');
			modal.id = "dzFailedModal";
			modal.style.display = 'block';
			modal.className = 'modal pimage';
			modal.innerHTML = '<div class="modal-content"><div class="modal-header bg-danger"><span class="close">&times;</span><h2>Es ist ein Fehler aufgetreten</h2></div><div class="modal-body"><p></p><strong><pre>Der eingefügte text konnte nicht interpretiert werden.<div>'+escapeHtml(JSON.stringify(rows, null, 2))+'</div></pre></strong></div><div class="modal-footer bg-danger"><h3></h3></div></div>';
			document.body.appendChild(modal);
			let span = modal.querySelector('.pimage.modal .close');
			span.onclick = function() { modal.parentElement.removeChild(modal); }
			window.onclick = function(event) { if (event.target == modal) { modal.parentElement.removeChild(modal); } };
		} else {
			for (i = 0; i < rows.length; i++) {
				if (rows[i][0] != '' || rows[i][1] != ''){
					autoAddLine(rows[i][0], rows[i][1]);
				}
			}
		}
	};
	
	$paste_area.on('keypress', function(e) {
		if (e.key == 'v' && e.ctrlKey === true) {
			e.preventDefault();
			e.stopPropagation();
			handle_paste(e);
			$paste_area.val("");
		}
	});
	$paste_area.on('paste', function(e) {
		e.preventDefault();
		e.stopPropagation();
		handle_paste(e);
		$paste_area.val("");
	});
	
	//------------
	
	//onclick handler
	$i_ov_from_auto = $('.ignore_from_use_auto');
	$i_ov_from_date = $('.ignore_from_use_current_date');
	$i_year = $('.tutor_year');
	$i_semester = $('.tutor_semester');
	$i_ov_until_auto = $('.ignore_until_use_auto');
	$i_extra= $('.create_old_tutor');
	
	$i_ov_from_auto.on('change', function(){
		if (this.checked) $i_ov_from_date[0].checked = false;
	});
	
	$i_ov_from_date.on('change', function(){
		if (this.checked) $i_ov_from_auto[0].checked = false;
	});
	
	
	const update_blank_dataset = function(){
		_dataset = {};
		$('.options_data input[name], .options_data textarea[name], .options_data select[name]').each(function(i,e){
			_dataset[e.name] = $(e).val();
		});
		_dataset.ajax = 1;
		
		//overwrite from - auto
		if ($i_ov_from_auto[0].checked){
			let m = ($i_semester.val()=='ws')? '10' : '04';
			let y = $i_year.val();
			_dataset.von = y+'-'+m+'-01';
		}
		
		// overwrite from - current date
		if ($i_ov_from_date[0].checked){
			_dataset.von = date_current;
		}
		
		// overwrite until
		if ($i_ov_until_auto[0].checked){
			let m = ($i_semester.val()=='ws')? '03' : '09';
			let y = parseInt($i_year.val()) + (($i_semester.val()=='ws')? 1 : 0);
			let d = ($i_semester.val()=='ws')? '31' : '30';
			_dataset.bis = y+'-'+m+'-'+d;
		}
		
		// extra dates - extra entry + flag
		_dataset_extra = null;
		if ($i_extra[0].checked){
			_dataset_extra = $.extend({}, _dataset);
			let m = ($i_semester.val()=='ws')? '04' : '10';
			let y = parseInt($i_year.val()) + (($i_semester.val()=='ws')? 1 : 0);
			_dataset_extra.von = y+'-'+m+'-01';
			
			m = ($i_semester.val()=='ws')? '03' : '09';
			y = parseInt($i_year.val()) + (($i_semester.val()=='ws')? 2 : 1);
			let d = ($i_semester.val()=='ws')? '31' : '30';
			_dataset_extra.bis = y+'-'+m+'-'+d;
			
			_dataset_extra.rolle_id = role_old_tutor;
		}
	};
	
	// waitmodal
	
	let wOpen = false;
	let wModal = null;
	let wProgressBar = null;
	const waitModal = function (){
		if (wOpen == false){
			wOpen = true;
			wModal = document.createElement('div');
			wModal.id = "dzFailedModal";
			wModal.style.display = 'block';
			wModal.className = 'modal pimage';
			wModal.innerHTML = '<div class="modal-content"><div class="modal-header bg-info"><h3>Bitte warten</h3></div><div class="modal-body text-center"><p></p><p><i class="fa fa-spinner fa-spin fa-fw fa-2x"></i></p><div class="text-center"><progress class="progressbar" value="0" max="100"></progress></div><div class="wProgress text-center"></div></div><div class="modal-footer bg-info"><h3></h3></div></div>';
			document.body.appendChild(wModal);
			wProgressBar = wModal.querySelector('progress.progressbar');
		}
	}
	
	const submit_cleanup = function(){
		// hide wait modal
		if(wModal != null){
			wModal.parentElement.removeChild(wModal); wProgressBar= null; wModal = null; wOpen = false;
		}
	};
	
	const ajax_worker = function(dset){
		console.log('------submit - '+ dset.rolle_id + ' -----------')
		console.log(dset);
		
		//parse dataset
		let data = new FormData();
		for (prop in dset){
			if (prop != 'caller_type' && dset.hasOwnProperty(prop)){
				data.append(prop, dset[prop]);
			}
		}
		let flag = dset.caller_type;
		
		$.ajax({
			type: 'POST',
			url: action_url,
			data: data,
			cache: false,
			contentType: false,
			processData: false,
			error: function (e) {
				handle_result_step(dset, e, false, flag);
			},
			success: function (e) {
				handle_result_step(dset, e, true, flag); 
			}
		});
	};
	
	const handle_result_step = function(dataset, response, success, flag){
		// visible content
		let $wProgress = $('.wProgress');
		$wProgress.text('Progress: '+ (progress) + ' ('+dataset.rolle_id + ' ' + flag + ') /' + datalength + ' n' + ((_dataset_extra != null)?', e':'') );		
		// progress
		progress++;
		wProgressBar.value = progress;
		// running
		running--;
		// success
		let round_success = success;
		let round_msg = null;
					
		if (round_success && !response.ret){
			round_success = false;
			round_msg = response.msgs;
		}
		success_all = success_all && round_success;
		// add to result array
		if (!round_success){
			resultl.push({success: false, rid: dataset.rolle_id, mail: dataset.email, err: round_msg});
		} else if (round_msg==null && response.msgs.length != 0){
			resultl.push({success: true, rid: dataset.rolle_id, mail: dataset.email, err: response.msgs});
		}
	};
	
	let resultl, progress, datalength, running, success_all;
	let is_running = false;

	const run_requests = function(dlist){
		if (!is_running){
			is_running = true;
			// init values
			resultl = [];
			progress = 0;
			datalength = dlist.length;
			running = 0;
			success_all = true;
			//update ui elements
			wProgressBar.max = datalength;
			let $wProgress = $('.wProgress');
			$wProgress.text('Progress: '+ (0) + '/' + datalength + ' n' + ((_dataset_extra != null)?', e':'') );

			let interv = null;
			let idx = 0;
			let loopfunc = function(){
				// limit by max_running
				if (running < max_running && idx < datalength){
				// run new workers
				ajax_worker(dlist[idx]);
				idx++;
				running++;
				}
				// check if is done
				if (progress == datalength && running == 0){
					//stop interval 
					clearInterval(interv);
					interv = null;
					is_running = false;
					submit_cleanup();
					present_results();
				}
			};
			interv = setInterval(loopfunc, 40);
		}
	};
  
	const present_results = function(){
		let modal = document.createElement('div');
		modal.id = "dzFailedModal";
		modal.style.display = 'block';
		modal.className = 'modal pimage';
		
		if (success_all) {
			modal.innerHTML = '<div class="modal-content"><div class="modal-header bg-success"><span class="close">&times;</span><h2>Tutoren eintragen - erfolgreich</h2></div><div class="modal-body"><p></p><strong>Es ist kein Fehler aufgetreten.'+((resultl.length) > 0? '<br><br><button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#collapseTutorLog" aria-expanded="false" aria-controls="collapseTutorLog">Show Log</button><br><br><div class="collapse" id="collapseTutorLog"><pre>'+ escapeHtml(JSON.stringify(resultl, null, 2)) +'</pre></div>'  : '')+'</strong></div><div class="modal-footer bg-success"><h3></h3></div></div>';
		} else {
			modal.innerHTML = '<div class="modal-content"><div class="modal-header bg-danger"><span class="close">&times;</span><h2>Tutoren eintragen - Fehler</h2></div><div class="modal-body"><p></p><strong>Folgende Einträge haben einen Fehler erzeugt:<br><pre>'+ escapeHtml(JSON.stringify(resultl, null, 2)) +'</pre></strong></div><div class="modal-footer bg-danger"><h3></h3></div></div>';
		}
		
		document.body.appendChild(modal);
		let span = modal.querySelector('.pimage.modal .close');
		span.onclick = function() { modal.parentElement.removeChild(modal); }
		window.onclick = function(event) { if (event.target == modal) { modal.parentElement.removeChild(modal); }  };
	};
  
	// submit
	$btn_submit.on('click', function(){
		if (check_content()){
			// show wait modal
			waitModal();
			// create datalist
			let tmplist = [];
			for(let i = 0; i < user_data.length; i++){
				// normal dset
				let dset = $.extend({}, _dataset);
				dset.rolle_id = user_data[i].rid;
				dset.caller_type = 'n';
				dset.email = user_data[i].umail;
				tmplist.push(dset);
				// extra dset
				if (_dataset_extra != null){
					let dset2 = $.extend({}, _dataset_extra);
					dset2.caller_type = 'e';
					dset2.email = user_data[i].umail;
					tmplist.push(dset2);
				}
			}
			// submit data
			run_requests(tmplist);
		} else {
			$btn_submit.hide();
		}
	});
	
	
	/*
	const sumbit_dataset = function (dset, callback){
		console.log('------submit - '+ dset.rolle_id + ' -----------')
		console.log(dset);

		let data = new FormData();
		for (prop in dset){
			if (dset.hasOwnProperty(prop)){
				data.append(prop, dset[prop]);
			}
		}

		$.ajax({
			type: 'POST',
			url: action_url,
			data: data,
			cache: false,
			contentType: false,
			processData: false,
			error: function (e,f,g) {
				if (callback != null){
					callback(false, e, f, g);
				} 
			},
			success: function (e,f,g) {
				if (callback != null){
					callback(true, e, f, g);
				} 
			}
		});
	}
	
	// submit
	$btn_submit.on('click', function(){
		if (check_content()){
			let idx = -1;
			
			// show wait modal
			waitModal();
			
			
			
			
			
			let success_all = true;
			let error_list = [];
			const callback_last = function(){				
				// hide wait modal
				submit_cleanup(); 
				
				let modal = document.createElement('div');
				modal.id = "dzFailedModal";
				modal.style.display = 'block';
				modal.className = 'modal pimage';
				
				if (success_all) {
					modal.innerHTML = '<div class="modal-content"><div class="modal-header bg-success"><span class="close">&times;</span><h2>Tutoren eintragen - erfolgreich</h2></div><div class="modal-body"><p></p><strong>Es ist kein Fehler aufgetreten.'+((error_list.length) > 0? '<br><br><button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#collapseTutorLog" aria-expanded="false" aria-controls="collapseTutorLog">Show Log</button><br><br><div class="collapse" id="collapseTutorLog"><pre>'+ escapeHtml(JSON.stringify(error_list, null, 2)) +'</pre></div>'  : '')+'</strong></div><div class="modal-footer bg-success"><h3></h3></div></div>';
				} else {
					modal.innerHTML = '<div class="modal-content"><div class="modal-header bg-danger"><span class="close">&times;</span><h2>Tutoren eintragen - Fehler</h2></div><div class="modal-body"><p></p><strong>Folgende Einträge haben einen Fehler erzeugt:<br><pre>'+ escapeHtml(JSON.stringify(error_list, null, 2)) +'</pre></strong></div><div class="modal-footer bg-danger"><h3></h3></div></div>';
				}
				
				document.body.appendChild(modal);
				let span = modal.querySelector('.pimage.modal .close');
				span.onclick = function() { modal.parentElement.removeChild(modal); }
				window.onclick = function(event) { if (event.target == modal) { modal.parentElement.removeChild(modal); }  };
			};
			
			let last_rid = 0;
			let last_caller = '';
			const success_handler = function (i, success, e, f, g){
				
				let $wProgress = $('.wProgress');
				$wProgress.text('Progress: '+ (parseInt(i)+1) + '/' + user_data.length + ' n' + ((_dataset_extra != null)?', e':'') );
				
				if (i > -1){
					let $wProgress = $('.wProgress');
					$wProgress.text('Progress: '+ (parseInt(i)+1) + last_caller + '/' + user_data.length + ' n' + ((_dataset_extra != null)?', e':'') );
					
					let round_success = true;
					let round_msg = null;
					
					if (round_success && !success){
						round_success = false;
					}
					if (round_success && !e.ret){
						round_success = false;
						round_msg = e.msgs;
					}
					
					if (!round_success){
						success_all = false;
						error_list.push({success: false, rid: last_rid, mail: user_data[i].umail, err: round_msg});
					} else if (round_msg==null && e.msgs.length != 0){
						error_list.push({success: true, rid: last_rid, mail: user_data[i].umail, err: e.msgs});
					}
					
				}
			};
			
			// submit normal request
			const submit_callback_normal = function(success, e,f,g){
				success_handler(idx, success, e,f,g);
				idx++;
				if (user_data.length >= idx + 1){
					let dset = $.extend({}, _dataset);
					dset.rolle_id = user_data[idx].rid;
					last_rid = dset.rolle_id;
					last_caller = 'n';
					dset.email = user_data[idx].umail;
					setTimeout(function(){
						sumbit_dataset(dset, submit_callback_extra);
					} , 20);
				} else {
					callback_last();
				}
			};
			
			// submit extra request
			const submit_callback_extra = function(success, e,f,g){
				if (_dataset_extra != null && user_data.length >= idx + 1){
					success_handler(idx, success, e,f,g);
					let dset = $.extend({}, _dataset_extra);
					last_rid = dset.rolle_id;
					last_caller = 'e';
					dset.email = user_data[idx].umail;
					setTimeout(function(){
						sumbit_dataset(dset, submit_callback_normal);
					} , 20);
				} else {
					submit_callback_normal(success, e,f,g);
				}
			};
			
			submit_callback_normal();
		} else {
			$btn_submit.hide();
		}
	});

	*/
})();

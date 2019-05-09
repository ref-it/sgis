(function(){

	const escapeHtml = function (text) {
		return text
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;")
			.replace(/'/g, "&#039;");
	}

	let wOpen = false;
	let wModal = null;
	const waitModal = function (){
		if (wOpen == false){
			wOpen = true;
			
			wModal = document.createElement('div');
			wModal.id = "dzFailedModal";
			wModal.style.display = 'block';
			wModal.className = 'modal pimage';
			wModal.innerHTML = '<div class="modal-content"><div class="modal-header bg-info"><span class="close">&times;</span><h3>Bitte warten</h3></div><div class="modal-body text-center"><p></p><p><i class="fa fa-spinner fa-spin fa-fw fa-2x"></i></p></div><div class="modal-footer bg-info"><h3></h3></div></div>';
			document.body.appendChild(wModal);
			let span = wModal.querySelector('.pimage.modal .close');
		}
	}
	
	
	if (typeof(pimage) != 'undefined' && pimage === 'dropzone'){
		const cw = document.querySelector('.croppie_wrapper_inner');
		const dzw = document.querySelector('#pDropzone');
		const confirm_btn = document.querySelector('.confirm_croppie');
		const abort_btn = document.querySelector('.abort_croppie');
		const rotatec_btn = document.querySelector('.rotate_c_croppie');
		const rotatea_btn = document.querySelector('.rotate_a_croppie');
		Dropzone.autoDiscover = false;
		
		let pDropzone = null;
		let croppie = null;
		
		const load_dropzone = function(){
			dzw.innerHTML = "<div class='dz0 dropzone'></div><i>* Es werden nur Bilder im PNG und JPEG format unterstützt.</i>";
			cw.innerHTML = "<hr><div class='cp0'></div><div style='margin-bottom: 50px;'></div><hr>";
			cw.style.display = "none";
			confirm_btn.style.display = "none";
			abort_btn.style.display = "none";
			rotatec_btn.style.display = "none";
			rotatea_btn.style.display = "none";
			dzw.style.display = "block";
			
			pDropzone = new Dropzone('.dz0', {
				url: '/sgis/index.php',
				transformFile: loadCroppie,
				paramName: "file[]", // The name that will be used to transfer the file
				maxFilesize: 5, // MB
				params: function(){
					var obj = {};
					var fchal = document.getElementsByName('nonce')[0];
					obj[fchal.getAttribute("name")] = fchal.value;
					obj.action = 'pimage.upload';
					return obj;
				},
				uploadMultiple: false,
				createImageThumbnails: true,
				clickable:true,
				ignoreHiddenFiles: true,
				maxFiles: 1,
				parallelUploads: 1,
				acceptedFiles: 'image/png,image/jpg,image/jpeg',
				autoProcessQueue: true,
				addRemoveLinks: true,
				hiddenInputContainer: 'body',
				forceFallack: false,
				thumbnailWidth: 85,
				thumbnailHeight: 85,
				dictDefaultMessage: 'Profilbild zum Hochladen hier hinein ziehen.<br><i class="mt-2 fa fa-upload fa-4x d-block"></i>',
				dictRemoveFile: 'Entfernen',
				error: function (file, response) {
					var text = response;
					text = text.replace(/<(br|div)>/g, "\n");
					var div = document.createElement("div");
					div.innerHTML = text;
					text = div.textContent || div.innerText || "";
					

					let modal = document.createElement('div');
					modal.id = "dzFailedModal";
					modal.style.display = 'block';
					modal.className = 'modal pimage';
					modal.innerHTML = '<div class="modal-content"><div class="modal-header bg-danger"><span class="close">&times;</span><h2>Es ist ein Fehler aufgetreten</h2></div><div class="modal-body"><p></p><strong><pre>'+ text + '</pre></strong></div><div class="modal-footer bg-danger"><h3></h3></div></div>';
					document.body.appendChild(modal);
					let span = modal.querySelector('.pimage.modal .close');
					span.onclick = function() { modal.parentElement.removeChild(modal); load_dropzone(); if(wModal != null){ wModal.parentElement.removeChild(wModal); wModal = null;} }
					window.onclick = function(event) { if (event.target == modal) { modal.parentElement.removeChild(modal); load_dropzone(); } if(wModal != null){ wModal.parentElement.removeChild(wModal); wModal = null;} };
				},
				success: function (file, response) {
					location.reload(true);
				}
			});
		}
		
		const loadCroppie = function (file, done){
			cw.style.display = "block";
			confirm_btn.style.display = "inline-block";
			abort_btn.style.display = "inline-block";
			rotatec_btn.style.display = "inline-block";
			rotatea_btn.style.display = "inline-block";
			dzw.style.display = "none";
			let cpe = cw.querySelector('.cp0');

			var type_short = file.type;
			type_short = type_short.replace('image/', '');
			
			// load croppie
			// Create the Croppie editor
			var croppie = new Croppie(cpe, {
				enableResize: false,
				viewport: {
					width: 92,
					height: 128
				},
				type: 'square',
				enforceBoundary: true,
				enableOrientation: true
			});

			// Tell Croppie to load the file
			croppie.bind({
				url: URL.createObjectURL(file)
			});
			
			rotatea_btn.onclick=function(){
				croppie.rotate(90);
			};
			rotatec_btn.onclick=function(){
				croppie.rotate(-90);
			};
			
			confirm_btn.onclick = function() {
				// Get the output file data from Croppie
				croppie.result({
					type:'blob',
					size: {
						width: 920,
						height: 1280
					},
				    format: ''+type_short,
					quality:0.8,
				}).then(function(blob) {
				
				// Create a new Dropzone file thumbnail
				pDropzone.createThumbnail(
					blob,
					pDropzone.options.thumbnailWidth,
					pDropzone.options.thumbnailHeight,
					pDropzone.options.thumbnailMethod,
					false, 
					function(dataURL) {
					
						// Update the Dropzone file thumbnail
						pDropzone.emit('thumbnail', file, dataURL);

						dzw.style.display = "block";
						cw.style.display = "none";
						
						waitModal();
						
						console.log(blob);
						
						// Tell Dropzone of the new file
						done(blob);
					});
				});
			};
		}
		abort_btn.addEventListener("click", load_dropzone); ;
		load_dropzone();
		
	} else if(typeof(pimage) != 'undefined' && pimage === 'remove') {
		const remove_btn = document.querySelector('.pimage_remove');
		remove_btn.addEventListener('click', function(ev){
			waitModal();
			let dataset = {};
			var fchal = document.getElementsByName('nonce')[0];
				dataset[fchal.getAttribute("name")] = fchal.value;
				dataset.action = 'pimage.remove';
			$.ajax({
				type: 'POST',
				url: '/sgis/index.php',
				data: dataset,
				error: function (e,f,g) {
					let modal = document.createElement('div');
					modal.id = "dzFailedModal";
					modal.style.display = 'block';
					modal.className = 'modal pimage';
					modal.innerHTML = '<div class="modal-content"><div class="modal-header"><span class="close">&times;</span><h3>Es ist ein Fehler aufgetreten</h3></div><div class="modal-body"><pre>'+ escapeHtml(e.responseText) + '</pre></div><div class="modal-footer"><h3></h3></div></div>';
					document.body.appendChild(modal);
					let span = modal.querySelector('.pimage.modal .close');
					console.log(span);
					span.onclick = function() { modal.parentElement.removeChild(modal); load_dropzone(); }
					window.onclick = function(event) { if (event.target == modal) { modal.parentElement.removeChild(modal); load_dropzone(); } };
				},
				success: function (e,f,g) {
					location.reload(true);
				}
			});
		});
	}
})();


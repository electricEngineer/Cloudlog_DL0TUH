$('.bandtable').DataTable({
	"pageLength": 25,
	responsive: false,
	ordering: false,
	"scrollY": "500px",
	"scrollCollapse": true,
	"paging": false,
	"scrollX": true
});

function createBandDialog() {
	$.ajax({
		url: base_url + 'index.php/band/create',
		type: 'post',
		success: function (html) {
			BootstrapDialog.show({
				title: 'Create band',
				size: BootstrapDialog.SIZE_WIDE,
				cssClass: 'create-band-dialog',
				nl2br: false,
				message: html,
				buttons: [{
					label: 'Close',
					action: function (dialogItself) {
						dialogItself.close();
					}
				}]
			});
		}
	});
}

function createBand(form) {
	if (form.mode.value != '') {
		$.ajax({
			url: base_url + 'index.php/band/create',
			type: 'post',
			data: {
				'band': form.band.value
			},
			success: function (html) {
				location.reload();
			}
		});
	}
}

function deactivateBand(bandid) {
	$.ajax({
		url: base_url + 'index.php/band/deactivate',
		type: 'post',
		data: { 'id': bandid },
		success: function (html) {
			$(".mode_" + modeid).text('not active');
			$('.btn_' + modeid).html('Activate');
			$('.btn_' + modeid).attr('onclick', 'activateMode(' + modeid + ')')
		}
	});
}

function activateBand(bandid) {
	$.ajax({
		url: base_url + 'index.php/band/activate',
		type: 'post',
		data: { 'id': bandid },
		success: function (html) {
			$('.mode_' + modeid).text('active');
			$('.btn_' + modeid).html('Deactivate');
			$('.btn_' + modeid).attr('onclick', 'deactivateMode(' + modeid + ')')
		}
	});
}

function deleteBand(id, band) {
	BootstrapDialog.confirm({
		title: 'DANGER',
		message: 'Warning! Are you sure you want to delete the following band: ' + band + '?',
		type: BootstrapDialog.TYPE_DANGER,
		closable: true,
		draggable: true,
		btnOKClass: 'btn-danger',
		callback: function (result) {
			if (result) {
				$.ajax({
					url: base_url + 'index.php/band/delete',
					type: 'post',
					data: {
						'id': id
					},
					success: function (data) {
						$(".band_" + id).parent("tr:first").remove(); // removes mode from table
					}
				});
			}
		}
	});
}

function activateAllBands() {
	BootstrapDialog.confirm({
		title: 'DANGER',
		message: 'Warning! Are you sure you want to activate all bands?',
		type: BootstrapDialog.TYPE_DANGER,
		closable: true,
		draggable: true,
		btnOKClass: 'btn-danger',
		callback: function (result) {
			if (result) {
				$.ajax({
					url: base_url + 'index.php/band/activateall',
					type: 'post',
					success: function (data) {
						location.reload();
					}
				});
			}
		}
	});
}

function deactivateAllBands() {
	BootstrapDialog.confirm({
		title: 'DANGER',
		message: 'Warning! Are you sure you want to deactivate all bands?',
		type: BootstrapDialog.TYPE_DANGER,
		closable: true,
		draggable: true,
		btnOKClass: 'btn-danger',
		callback: function (result) {
			if (result) {
				$.ajax({
					url: base_url + 'index.php/band/deactivateall',
					type: 'post',
					success: function (data) {
						location.reload();
					}
				});
			}
		}
	});
}

function saveBand(id) {
	$.ajax({
		url: base_url + 'index.php/band/saveBand',
		type: 'post',
		data: {'id': id,  
			'status': $(".band_"+id+" input[type='checkbox']").is(":checked"),
			'cq': $(".cq_"+id+" input[type='checkbox']").is(":checked"),
			'dok': $(".dok_"+id+" input[type='checkbox']").is(":checked"),
			'dxcc': $(".dxcc_"+id+" input[type='checkbox']").is(":checked"),
			'iota': $(".iota_"+id+" input[type='checkbox']").is(":checked"),
			'sig': $(".sig_"+id+" input[type='checkbox']").is(":checked"),
			'sota': $(".sota_"+id+" input[type='checkbox']").is(":checked"),
			'uscounties': $(".uscounties_"+id+" input[type='checkbox']").is(":checked"),
			'was': $(".was_"+id+" input[type='checkbox']").is(":checked"),
			'vucc': $(".vucc_"+id+" input[type='checkbox']").is(":checked")
		},
		success: function (html) {
			// Add an alert to say that it is saved. Vanish after 5 seconds.
		}
	});
}
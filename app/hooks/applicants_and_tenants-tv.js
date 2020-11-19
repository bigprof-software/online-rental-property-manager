/* start of mass_update code */
var massUpdateAlert = function(msg, showOk, okClass) {
	if(showOk == undefined) showOk = false;
	if(okClass == undefined) okClass = 'default';

	var footer = [];
	if(showOk) footer.push({ label: massUpdateTranslation.ok, bs_class: okClass });

	$j('.modal').modal('hide');
	var mId = modal_window({ message: '', title: msg, footer: footer });
	$j('#' + mId).find('.modal-body').remove();
	if(!footer.length) $j('#' + mId).find('.modal-footer').remove();
}


/* Change status command */
function massUpdateCommand_m8xfpspfv9y3762768or(tn, ids) {

	/* Ask user for new value */
	modal_window({
		id: 'mass-update-new-value-modal',
		message: "<div id=\"mass-update-new-value\"><\/div>",
		title: '<i class="glyphicon glyphicon-circle-arrow-right"></i> ' + 
			"Change status",
		footer :[{
			label: massUpdateTranslation.confirm,
			bs_class: 'primary',
			click: function () {
				var newValue = $j('#mass-update-new-value').select2('val')

				/* ask user for confirmation before applying updates */
				if(!confirm(massUpdateTranslation.areYouSureApply)) return;

				/* send update request */
				massUpdateAlert(massUpdateTranslation.pleaseWait);
				$j.ajax({
					url: "hooks\/ajax-mass-update-applicants_and_tenants-status-m8xfpspfv9y3762768or.php",
					data: { ids: ids, newValue: newValue },
					success: function() { location.reload(); },
					error: function() {
						massUpdateAlert('<span class="text-danger">' + massUpdateTranslation.error + '</span>', true, 'danger');
					}
				});
			}
		}]
	});


	/* prepare select2 drop-down inside modal */
	$j('#mass-update-new-value-modal').on('shown.bs.modal', function () {
		$j("#mass-update-new-value").select2({
			width: '100%',
			formatNoMatches: function(term){ return massUpdateTranslation.noMatches; },
			minimumResultsForSearch: 5,
			loadMorePadding: 200,
			data: [{"id":"Applicant","text":"Applicant"},{"id":"Tenant","text":"Tenant"},{"id":"Previous tenant","text":"Previous tenant"}],
			escapeMarkup: function(str){ return str; }
		}).select2('focus');
	});

}

/* Edit Notes command */
function massUpdateCommand_li3ml58uplm5oyp54b8x(tn, ids) {

	/* Ask user for new value */
	modal_window({
		id: 'mass-update-new-value-modal',
		message: "<textarea rows=\"4\" cols=\"50\" class=\"form-control\" id=\"mass-update-new-value\"><\/textarea>",
		title: '<i class="glyphicon glyphicon-pencil"></i> ' + 
			"Edit Notes",
		footer :[{
			label: massUpdateTranslation.confirm,
			bs_class: 'primary',
			click: function () {
				var newValue = $j('.modal .nicEdit-main').html()

				/* ask user for confirmation before applying updates */
				if(!confirm(massUpdateTranslation.areYouSureApply)) return;

				/* send update request */
				massUpdateAlert(massUpdateTranslation.pleaseWait);
				$j.ajax({
					url: "hooks\/ajax-mass-update-applicants_and_tenants-notes-li3ml58uplm5oyp54b8x.php",
					data: { ids: ids, newValue: newValue },
					success: function() { location.reload(); },
					error: function() {
						massUpdateAlert('<span class="text-danger">' + massUpdateTranslation.error + '</span>', true, 'danger');
					}
				});
			}
		}]
	});


	/* prepare nicEditor inside modal */
	$j('#mass-update-new-value-modal').on('shown.bs.modal', function () {
		new nicEditor({ fullPanel : true }).panelInstance('mass-update-new-value');
		$j('.nicEdit-panelContain').parent().width('100%');
		$j('.nicEdit-panelContain').parent().next().width('98%');
		$j('.nicEdit-main').width('99%');
		$j('.nicEdit-main').attr('style', 'min-height: 5em !important');

		// focus on nicedit box
		$j('.modal .nicEdit-main').focus();
	});

}
/* end of mass_update code */
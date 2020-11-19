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
function massUpdateCommand_02ra72cumuxuqy0sm1hq(tn, ids) {

	/* Ask user for new value */
	modal_window({
		id: 'mass-update-new-value-modal',
		message: "<div id=\"mass-update-new-value\"><\/div>",
		title: '<i class="glyphicon glyphicon-tags"></i> ' + 
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
					url: "hooks\/ajax-mass-update-units-status-02ra72cumuxuqy0sm1hq.php",
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
			data: [{"id":"Occupied","text":"Occupied"},{"id":"Listed","text":"Listed"},{"id":"Unlisted","text":"Unlisted"}],
			escapeMarkup: function(str){ return str; }
		}).select2('focus');
	});

}
/* end of mass_update code */
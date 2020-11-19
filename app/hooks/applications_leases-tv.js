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


/* Approve application command */
function massUpdateCommand_ghqe4agakj7de10gc0ba(tn, ids) {

	/* ask user for confirmation before applying updates */
	if(!confirm(massUpdateTranslation.areYouSureApply)) return;

	massUpdateAlert(massUpdateTranslation.pleaseWait);

	$j.ajax({
		url: "hooks\/ajax-mass-update-applications_leases-status-ghqe4agakj7de10gc0ba.php",
		data: { ids: ids },
		success: function() { location.reload(); },
		error: function() {
			massUpdateAlert('<span class="text-danger">' + massUpdateTranslation.error + '</span>', true, 'danger');
		}
	});

}
/* end of mass_update code */
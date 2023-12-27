var AppGini = AppGini || {};

AppGini.version = 23.17;

/* initials and fixes */
jQuery(function() {
	AppGini.count_ajaxes_blocking_saving = 0;

	/* add ":truncated" pseudo-class to detect elements with clipped text */
	$j.expr[':'].truncated = function(obj) {
		var $this = $j(obj);
		var $c = $this
					.clone()
					.css({ display: 'inline', width: 'auto', visibility: 'hidden', 'padding-right': 0 })
					.css({ 'font-size': $this.css('font-size') })
					.appendTo('body');

		var e_width = $this.outerWidth();
		var c_width = $c.outerWidth();
		$c.remove();

		return ( c_width > e_width );
	};

	$j(window).resize(function() {
		var window_width = $j(window).width();
		var max_width = $j('body').width() * 0.5;

		var full_img_factor = 0.9; /* xs */
		if(window_width >= 992) full_img_factor = 0.6; /* md, lg */
		else if(window_width >= 768) full_img_factor = 0.9; /* sm */

		$j('.detail_view .img-responsive').css({'max-width' : parseInt($j('.detail_view').width() * full_img_factor) + 'px'});

		/* change height of sizer div below navbar to accomodate navbar height */
		$j('.top-margin-adjuster').height($j('.navbar-fixed-top:visible').height() ?? 10);

		/* remove labels from truncated buttons, leaving only glyphicons */
		$j('.btn.truncate:truncated').each(function() {
			// hide text
			var label = $j(this).html();
			var mlabel = label.replace(/.*(<i.*?><\/i>).*/, '$1');
			$j(this).html(mlabel);
		});
	});

	setTimeout(function() { $j(window).resize(); }, 1000);
	setTimeout(function() { $j(window).resize(); }, 3000);

	/* don't allow saving detail view when there's an ajax request to a url that matches the following */
	var ajax_blockers = new RegExp(/(ajax_combo\.php|_autofill\.php|ajax_check_unique\.php)/);
	var updateBtnHtml = '', insertBtnHtml = '';
	$j(document).ajaxSend(function(e, r, s) {
		if(s.url.match(ajax_blockers)) {
			AppGini.count_ajaxes_blocking_saving++;
			$j('#update, #insert').prop('disabled', true);

			updateBtnHtml = updateBtnHtml || $j('#update').html();
			insertBtnHtml = insertBtnHtml || $j('#insert').html();
			if(updateBtnHtml)
				$j('#update').html('<i class="glyphicon glyphicon-refresh loop-rotate"></i>');
			if(insertBtnHtml)
				$j('#insert').html('<i class="glyphicon glyphicon-refresh loop-rotate"></i>');
		}
	});
	$j(document).ajaxComplete(function(e, r, s) {
		if(s.url.match(ajax_blockers)) {
			AppGini.count_ajaxes_blocking_saving = Math.max(AppGini.count_ajaxes_blocking_saving - 1, 0);
			if(AppGini.count_ajaxes_blocking_saving <= 0) {
				$j('#update:not(.user-locked), #insert').prop('disabled', false);
				if(updateBtnHtml) $j('#update').html(updateBtnHtml);
				if(insertBtnHtml) $j('#insert').html(insertBtnHtml);
			}
		}
	});

	/* don't allow responsive images to initially exceed the smaller of their actual dimensions, or .6 container width */
	jQuery('.detail_view .img-responsive').each(function() {
		 var pic_real_width, pic_real_height;
		 var img = jQuery(this);
		 jQuery('<img/>') // Make in memory copy of image to avoid css issues
				.attr('src', img.attr('src'))
				.on('load', function() {
					pic_real_width = this.width;
					pic_real_height = this.height;

					if(pic_real_width > $j('.detail_view').width() * .6) pic_real_width = $j('.detail_view').width() * .6;
					img.css({ "max-width": pic_real_width });
				});
	});

	jQuery('.table-responsive .img-responsive').each(function() {
		 var pic_real_width, pic_real_height;
		 var img = jQuery(this);
		 jQuery('<img/>') // Make in memory copy of image to avoid css issues
				.attr('src', img.attr('src'))
				.on('load', function() {
					pic_real_width = this.width;
					pic_real_height = this.height;

					if(pic_real_width > $j('.table-responsive').width() * .6) pic_real_width = $j('.table-responsive').width() * .6;
					img.css({ "max-width": pic_real_width });
				});
	});

	/* toggle TV action buttons based on selected records */
	jQuery('.record_selector').click(function() {
		var id = jQuery(this).val();
		var checked = jQuery(this).prop('checked');
		update_action_buttons();
	});

	/* select/deselect all records in TV */
	jQuery('#select_all_records').click(function() {
		jQuery('.record_selector').prop('checked', jQuery(this).prop('checked'));
		update_action_buttons();
	});

	/* fix behavior of select2 in bootstrap modal. See: https://github.com/ivaynberg/select2/issues/1436 */
	jQuery.fn.modal.Constructor.prototype.enforceFocus = function() { };

	/* remove empty navbar menus */
	$j('nav li.dropdown').each(function() {
		var num_items = $j(this).children('.dropdown-menu').children('li').length;
		if(!num_items) $j(this).remove();
	})

	update_action_buttons();

	/* remove empty images and links */
	$j(`.table a[href="${AppGini.config.imgFolder}"], img[src="${AppGini.config.imgFolder}"]`).remove();

	/* remove empty email links */
	$j('a[href="mailto:"]').remove();

	/* Disable action buttons when form is submitted to avoid user re-submission on slow connections */
	$j('form').eq(0).submit(function() {
		setTimeout(function() {
			var tn = AppGini.currentTableName();
			$j('#' + tn + '_dv_action_buttons').find('.btn').prop('disabled', true);
		}, 200); // delay purpose is to allow submitting the button values first then disable them.
	});

	/* fix links inside alerts */
	$j('.alert a:not(.btn)').addClass('alert-link');

	/* highlight selected/focused rows */
	var highlightSelectedRows = function() {
		AppGini.defineHighlightClass();
		$j('tr .record_selector').each(function() {
			var sel = $j(this);
			sel.parents('tr').toggleClass('highlighted-record', sel.prop('checked') || sel.is(':focus'));
		});
	}
	setInterval(highlightSelectedRows, 100);

	// format .locale-int and .locale-float numbers
	// to stop it, set AppGini.noLocaleFormatting to true in a hook script in footer-extras.php, .. etc
	if(!AppGini.noLocaleFormatting) setInterval(() => {
		$j('.locale-int').each(function() {
			let i = $j(this).text().trim();
			if(!/^\d+(\.\d+)?$/.test(i)) return; // already formatted or invalid number

			$j(this).text(parseInt(i).toLocaleString());
		})

		$j('.locale-float').each(function() {
			let f = $j(this).text().trim();
			if(!/^\d+(\.\d+)?$/.test(f)) return; // already formatted or invalid number

			// preserve decimals
			const countDecimals = (f.split('.')[1] || '').length;

			$j(this).text(parseFloat(f).toLocaleString(undefined, {minimumFractionDigits: countDecimals, maximumFractionDigits: countDecimals}));
		})
	}, 100);

	/* update calculated fields */
	AppGini.calculatedFields.init();

	/* on changing an upload field, check file type and size */
	$j('input[type="file"]').on('change', function() {
		var id = $j(this).attr('id'),
			types = $j(this).data('filetypes'),
			maxSize = $j(this).data('maxsize');
		if(id == undefined || types == undefined || maxSize == undefined) return;

		AppGini.checkFileUpload(id, types, maxSize);
		AppGini.previewUploadedImage(id);
	})

	/* allow clearing chosen file upload */
	$j('.clear-upload').on('click', function() {
		$j(this)
			.addClass('hidden')
			.parents('.form-group')
			.find('input[type="file"]')
			.val('')
			.trigger('change');
	})

	/* confirm when deleting a record, and prevent form validation */
	$j('button[id=delete]').on('click', (e) => {
		if(!confirm(AppGini.Translate._map['are you sure?'])) {
			e.preventDefault();
			return false;
		}

		$j('form').attr('novalidate', 'novalidate')
		return true;
	})

	/* select email/web links on uncollapsing in DV */
	$j('.detail_view').on('click', '.btn[data-toggle="collapse"]', function() {
		var target = $j($j(this).data('target'));
		setTimeout(function() { target.focus(); }, 100);
	});

	// adjust DV page title link to go back if appropriate
	AppGini.alterDVTitleLinkToBack();

	AppGini.lockUpdatesOnUserRequest();

	// in table view, hide unnecessary page elements if no records are displayed
	if($j('.table_view').length) {
		var tvHasWarning = $j('.table_view tfoot .alert-warning').length > 0;

		setInterval(function() {
			$j('#Print, #CSV, .tv-tools, .table_view thead, .table_view tr.success')
				.toggleClass('hidden', tvHasWarning);

			$j('.tv-toggle').parent().toggleClass('hidden', tvHasWarning);
		}, 100);
	}

	// remove children-tabs from detail view for a new record
	AppGini.newRecord(function() { $j('.children-tabs').remove(); })

	// apply keyboard shortcuts
	AppGini.handleKeyboardShortcuts();
	AppGini.updateKeyboardShortcutsStatus();

	// handle clearing dates
	AppGini.handleClearingDates();

	// apply nicedit BGs
	AppGini.once({
		condition: () => $j('.nicedit-bg').length > 0,
		action: AppGini.applyNiceditBgColors,
		timeout: 30000 // stop trying after 30 sec
	});

	// for read-only lookups in DV, prevent flex layout
	AppGini.once({
		condition: () => $j('.lookup-flex .match-text').length > 0,
		action: () => $j('.lookup-flex .match-text').addClass('rspacer-lg').parents('.lookup-flex').removeClass('lookup-flex').addClass('form-control-static'),
		timeout: 30000
	})

	// define dragover and dragover-error CSS classes based on current theme colors
	AppGini.createCSSClass('dragover', 'alert alert-info', ['border-color'], {'border-width': '3px', 'border-style': 'dashed'});
	AppGini.createCSSClass('dragover-error', 'alert alert-danger', ['border-color'], {'border-width': '3px'});

	// allow drag and drop of files into file upload fields
	$j('input[type=file]').parents('.form-control-static')
	.on('dragover', (e) => {
		e.preventDefault();
		$j(e.currentTarget).addClass('dragover');
	})
	.on('dragleave', (e) => {
		$j(e.currentTarget).removeClass('dragover dragover-error');
	})
	.on('drop', (e) => {
		e.preventDefault();
		$j(e.currentTarget).removeClass('dragover dragover-error');

		const fileList = e.originalEvent.dataTransfer.files ?? null;
		if(!fileList || fileList.length > 1) {
			$j(e.currentTarget).addClass('dragover-error');
			return;
		}

		const input = $j(e.currentTarget).find('input[type=file]');
		input[0].files = fileList;
		input.trigger('change');
	})

	// open links that have a .modal-link class in a modal window
	$j('body').on('click', 'a[href].modal-link', function(e) {
		e.preventDefault();
		modal_window({
			url: $j(this).attr('href'),
			title: $j(this).attr('title') || $j(this).text(),
			size: 'full',
		});
	})

	// update child counts
	AppGini.updateChildrenCount();

	// update calculated fields if childrenCountChanged event is triggered
	$j(document).on('childrenCountChanged', () => AppGini.calculatedFields.init());

	// trigger AppGini.updateChildrenCount() on clicking .update-children-count
	$j(document).on('click', '.update-children-count', function() {
		AppGini.updateChildrenCount(false); // update only once -- no rescheduling
	})

	AppGini.once({
		condition: () => AppGini.Translate !== undefined,
		action: () => moment.locale(AppGini.Translate._map['datetimepicker locale'])
	})

	// if upload toolbox is empty, hide it
	$j('.upload-toolbox').toggleClass('hidden', !$j('.upload-toolbox').children().not('.hidden').length)
});

/* show/hide TV action buttons based on whether records are selected or not */
function update_action_buttons() {
	if(jQuery('.record_selector:checked').length) {
		jQuery('.selected_records').removeClass('hidden');
		jQuery('#select_all_records')
			.prop('checked', (jQuery('.record_selector:checked').length == jQuery('.record_selector').length));
	} else {
		jQuery('.selected_records').addClass('hidden');
	}
}

/* fix table-responsive behavior on Chrome */
function fix_table_responsive_width() {
	var resp_width = jQuery('div.table-responsive').width();
	var table_width;

	if(resp_width) {
		jQuery('div.table-responsive table').width('100%');
		table_width = jQuery('div.table-responsive table').width();
		resp_width = jQuery('div.table-responsive').width();
		if(resp_width == table_width) {
			jQuery('div.table-responsive table').width(resp_width - 1);
		}
	}
}

AppGini.ajaxCache = function() {
	var _tests = [];

	/*
		An array of functions that receive a parameterless url and a parameters object,
		makes a test,
		and if test passes, executes something and/or
		returns a non-false value if test passes,
		or false if test failed (useful to tell if tests should continue or not)
	*/
	var addCheck = function(check) { /* */
		if(typeof(check) == 'function') {
			_tests.push(check);
		}
	};

	var _jqAjaxData = function(opt) { /* */
		var opt = opt || {};   
		var url = opt.url || '';
		var data = opt.data || {};

		var params = url.match(/\?(.*)$/);
		var param = (params !== null ? params[1] : '');

		var sPageURL = decodeURIComponent(param),
			sURLVariables = sPageURL.split('&'),
			sParameter,
			i;

		for(i = 0; i < sURLVariables.length; i++) {
			sParameter = sURLVariables[i].split('=');
			if(sParameter[0] == '') continue;
			data[sParameter[0]] = sParameter[1] || '';
		}

		return data;
	};

	var start = function() { /* */
		if(!_tests.length) return; // no need to monitor ajax requests since no checks were defined
		var reqTests = _tests;
		$j.ajaxPrefilter(function(options, originalOptions, jqXHR) {
			var success = originalOptions.success || $j.noop,
				data = _jqAjaxData(originalOptions),
				oUrl = originalOptions.url || '',
				url = oUrl.match(/\?/) ? oUrl.match(/(.*)\?/)[1] : oUrl;

			options.beforeSend = function() { /* */
				var req, cached = false, resp;

				for(var i = 0; i < reqTests.length; i++) {
					resp = reqTests[i](url, data);
					if(resp === false) continue;

					success(resp);
					return false;
				}

				return true;
			}
		});
	};

	return {
		addCheck: addCheck,
		start: start
	};
};

function applicants_and_tenants_validateData() {
	$j('.has-error').removeClass('has-error');
	var errors = false;

	// check all required fields have values
	if(!AppGini.Validation.fieldRequired('radio', 'status', 'Status')) return false;

	return !errors;
}
function applications_leases_validateData() {
	$j('.has-error').removeClass('has-error');
	var errors = false;

	// check all required fields have values
	if(!AppGini.Validation.fieldRequired('radio', 'status', 'Application status')) return false;
	if(!AppGini.Validation.fieldRequired('radio', 'type', 'Lease type')) return false;
	if(!AppGini.Validation.fieldRequired('list', 'recurring_charges_frequency', 'Recurring charges frequency')) return false;

	return !errors;
}
function residence_and_rental_history_validateData() {
	$j('.has-error').removeClass('has-error');
	var errors = false;

	return !errors;
}
function employment_and_income_history_validateData() {
	$j('.has-error').removeClass('has-error');
	var errors = false;

	return !errors;
}
function references_validateData() {
	$j('.has-error').removeClass('has-error');
	var errors = false;

	return !errors;
}
function rental_owners_validateData() {
	$j('.has-error').removeClass('has-error');
	var errors = false;

	return !errors;
}
function properties_validateData() {
	$j('.has-error').removeClass('has-error');
	var errors = false;

	// check all required fields have values
	if(!AppGini.Validation.fieldRequired('text', 'property_name', 'Property Name')) return false;
	if(!AppGini.Validation.fieldRequired('radio', 'type', 'Type')) return false;

	// check file uploads (file type and size)
	if($j('#photo').val() && !AppGini.checkFileUpload('photo', 'jpg|jpeg|gif|png|webp', 2048000)) {
		AppGini.scrollTo('photo');
		return false;
	}

	return !errors;
}
function property_photos_validateData() {
	$j('.has-error').removeClass('has-error');
	var errors = false;

	// check file uploads (file type and size)
	if($j('#photo').val() && !AppGini.checkFileUpload('photo', 'jpg|jpeg|gif|png|webp', 2048000)) {
		AppGini.scrollTo('photo');
		return false;
	}

	return !errors;
}
function units_validateData() {
	$j('.has-error').removeClass('has-error');
	var errors = false;

	// check all required fields have values
	if(!AppGini.Validation.fieldRequired('radio', 'status', 'Status')) return false;

	// check file uploads (file type and size)
	if($j('#photo').val() && !AppGini.checkFileUpload('photo', 'jpg|jpeg|gif|png|webp', 2048000)) {
		AppGini.scrollTo('photo');
		return false;
	}

	return !errors;
}
function unit_photos_validateData() {
	$j('.has-error').removeClass('has-error');
	var errors = false;

	// check file uploads (file type and size)
	if($j('#photo').val() && !AppGini.checkFileUpload('photo', 'jpg|jpeg|gif|png|webp', 2048000)) {
		AppGini.scrollTo('photo');
		return false;
	}

	return !errors;
}

function post(url, params, update, disable, loading, success_callback) {
	$j.ajax({
		url: url,
		type: 'POST',
		data: params,
		beforeSend: function() {
			if($j('#' + disable).length) $j('#' + disable).prop('disabled', true);
			if($j('#' + loading).length && update != loading)
				$j('#' + loading).html(
					'<div style="direction: ltr;"><i class="glyphicon glyphicon-refresh loop-rotate"></i> $loading</div>'
					.replace('$loading', AppGini.Translate._map['Loading ...'])
				);
		},
		success: function(resp) {
			if($j('#' + update).length) $j('#' + update).html(resp);
			if(success_callback != undefined)
				success_callback();
			else
				// re-calculate fields by default if no other callback explicitly passed
				AppGini.calculatedFields.init();
		},
		complete: function() {
			if($j('#' + disable).length) $j('#' + disable).prop('disabled', false);
			if($j('#' + loading).length && loading != update) $j('#' + loading).html('');
		}
	});
}

function post2(url, params, notify, disable, loading, redirectOnSuccess) {
	new Ajax.Request(
		url, {
			method: 'post',
			parameters: params,
			onCreate: function() {
				if($j('#' + disable).length) $j('#' + disable).prop('disabled', true);
				if($j('#' + loading).length) $j('#' + loading).show();
			},
			onSuccess: function(resp) {
				/* show notification containing returned text */
				if($j('#' + notify).length)
					$j('#' + notify).removeClass('alert-danger').show().html(resp.responseText);

				/* in case no errors returned, */
				if(resp.responseText.indexOf(AppGini.Translate._map['error:']) == -1) {
					/* redirect to provided url */
					if(redirectOnSuccess !== undefined) {
						window.location = redirectOnSuccess;

					/* or hide notification after a few seconds if no url is provided */
					} else {
						if($j('#' + notify).length)
							setTimeout(function() { $j('#' + notify).hide(); }, 15000);
					}

				/* in case of error, apply error class */
				} else {
					$j('#' + notify).addClass('alert-danger');
				}
			},
			onComplete: function() {
				$j('#' + disable).prop('disabled', false);
				$j('#' + loading).hide();
			}
		}
	);
}

function passwordStrength(password, username) {
	// score calculation (out of 10)
	var score = 0;
	re = new RegExp(username, 'i');
	if(username.length && password.match(re)) score -= 5;
	if(password.length < 6) score -= 3;
	else if(password.length > 8) score += 5;
	else score += 3;
	if(password.match(/(.*[0-9].*[0-9].*[0-9])/)) score += 3;
	if(password.match(/(.*[!,@,#,$,%,^,&,*,?,_,~].*[!,@,#,$,%,^,&,*,?,_,~])/)) score += 5;
	if(password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) score += 2;

	if(score >= 9)
		return 'strong';
	else if(score >= 5)
		return 'good';
	else
		return 'weak';
}
function validateEmail(email) { 
	var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return re.test(email);
}
function loadScript(jsUrl, cssUrl, callback) {
	// adding the script tag to the head
	var head = document.getElementsByTagName('head')[0];
	var script = document.createElement('script');
	script.type = 'text/javascript';
	script.src = jsUrl;

	if(cssUrl != '') {
		var css = document.createElement('link');
		css.href = cssUrl;
		css.rel = "stylesheet";
		css.type = "text/css";
		head.appendChild(css);
	}

	// then bind the event to the callback function 
	// there are several events for cross browser compatibility
	if(script.onreadystatechange != undefined) { script.onreadystatechange = callback; }
	if(script.onload != undefined) { script.onload = callback; }

	// fire the loading
	head.appendChild(script);
}
/**
 * options object. The following members can be provided:
 *    url: iframe url to load
 *    message: instead of a url to open, you could pass a message. HTML tags allowed.
 *    id: id attribute of modal window. auto-generated if not provided
 *    title: optional modal window title
 *    size: 'default', 'full'
 *    noAnimation: optional, default is false. Set to true to disable animation effect while modal is launched
 *    show: optional function to execute after showing the modal
 *    close: optional function to execute after closing the modal
 *    footer: optional array of objects describing the buttons to display in the footer.
 *       Each button object can have the following members:
 *          label: string, label of button
 *          bs_class: string, button bootstrap class. Can be 'primary', 'default', 'success', 'warning' or 'danger'
 *          click: function to execute on clicking the button. If the button closes the modal, this
 *                 function is executed before the close handler
 *          causes_closing: boolean, default is true.
 */
function modal_window(options) {
	return jQuery('body').agModal(options).agModal('show').attr('id');
}

function random_string(string_length) {
	var text = "";
	var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

	for(var i = 0; i < string_length; i++)
		text += possible.charAt(Math.floor(Math.random() * possible.length));

	return text;
}

/**
 *  @return array of IDs (PK values) of selected records in TV (records that the user checked)
 */
function get_selected_records_ids() {
	return jQuery('.record_selector:checked').map(function() { return jQuery(this).val() }).get();
}

function print_multiple_dv_tvdv(t, ids) {
	document.myform.NoDV.value=1;
	document.myform.PrintDV.value=1;
	document.myform.SelectedID.value = '';
	document.myform.submit();
	return true;
}

function print_multiple_dv_sdv(t, ids) {
	document.myform.NoDV.value=1;
	document.myform.PrintDV.value=1;
	document.myform.writeAttribute('novalidate', 'novalidate');
	document.myform.submit();
	return true;
}

function mass_delete(t, ids) {
	if(ids == undefined) return;
	if(!ids.length) return;

	var confirm_message = '<div class="alert alert-danger">' +
			'<i class="glyphicon glyphicon-warning-sign"></i> ' + 
			AppGini.Translate._map['<n> records will be deleted. Are you sure you want to do this?'] +
		'</div>';
	var confirm_title = AppGini.Translate._map['Confirm deleting multiple records'];
	var label_yes = AppGini.Translate._map['Yes, delete them!'];
	var label_no = AppGini.Translate._map['No, keep them.'];
	var progress = AppGini.Translate._map['Deleting record <i> of <n>'];
	var continue_delete = true;

	// request confirmation of mass delete operation
	modal_window({
		message: confirm_message.replace(/\<n\>/, ids.length),
		title: confirm_title,
		footer: [ /* shows a 'yes' and a 'no' buttons .. handler for each follows ... */
			{
				label: '<i class="glyphicon glyphicon-trash"></i> ' + label_yes,
				bs_class: 'danger',
				// on confirming, start delete operations
				click: function() {

					// show delete progress, allowing user to abort operations by closing the window or clicking cancel
					var progress_window = modal_window({
						title: AppGini.Translate._map['Delete progress'],
						message: '' +
							'<div class="progress">' +
								'<div class="progress-bar progress-bar-warning" role="progressbar" style="width: 0;"></div>' +
							'</div>' + 
							'<button type="button" class="btn btn-default details_toggle" onclick="' +
								'jQuery(this).children(\'.glyphicon\').toggleClass(\'glyphicon-chevron-right glyphicon-chevron-down\'); ' +
								'jQuery(\'.well.details_list\').toggleClass(\'hidden\');'
								+ '">' +
								'<i class="glyphicon glyphicon-chevron-right"></i> ' +
								AppGini.Translate._map['Show/hide details'] +
							'</button>' +
							'<div class="well well-sm details_list hidden"><ol></ol></div>',
						close: function() {
							// stop deleting further records ...
							continue_delete = false;
						},
						footer: [
							{
								label: '<i class="glyphicon glyphicon-remove"></i> ' + AppGini.Translate._map['Cancel'],
								bs_class: 'warning'
							}
						]
					});

					// begin deleting records, one by one
					progress = progress.replace(/\<n\>/, ids.length);
					var delete_record = function(itrn) {
						if(!continue_delete) return;
						jQuery.ajax(t + '_view.php', {
							type: 'POST',
							data: { delete_x: 1, SelectedID: ids[itrn], csrf_token: $j('#csrf_token').val() },
							success: function(resp) {
								if(resp != 'OK') {
									jQuery('<li class="text-danger">' + resp + '</li>').appendTo('.well.details_list ol');
									return;
								}

								jQuery('<li class="text-success">' + AppGini.Translate._map['The record has been deleted successfully'] + '</li>')
									.appendTo('.well.details_list ol');
								jQuery('#record_selector_' + ids[itrn])
									.prop('checked', false).parent().parent().fadeOut(1500);
								jQuery('#select_all_records').prop('checked', false);

								// decrement record count
								let recCount = $j('.record-count').text().replace(/\D/g, ''),
									lastRec =  $j('.last-record').text().replace(/\D/g, '');

								$j('.record-count').text(parseInt(recCount) - 1);
								$j('.last-record').text(parseInt(lastRec) - 1);
							},
							error: function() {
								jQuery('<li class="text-warning">' + AppGini.Translate._map['Connection error'] + '</li>')
									.appendTo('.well.details_list ol');
							},
							complete: function() {
								jQuery('#' + progress_window + ' .progress-bar')
									.attr('style', 'width: ' + (Math.round((itrn + 1) / ids.length * 100)) + '%;')
									.html(progress.replace(/\<i\>/, (itrn + 1)));

								if(itrn < (ids.length - 1)) {
									delete_record(itrn + 1);
									return;
								}

								if(jQuery('.well.details_list li.text-danger, .well.details_list li.text-warning').length) {
									jQuery('button.details_toggle')
										.removeClass('btn-default').addClass('btn-warning')
										.click();
									jQuery('.btn-warning[id^=' + progress_window + '_footer_button_]')
										.toggleClass('btn-warning btn-default')
										.html(AppGini.Translate._map['ok']);
									return;
								}

								setTimeout(function() { jQuery('#' + progress_window).agModal('hide'); }, 500);
							}
						});
					}

					delete_record(0);
				}
			},
			{
				label: '<i class="glyphicon glyphicon-ok"></i> ' + label_no,
				bs_class: 'success' 
			}
		]
	});
}

function mass_change_owner(t, ids) {
	if(ids == undefined) return;
	if(!ids.length) return;

	var update_form = AppGini.Translate._map['Change owner of <n> selected records to'] + 
		'<span id="new_owner_for_selected_records"></span>' +
		'<input type="hidden" name="new_owner_for_selected_records" value="">';
	var confirm_title = AppGini.Translate._map['Change owner'];
	var label_yes = AppGini.Translate._map['Continue'];
	var label_no = AppGini.Translate._map['Cancel'];
	var progress = AppGini.Translate._map['Updating record <i> of <n>'];
	var continue_updating = true;

	// request confirmation of mass update operation
	modal_window({
		message: update_form.replace('<n>', ids.length),
		title: confirm_title,
		footer: [ /* shows a 'continue' and a 'cancel' buttons .. handler for each follows ... */
			{
				label: '<i class="glyphicon glyphicon-ok"></i> ' + label_yes,
				bs_class: 'success',
				// on confirming, start update operations
				click: function() {
					var memberID = jQuery('input[name=new_owner_for_selected_records]').eq(0).val();
					if(!memberID.length) return;

					// show update progress, allowing user to abort operations by closing the window or clicking cancel
					var progress_window = modal_window({
						title: AppGini.Translate._map['Update progress'],
						message: '' +
							'<div class="progress">' +
								'<div class="progress-bar progress-bar-success" role="progressbar" style="width: 0;"></div>' +
							'</div>' + 
							'<button type="button" class="btn btn-default details_toggle" onclick="' +
								'jQuery(this).children(\'.glyphicon\').toggleClass(\'glyphicon-chevron-right glyphicon-chevron-down\'); ' +
								'jQuery(\'.well.details_list\').toggleClass(\'hidden\');'
								+ '">' +
								'<i class="glyphicon glyphicon-chevron-right"></i> ' +
								AppGini.Translate._map['Show/hide details'] +
							'</button>' +
							'<div class="well well-sm details_list hidden"><ol></ol></div>',
						close: function() {
							// stop updating further records ...
							continue_updating = false;
						},
						footer: [
							{
								label: '<i class="glyphicon glyphicon-remove"></i> ' + AppGini.Translate._map['Cancel'],
								bs_class: 'warning'
							}
						]
					});

					// begin updating records, one by one
					progress = progress.replace('<n>', ids.length);
					var update_record = function(itrn) {
						if(!continue_updating) return;
						jQuery.ajax('admin/pageEditOwnership.php', {
							type: 'POST',
							data: {
								pkValue: ids[itrn],
								t: t,
								memberID: memberID,
								saveChanges: 1,
								csrf_token: $j('#csrf_token').val()
							},
							success: function(resp) {
								if(resp != 'OK') {
									jQuery(".well.details_list ol").append('<li class="text-danger">' + resp + '</li>');
									return;
								}

								jQuery('<li class="text-success">' + AppGini.Translate._map['record updated'] + '</li>')
									.appendTo(".well.details_list ol");
								jQuery('#select_all_records, #record_selector_' + ids[itrn]).prop('checked', false);
							},
							error: function() {
								jQuery('<li class="text-warning">' + AppGini.Translate._map['Connection error'] + '</li>')
									.appendTo(".well.details_list ol");
							},
							complete: function() {
								jQuery('#' + progress_window + ' .progress-bar')
									.attr('style', 'width: ' + (Math.round((itrn + 1) / ids.length * 100)) + '%;')
									.html(progress.replace(/\<i\>/, (itrn + 1)));

								if(itrn < (ids.length - 1)) {
									update_record(itrn + 1);
									return;
								}

								if(jQuery('.well.details_list li.text-danger, .well.details_list li.text-warning').length) {
									jQuery('button.details_toggle')
										.removeClass('btn-default').addClass('btn-warning')
										.click();
									jQuery('.btn-warning[id^=' + progress_window + '_footer_button_]')
										.toggleClass('btn-warning btn-default')
										.html(AppGini.Translate._map['ok']);
									return;
								}

								jQuery('button.btn-warning[id^=' + progress_window + '_footer_button_]')
									.toggleClass('btn-warning btn-success')
									.html('<i class="glyphicon glyphicon-ok"></i> ' + AppGini.Translate._map['ok']);
							}
						});
					}

					update_record(0);
				}
			},
			{
				label: '<i class="glyphicon glyphicon-remove"></i> ' + label_no,
				bs_class: 'warning' 
			}
		]
	});

	/* show drop down of users */
	var populate_new_owner_dropdown = function() {

		jQuery('[id=new_owner_for_selected_records]').select2({
			width: '100%',
			formatNoMatches: function(term) { return AppGini.Translate._map['No matches found!']; },
			minimumResultsForSearch: 5,
			loadMorePadding: 200,
			escapeMarkup: function(m) { return m; },
			ajax: {
				url: 'admin/getUsers.php',
				dataType: 'json',
				cache: true,
				data: function(term, page) { return { s: term, p: page, t: t }; },
				results: function(resp, page) { return resp; }
			}
		}).on('change', function(e) {
			jQuery('[name="new_owner_for_selected_records"]').val(e.added.id);
		});

	}

	populate_new_owner_dropdown();
}

function add_more_actions_link() {
	window.open('https://bigprof.com/appgini/help/advanced-topics/hooks/multiple-record-batch-actions?r=appgini-action-menu');
}

/* detect current screen size (xs, sm, md or lg) */
function screen_size(sz) {
	if(!$j('.device-xs').length) {
		$j('body').append(
			'<div class="device-xs visible-xs"></div>' +
			'<div class="device-sm visible-sm"></div>' +
			'<div class="device-md visible-md"></div>' +
			'<div class="device-lg visible-lg"></div>'
		);
	}
	return $j('.device-' + sz).is(':visible');
}

/* enable floating of action buttons in DV so they are visible on vertical scrolling */
function enable_dvab_floating() {
	/* already run? */
	if(window.enable_dvab_floating_run != undefined) return;

	/* scroll action buttons of DV on scrolling DV */
	$j(window).scroll(function() {
		if(!$j('.detail_view').length) return;
		if(!screen_size('md') && !screen_size('lg')) {
			$j('.detail_view .btn-toolbar').css({ 'margin-top': 0 });
			return;
		}

		/* get vscroll amount, DV form height, button toolbar height and position */
		var vscroll = $j(window).scrollTop();
		var dv_height = $j('[id$="_dv_form"]').eq(0).height();
		var bt_height = $j('.detail_view .btn-toolbar').height();
		var form_top = $j('.detail_view .form-group').eq(0).offset().top;
		var bt_top_max = dv_height - bt_height - 10;

		if(vscroll > form_top) {
			var tm = parseInt(vscroll - form_top) + 60;
			if(tm > bt_top_max) tm = bt_top_max;

			$j('.detail_view .btn-toolbar').css({ 'margin-top': tm + 'px' });
		} else {
			$j('.detail_view .btn-toolbar').css({ 'margin-top': 0 });
		}
	});
	window.enable_dvab_floating_run = true;
}

/* check if a given field's value is unique and reflect this in the DV form */
function enforce_uniqueness(table, field) {
	$j('#' + field).on('change', function() {
		/* check uniqueness of field */
		var data = {
			t: table,
			f: field,
			value: $j('#' + field).val()
		};

		if($j('[name=SelectedID]').val().length) data.id = $j('[name=SelectedID]').val();

		$j.ajax({
			url: 'ajax_check_unique.php',
			data: data,
			complete: function(resp) {
				if(resp.responseJSON.result == 'ok') {
					$j('#' + field + '-uniqueness-note').hide();
					$j('#' + field).parents('.form-group').removeClass('has-error');
				} else {
					$j('#' + field + '-uniqueness-note').show();
					$j('#' + field).parents('.form-group').addClass('has-error');
					$j('#' + field).focus();
					setTimeout(function() { $j('#update, #insert').prop('disabled', true); }, 500);
				}
			}
		})
	});
}

/* persist expanded/collapsed chidren in DVP */
function persist_expanded_child(id) {
	var expand_these = JSON.parse(localStorage.getItem('rental_property_manager.dvp_expand'));
	if(expand_these == undefined) expand_these = [];

	if($j('[id=' + id + ']').hasClass('active')) {
		if(expand_these.indexOf(id) < 0) {
			// expanded button and not persisting in cookie? save it!
			expand_these.push(id);
			localStorage.setItem('rental_property_manager.dvp_expand', JSON.stringify(expand_these));
		}
	} else {
		if(expand_these.indexOf(id) >= 0) {
			// collapsed button and persisting in cookie? remove it!
			expand_these.splice(expand_these.indexOf(id), 1);
			localStorage.setItem('rental_property_manager.dvp_expand', JSON.stringify(expand_these));
		}
	}
}

/* apply expanded/collapsed status to children in DVP */
function apply_persisting_children() {
	var expand_these = JSON.parse(localStorage.getItem('rental_property_manager.dvp_expand'));
	if(expand_these == undefined) return;

	expand_these.each(function(id) {
		$j('[id=' + id + ']:not(.active)').click();
	});
}

function select2_max_width_decrement() {
	return ($j('div.container, div.container-fluid').eq(0).hasClass('theme-compact') ? 99 : 109);
}

/**
 *  @brief AppGini.TVScroll().more() to scroll one column more. 
 *         AppGini.TVScroll().less() to scroll one column less.
 */
AppGini.TVScroll = function() {

	/**
	 *  @brief Calculates the width of the first n columns of the TV table
	 *  
	 *  @param [in] n how many columns to calculate the width for
	 *  @return Return total width of given n columns, or 0 if n < 1 or invalid
	 */
	var _TVColsWidth = function(n) {
		if(isNaN(n)) return 0;
		if(n < 1) return 0;

		var tw = 0, cc;
		for(var i = 0; i < n; i++) {
			cc = $j('.table_view .table th:visible').eq(i);
			if(!cc.length) break;
			tw += cc.outerWidth();
		}

		return tw;
	};

	/**
	 *  @brief show/hide tv-scroll buttons based on whether TV is horizontally scrollable or not
	 *  @details should be called once on document load before hiding TV columns (by calling less())
	 */
	var toggle_tv_scroll_tools = function() {
		var tr = $j('.table_view .table-responsive'),
			vpw = tr.width(), // viewport width
			tfw = tr.find('.table').width(); // full width of the table

		if(vpw >= tfw) $j('.tv-scroll').parents('.btn-group').hide();
		else $j('.tv-scroll').parents('.btn-group').show();
	}

	/**
	 *  @brief Prepares variables for use by less & more
	 */
	var _TVScrollSetup = function() {
		if(AppGini._TVColsScrolled === undefined) AppGini._TVColsScrolled = 0;
		AppGini._TVColsCount = $j('.table_view .table th:visible').length;

		/* type of scrolling, https://github.com/othree/jquery.rtl-scroll-type */
		/*
			How to interpret AppGini._ScrollType?
			{LTR | RTL}:{scrollLeft val for left position}:{scrollLeft val for right position}:{initial scrollLeft val}
		*/
		if(AppGini._ScrollType === undefined) {
			/* all browsers behave the same on LTR */
			AppGini._ScrollType = 'LTR:0:100:0';

			if($j('.container').hasClass('theme-rtl') || $j('.container-fluid').hasClass('theme-rtl')) {
				var definer = $j('<div dir="rtl" style="font-size: 14px; width: 4px; height: 1px; position: absolute; top: -1000px; overflow: scroll">ABCD</div>').appendTo('body')[0];

				AppGini._ScrollType = 'RTL:100:0:0'; // IE
				if(definer.scrollLeft > 0) {
					AppGini._ScrollType = 'RTL:0:100:70'; // WebKit
				} else {
					definer.scrollLeft = 1;
					if(definer.scrollLeft === 0) AppGini._ScrollType = 'RTL:-100:0:0'; // Firefox/Opera
				}
			}

			/* show/hide #tv-scroll buttons based on TV scroll state */
			$j(window).resize(toggle_tv_scroll_tools);
			toggle_tv_scroll_tools();
		}  
	};

	/**
	 *  @brief Resets all scrolling and setup values.
	 *  @details Useful after hiding/showing columns to re-setup TV scrolling
	 */
	var reset = function() {
		if(AppGini._ScrollType === undefined) return; // nothing to reset!
		AppGini._TVColsScrolled = undefined;

		var tr = $j('.table_view .table-responsive');
		switch(AppGini._ScrollType) {
			case 'RTL:100:0:0':
			case 'RTL:0:100:0':
			case 'RTL:-100:0:0':
				tr.scrollLeft(0);
				break;
			case 'RTL:0:100:70':
				var vpw = tr.width(), // viewport width
					tfw = tr.find('.table').width(); // full width of the table
				tr.scrollLeft(tfw - vpw + 10);
				break;
		}

		_TVScrollSetup();
	};

	var _TVScroll = function() {
		var scroll = 0,
			tr = $j('.table_view .table-responsive'),
			cw = _TVColsWidth(AppGini._TVColsScrolled); // width of columns to scroll to

		switch(AppGini._ScrollType) {
			case 'RTL:100:0:0':
			case 'LTR:0:100:0':
				scroll = cw - 1;
				break;
			case 'RTL:-100:0:0':
				scroll = -1 * cw + 1;
				break;
			case 'RTL:0:100:70':
				var vpw = tr.width(), // viewport width
					tfw = tr.find('.table').width(); // full width of the table
				scroll = tfw - vpw - cw + 1;
				break;
		}

		tr.scrollLeft(scroll);
	};

	/**
	 *  @brief Scroll the TV table 1 column more
	 */
	var more = function() {
		if(AppGini._TVColsScrolled >= AppGini._TVColsCount) return;
		AppGini._TVColsScrolled++;
		_TVScroll();
	};

	/**
	 *  @brief Scroll the TV table 1 column less
	 */
	var less = function() {
		if(AppGini._TVColsScrolled <= 0) return;
		AppGini._TVColsScrolled--;
		_TVScroll();
	};

	_TVScrollSetup();

	return { more: more, less: less, reset: reset };

};

(function($j) {
	/*
		apply a modal or an in-page modal to an element,
		or access modal methods/events if it's already 'modal'ed

		Expected usage:
		1. $j('any_selector').agModal({ new modal options .. })
		2. $j('#modal_id').agModal('command')
		3. $j('#modal_id').on('event.bs.modal', event_handler)

		case 1: the selector doesn't matter ... the modal will be created and attached
				to the body element .. to retrieve the modal id if not specified in options:
				var modal_id = $j('any_selector').agModal({ new modal options .. }).attr('id');

		case 2: the selector must be the modal element .. if it's a standard BS modal,
				command will be passed as is to .modal() and the return value returned.
				if it's an in-page modal, command will be emulated and the modal element
				returned.

		case 3: Bootstrap modal events.
	*/
	$j.fn.agModal = function(options) {
		var theModal = this,
		open = function() {
			return theModal.trigger('show.bs.modal').removeClass('hide').trigger('shown.bs.modal');
		},
		close = function() {
			return theModal.trigger('hide.bs.modal').addClass('hide').trigger('hidden.bs.modal');
		};

		if(typeof(options) == 'string') {
			if(theModal.hasClass('modal')) return theModal.modal(options);
			if(!theModal.hasClass('inpage-modal')) return theModal;

			/* emulate .modal(command) for the in-page modal */
			switch(options) {
				case 'show':
					open();
					break;
				case 'hide':
					close();
					break;
			}

			return theModal;
		}

		var op = $j.extend({
			/* default options */
			id: random_string(20),
			footer: [],
			extras: {},
			size: 'default',
			forceIPM: false
		}, options);

		if(op.url == undefined && op.message == undefined) {
			console.error('Missing message/url in call to AppGini.modal().');
			return theModal;
		}

		var iOS = /(iPad|iPhone|iPod)/g.test(navigator.userAgent), /* true for iOS devices */
		auto_id = (options.id === undefined), /* true if modal id is auto-generated */

		_resize = function(id) {
			var mod = $j('#' + id);
			if(!mod.length) return;

			var ipm = (mod.hasClass('inpage-modal') ? '.inpage-modal-' : '.modal-');

			var wh = $j(window).height(),
				mtm = mod.find(ipm + 'dialog').css('margin-top'),
				mhfoh = mod.find(ipm + 'header').outerHeight();

			if(mod.find(ipm + 'footer').length) mhfoh += mod.find(ipm + 'footer').outerHeight();

			mod.find(ipm + 'dialog').css({
				margin: mtm,
				width: 'calc(100% - 2 * ' + mtm + ')'
			});

			mod.find(ipm + 'body').css({
				height: 'calc(' + wh + 'px - ' + mhfoh + 'px - 2 * ' + mtm + ' - 6px)'
			});
		},

		_bsModal = function() {
			/* build the html of footer buttons into footer_buttons variable */
			var footer_buttons = '';
			for(i = 0; i < op.footer.length; i++) {
				if(typeof(op.footer[i].label) != 'string') continue;

				op.footer[i] = $j.extend(
					/* defaults */
					{
						causes_closing: true,
						bs_class: 'default'
					},
					op.footer[i],
					/* enforce the following values */
					{ id: op.id + '_footer_button_' + random_string(10) }
				);

				footer_buttons += '<button ' +
						'type="button" ' +
						'class="btn btn-' + op.footer[i].bs_class + '" ' +
						(op.footer[i].causes_closing ? 'data-dismiss="modal" ' : '') +
						'id="' + op.footer[i].id + '" ' +
						'>' + op.footer[i].label +
					'</button>';
			}

			var mod = $j(
				'<div class="modal ' + (op.noAnimation ? '' : 'fade') + '" tabindex="-1" role="dialog" id="' + op.id + '">' +
					'<div class="modal-dialog" role="document">' +
						'<div class="modal-content">' +
							( op.title != undefined ?
								'<div class="modal-header">' +
									'<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
									'<h4 class="modal-title" style="width: 90%;">' + op.title + '</h4>' +
								'</div>'
								: ''
							) +
							'<div class="modal-body">' +
								( op.url != undefined ?
									'<iframe ' +
										'width="100%" height="100%" ' +
										'style="display: block; overflow: scroll !important; -webkit-overflow-scrolling: touch !important;" ' +
										'sandbox="allow-modals allow-forms allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-downloads" ' +
										'src="' + op.url + '">' +
									'</iframe>'
									: op.message
								) +
							'</div>' +
							'<div class="modal-footer">' + footer_buttons + '</div>' +
						'</div>' +
					'</div>' + 
				'</div>'
			);

			if(op.url != undefined) {
				mod.find('.modal-body').css('padding', '0');
			}

			return mod;
		},

		_ipModal = function() {
			/* prepare footer buttons, if any */
			var footer_buttons = '', closer_class = '';
			for(i = 0; i < op.footer.length; i++) {
				if(typeof(op.footer[i].label) != 'string') continue;

				if(op.footer[i].causes_closing !== false) { op.footer[i].causes_closing = true; }
				op.footer[i].bs_class = op.footer[i].bs_class || 'default';
				op.footer[i].id = op.id + '_footer_button_' + random_string(10);           

				closer_class = (op.footer[i].causes_closing ? ' closes-inpage-modal' : '');

				footer_buttons += '<button type="button" ' +
						'class="hspacer-lg vspacer-lg btn btn-' + op.footer[i].bs_class + closer_class + '" ' +
						'id="' + op.footer[i].id + '" ' +
						'>' + op.footer[i].label + '</button>';
			}

			var imc = $j(
				'<div id="' + op.id + '" ' +
					'class="inpage-modal hide ' + $j('.container, .container-fluid').eq(0).attr('class') + '" ' + 
					'style="' +
						'padding-left: 0; padding-right: 0;' +
						'width: 100% !important;' +
					'">' +
					'<div ' +
						'class="inpage-modal-dialog" ' +
						'style="' +
							'box-shadow: 0 0 61px 15px #666;' +
							'margin: 10px !important;' +
							'border: solid 1px;' +
							'border-radius: 5px;' +
						'">' +
						'<div class="inpage-modal-content">' +
							( op.title != undefined ?
								'<div class="inpage-modal-header" style="border-bottom: solid 1px;">' +
									'<div class="row" style="margin: 0;">' + 
										'<div class="col-xs-10 col-sm-11 inpage-modal-title">' +
											'<div class="h4">' + op.title + '</div>' +
										'</div>' +
										'<div class="col-xs-2 col-sm-1 closes-inpage-modal text-center inpage-modal-dismiss" style="cursor: pointer;">' +
											'<h4 class="glyphicon glyphicon-remove"></h4>' +
										'</div>' +
									'</div>' +
								'</div>'
								: ''
							) +

							( footer_buttons.length ?
								'<div class="inpage-modal-footer text-right flip" style="border-bottom: solid 1px;">' + footer_buttons + '</div>'
								: ''
							) +

							'<div class="inpage-modal-body">' +
								( op.url != undefined ?
									'<iframe ' +
										'width="100%" height="100%" ' +
										'style="display: block; overflow: scroll !important; -webkit-overflow-scrolling: touch !important;" ' +
										'sandbox="allow-modals allow-forms allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-downloads" ' +
										'src="' + op.url + '">' +
									'</iframe>'
									: op.message
								) +
							'</div>' +
						'</div>' +
					'</div>' +
				'</div>'
			);

			/* hover effect for dismiss button + close modal if a closer clicked */
			imc.on('mouseover', '.inpage-modal-dismiss', function() {
				$j(this).addClass('text-danger bg-danger');
			}).on('mouseout', '.inpage-modal-dismiss', function() {
				$j(this).removeClass('text-danger bg-danger');
			}).on('click', '.closes-inpage-modal', close);

			imc.find('.inpage-modal-title').css({
				overflow: 'auto',
				'white-space': 'nowrap'
			});

			return imc;
		};

		/* if modal exists, remove it first */
		$j('#' + op.id).remove();

		theModal = ((iOS || op.forceIPM) && op.size == 'full' ? _ipModal() : _bsModal());

		theModal.appendTo('body');

		/* bind footer buttons click handlers */
		for(i = 0; i < op.footer.length; i++) {
			if(typeof(op.footer[i].click) == 'function') {
				$j('#' + op.footer[i].id).click(op.footer[i].click);
			}
		}

		theModal
		.on('show.bs.modal', function() {
			if(op.size != 'full') return;

			/* hide main page to avoid all scrolling/panning hell on touch screens! */
			$j('.container, .container-fluid').eq(0).hide();
		})
		.on('shown.bs.modal', function() {
			if(op.size != 'full') return;

			var id = op.id, rsz = _resize;
			rsz(id);
			$j(window).resize(function() { rsz(id); });

			if(typeof(op.show) == 'function') {
				op.show();
			}
		})
		//.agModal('show')
		.on('hidden.bs.modal', function() {
			/* display main page again */
			if(op.size == 'full') $j('.container, .container-fluid').eq(0).show();

			if(typeof(op.close) == 'function') {
				op.close();
			}

			if(!auto_id) return;

			/* if id is automatic, remove modal after 1 minute from DOM */
			var id = op.id;
			var auto_remove = setInterval(function() {
				if($j('#' + id).is(':visible')) return; // don't remove if visible
				$j('#' + id).remove();
				clearInterval(auto_remove);
			}, 60000);
		});

		return theModal;
	};
})(jQuery);

/**
 *  @brief Used in pages loaded inside modals (e.g. those with Embedded=1) to close the containing modal.
 */
AppGini.closeParentModal = function() {
	var pm = window.parent.jQuery(".modal:visible");
	if(!pm.length) {
		pm = window.parent.jQuery(".inpage-modal:visible");
	}

	if(pm.length) pm.agModal('hide');
	return;
}

/**
 *  @return boolean indicating whether a modal is currently open or not
 */
AppGini.modalOpen = function() { /* */
	return jQuery('.modal-dialog:visible').length > 0 || jQuery('.inpage-modal-dialog:visible').length > 0;
};


/**
 *  @return true for mobile devices, false otherwise
 *  @details https://stackoverflow.com/a/11381730/1945185
 */
AppGini.mobileDevice = function() { /* */
	var check = false;
	(function(a) {if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))) check = true;})(navigator.userAgent||navigator.vendor||window.opera);
	return check;
};

AppGini.datetimeFormat = function(datetime) { /* */
	if(undefined == datetime) datetime = 'd';

	var dateFormat = 'MM/DD/YYYY';
	var timeFormat = 'hh:mm:ss A';

	if(datetime.match(/(dt|td)/i)) return dateFormat + ' ' + timeFormat;
	if(datetime.match(/t/i)) return timeFormat;
	return dateFormat;
};

AppGini.hideViewParentLinks = function() {
	/* find and hide parent links if field label has data 'parent_link' set to 'view_parent_hidden' */
	$j('label[data-parent_link=view_parent_hidden]').each(function() {
		$j(this).parents('.form-group').find('.view_parent').hide();
	});
};

AppGini.filterURIComponents = function(filterIndex, andOr, fieldIndex, operator, value) {
	filterIndex = parseInt(filterIndex); if(isNaN(filterIndex)) return '';
	if(filterIndex < 1 || filterIndex > 60) return '';

	andOr = andOr.toLowerCase();
	if(andOr != 'or') andOr = 'and';

	fieldIndex = parseInt(fieldIndex); if(isNaN(fieldIndex)) return '';
	if(fieldIndex < 1 || fieldIndex > 1000) return '';

	if(![
		'equal-to',
		'not-equal-to',
		'greater-than',
		'greater-than-or-equal-to',
		'less-than',
		'less-than-or-equal-to',
		'like',
		'not-like',
		'is-empty',
		'is-not-empty'
	].indexOf(operator)) operator = 'like';

	if(undefined == value) value = '';

	return '' +
		encodeURIComponent('FilterAnd[' + filterIndex + ']') + '=' + andOr + '&' +
		encodeURIComponent('FilterField[' + filterIndex + ']') + '=' + fieldIndex + '&' +
		encodeURIComponent('FilterOperator[' + filterIndex + ']') + '=' + operator + '&' +
		encodeURIComponent('FilterValue[' + filterIndex + ']') + '=' + encodeURIComponent(value);
}

/*
	retrieve the lookup text for given id by querying ajax_combo.php
	options: { id, table, field, callback }
	callback is called on success, passing { id, text }
*/
AppGini.lookupText = function(options) {
	if(undefined == options) return 'options?';
	if(undefined == options.id) return 'options.id?';
	if(undefined == options.table) return 'options.table?';
	if(undefined == options.field) return 'options.field?';
	if(undefined == options.callback) return 'options.callback?';
	if(typeof(options.callback) != 'function') return 'options.callback!';

	$j.ajax({
		url: 'ajax_combo.php',
		dataType: 'json',
		cache: true,
		data: { id: options.id, t: options.table, f: options.field },
		success: function(resp) {
			options.callback(resp.results[0]);
		}
	});

	return true;
}

AppGini.currentTableName = function() {
	// retrieve current table name from page URL
	var tables = location.href.match(/\/([a-zA-Z0-9_]+)_view\.php/);
	if(undefined == tables || undefined == tables.length || undefined == tables[1]) {
		console.warn('AppGini.currentTableName: Could not retrieve table name from page URL');
		return false;
	}

	return tables[1];
}

AppGini.displayedChildTableNames = function() {
	return $j('.detail_view [data-tablename]').map(function() {
		return $j(this).data('tablename');
	}).get();
}

AppGini.calculatedFields = {
	// The delay in msec between each server-side request to update calculated fields
	updateRequestsDelay: 500,

	_tablesWithoutCalculations: [], // would be populated with table names returning no calucated fields error

	init: function() {
		var table = AppGini.currentTableName();
		if(!table) return false;

		// this CSS class must be present in pages to trigger calculations
		if(!$j('.has-calculated-fields').length) return false;

		// init TV update of calculated fields
		AppGini.calculatedFields.updateServerSide(
			table, 
			// array of record IDs
			$j('.table_view tr[data-id]').map(function() {
				return $j(this).data('id');
			}).get()
		);

		// init child tabs update of calculated fields
		var childTables = AppGini.displayedChildTableNames();
		for(var cti = 0; cti < childTables.length; cti++) {
			var tn = childTables[cti];
			AppGini.calculatedFields.updateServerSide(
				tn, 
				$j('[id^="panel_' + tn + '-"] tr[data-id]').map(function() {
					return $j(this).data('id');
				}).get()
			);
		}

		// if there are child tables, no need to update DV as the above call should already do so
		if(childTables.length) return;

		// init DV update of calculated fields
		var selectedId = $j('input[name=SelectedID]').val();
		if(undefined != selectedId)
			AppGini.calculatedFields.updateServerSide(table, selectedId);
	},

	updateServerSide: function(table, id) {
		if(undefined === table || undefined === id || !table.length || !id.length) return;
		if(AppGini.calculatedFields._tablesWithoutCalculations.indexOf(table) >= 0) return;

		$j.ajax({
			url: 'ajax-update-calculated-fields.php',
			data: { table: table, id: id },
			type: 'POST',
			success: function(resp) {
				if(resp.data == undefined || resp.error == undefined) return;

				if(resp.error.length) {
					if(!resp.error.match(/no fields to calculate/i)) return;
					if(resp.data.table == undefined) return;

					if(AppGini.calculatedFields._tablesWithoutCalculations.indexOf(resp.data.table) < 0)
						AppGini.calculatedFields._tablesWithoutCalculations.push(resp.data.table);
					return;
				}

				AppGini.calculatedFields.updateClientSide(resp.data);
			},
			error: function() {
				// retry later, in 20 seconds ...
				setTimeout(function() {
					AppGini.calculatedFields.updateServerSide(table, id);
				}, 20000);
			}
		});
	},

	updateClientSide: function(data) {
		if(data.length != undefined) {
			for(var i = 0; i < data.length; i++)
				AppGini.calculatedFields.updateClientSide(data[i]);
			return;
		}

		if(data.table == undefined) return;
		if(data.field == undefined) return;
		if(data.id == undefined) return;
		if(data.value === undefined) return;
		if(data.value === null) data.value = '';

		// update calc fields in TV/TVP/children
		var safeId = data.id.replace(/"/, '\\"');
		var cell = $j('[id="' + data.table + '-' + data.field + '-' + safeId + '"]');
		var cellLink = cell.find('a');
		if(cellLink.length)
			cellLink.html(data.value);
		else
			cell.html(data.value);

		// update calc field in DV/DVP
		var detailViewForm = $j('.table-' + data.table + '.detail_view').parents('form');
		// make sure that data.id matches the hidden SelectedID var in the form
		if(data.id != detailViewForm.find('input[name="SelectedID"]').val()) return;

		var inpElem = detailViewForm.find('[id="' + data.field + '"]');
		if(inpElem.attr('value') !== undefined)
			inpElem.val(data.value);
		else
			inpElem.html(data.value);
	}
};

AppGini.checkFileUpload = function(fieldName, extensions, maxSize) {
	// if File interface is not supported, return with no further checks
	var files = $j('#' + fieldName)[0].files,
		formGroup = $j('#' + fieldName).parents('.form-group'),
		fileTypeError = formGroup.find('.file-type-error'),
		fileSizeError = formGroup.find('.file-size-error'),
		clearUpload = formGroup.find('.clear-upload');

	if(undefined === files) return true;

	// clear errors before checking
	formGroup.removeClass('has-error');
	fileTypeError.addClass('hidden');
	fileSizeError.addClass('hidden');

	// no files to check?
	if(!files.length) {
		clearUpload.addClass('hidden');
		return true;
	}
	clearUpload.removeClass('hidden');

	// if File interface doesn't support features we're using here, return
	if(undefined === files[0].name) return true;
	if(undefined === files[0].size) return true;

	// file ext check
	if(files[0].name.match(new RegExp('\.(' + extensions + ')$', 'i')) === null) {
		// show file type error
		formGroup.addClass('has-error');
		fileTypeError.removeClass('hidden');
		//toolbox.addClass('label-danger').removeClass('label-success');

		// update error message to show allowed file types
		fileTypeError.html(
			fileTypeError
				.html()
				.replace(/[<{]filetypes[}>]/i, extensions.replace(/\|/g, ', '))
		);

		return false;
	}

	// hide file type error
	fileTypeError.addClass('hidden');

	// file size check
	if(maxSize > 0 && files[0].size > maxSize) {
		// show file size error
		formGroup.addClass('has-error');
		fileSizeError.removeClass('hidden');
		//toolbox.addClass('label-danger').removeClass('label-success');

		// update error message to show max file size
		fileSizeError.html(
			fileSizeError
				.html()
				.replace(/[<{]maxsize[}>]/i, Math.round(maxSize / 1024))
		);

		return false;
	}

	// hide file size error as well as form group error
	fileSizeError.addClass('hidden');

	formGroup.removeClass('has-error');
	//toolbox.removeClass('label-danger').addClass('label-success');

	return true;
}

AppGini.previewUploadedImage = (id) => {
	const imageInput = $j(`#${id}`);
	const imagePreview = $j(`#${id}-image`);

	// abort if not accept attr doesn't contain image/*
	if(!imageInput.attr('accept').match(/image\/\*/i)) return;

	// store original image path if not already stored
	if(imagePreview.data('original-image') === undefined)
		imagePreview.data('original-image', imagePreview.attr('src'));

	// if no file selected, restore original image and return
	if(imageInput[0].files.length == 0) {
		imagePreview.attr('src', imagePreview.data('original-image'));
		return;
	}

	// read selected file and display it in the preview
	const reader = new FileReader();
	reader.onload = (e) => {
		imagePreview.attr('src', e.target.result);
	}

	reader.readAsDataURL(imageInput[0].files[0]);
}

/* setInterval alternative that repeats an action until a condition is met */
AppGini.repeatUntil = function(config) {
	if(config === undefined) return;
	if(typeof(config.action) != 'function') return;
	if(typeof(config.condition) != 'function') return;
	if(typeof(config.frequency) != 'number') config.frequency = 1000;

	AppGini._repeatUntilIntervals = AppGini._repeatUntilIntervals || {};

	(function(id, action, condition, frequency) {
		AppGini._repeatUntilIntervals[id] = setInterval(function() {
			if(!condition()) {
				action();
			} else {
				clearInterval(AppGini._repeatUntilIntervals[id]);
			}
		}, frequency);
	})(random_string(20), config.action, config.condition, config.frequency);
}

/*
 * setInterval alternative that waits until a condition is met then executes an action once
 * stops trying after timeout (in msec) if > 0
 * if doActionOnTimeout is set to true, the action is executed on timeout
 */
AppGini.once = function(config) {
	if(config === undefined) return;
	if(typeof(config.condition) != 'function') return;
	if(typeof(config.action) != 'function') return;
	if(config.timeout === undefined) config.timeout = 0;
	if(config.doActionOnTimeout === undefined) config.doActionOnTimeout = false;

	AppGini._onceIntervals = AppGini._onceIntervals || {};

	(function(id, action, condition, timeout, doActionOnTimeout) {
		AppGini._onceIntervals[id] = {
			callback: setInterval(function() {
				// timeout configured and passed?
				if(
					timeout > 0 && 
					(AppGini.unixTimestamp().msec - AppGini._onceIntervals[id].started) > timeout
				) {
					clearInterval(AppGini._onceIntervals[id].callback);
					delete AppGini._onceIntervals[id];
					if(doActionOnTimeout) action();
					return;
				}

				if(!condition()) return;

				action();
				clearInterval(AppGini._onceIntervals[id].callback);
				delete AppGini._onceIntervals[id];
			}, 50),
			started: AppGini.unixTimestamp().msec
		};
	})(random_string(20), config.action, config.condition, config.timeout, config.doActionOnTimeout);
}

AppGini.unixTimestamp = function() {
	return {
		msec: parseInt(moment().format('x')),
		sec: parseInt(moment().format('X'))
	};
}

/* function to trigger form change event for contenteditable elements */
AppGini.detectContentEditableChanges = function() {
	AppGini.repeatUntil({
		condition: function() { return $j('.has-input-handler').length > 0;    },
		action: function() {
			$j('[contenteditable="true"]:not(.has-input-handler)')
				.addClass('has-input-handler')
				.on('input', function() {
					$j(this).parents('form').trigger('change');
				});
		},
		frequency: 2000
	});
}

/* function to sort select2 search results by relevence */
AppGini.sortSelect2ByRelevence = function(res, cont, qry) {
	return res.sort(function(a, b) {
		if(qry.term) {
			var aStart = a.text.match(new RegExp("^" + qry.term, "i")) !== null;
			var bStart = b.text.match(new RegExp("^" + qry.term, "i")) !== null;
			if(aStart && !bStart) return false;
			if(!aStart && bStart) return true;
		}
		return a.text > b.text;
	});
}

/* function to replace absolute link in DV with a 'Back' link in case of POST */
AppGini.alterDVTitleLinkToBack = function() {
	// Only if in detail view
	if(!$j('.detail_view').length) return;

	// Only if we have a POST rather than GET request
	if(!$j('[name=SelectedID]').length) return;
	if(document.location.href.match(/[?&]SelectedID=/) !== null) return;

	$j('.page-header > h1 > a').on('click', function(e) {
		e.preventDefault();
		$j('#deselect').trigger('click');
		return false;
	})
}

AppGini.lockUpdatesOnUserRequest = function() {
	// if this is not DV of existing record where editing and saving a copy are both enabled, skip
	if(!$j('#update').length || !$j('#insert').length || !$j('input[name=SelectedID]').val().length) return;

	// if lock behavior already implemented, skip
	if($j('#update').hasClass('locking-enabled')) return;

	$j('#update')
		.addClass('locking-enabled')
		.css({ width: '75%', overflow: 'hidden' })
		.after('<button type="button" class="btn btn-success btn-update-locker"><i class="glyphicon glyphicon-lock"></i></button>')
		.parents('.btn-group-vertical')
			.toggleClass('btn-group-vertical btn-group vspacer-lg')
			.css({ 'margin-left': 0, 'margin-right': 0 })

	$j('.btn-update-locker')
		.css({
			width: '25%',
			overflow: 'hidden',
			'padding-right': 0,
			'padding-left': 0
		})
		.prop('title', AppGini.Translate._map['Disable'])
		.click(function() {
			var locker = $j(this), disable = !$j('#update').prop('disabled');

			$j('#update').prop('disabled', disable).toggleClass('user-locked', disable);

			locker.toggleClass('active');
			locker.prop('title', AppGini.Translate._map[locker.hasClass('active') ? 'Enable' : 'Disable']);
		})
}

/* function to focus a specific element of a form, given field name */
AppGini.focusFormElement = function(tn, fn) {
	if(AppGini.mobileDevice()) return;

	// if we have a visible alert, don't scroll to focused element
	const doScroll = ($j('.alert:visible').length == 0);

	var inputElem = $j([
		'select[id=' + fn + '-mm]',
		'select[id=' + fn + ']',
		'input[type=text][id=' + fn + ']',
		'input[type=file][id=' + fn + ']',
		'input[type=radio][name=' + fn + ']',
		'input[type=checkbox][id=' + fn + ']',
		'textarea[id=' + fn + ']',
		'.' + tn + '-' + fn  + ' .nicEdit-main'    
	].join(',')).not(':disabled').not('.select2-offscreen').filter(':visible').eq(0);

	if(inputElem.length) {
		inputElem.focus();
		if(doScroll) AppGini.scrollToDOMElement(inputElem[0], -100);
		return;
	}

	// maybe the field is a web/email link?
	var linker = $j('[id=' + fn + '-edit-link]');
	if(linker.length) {
		linker.click();
		if(doScroll) AppGini.scrollToDOMElement(linker[0], -100);
		return;
	}

	// perhaps field is a select2?
	var s2 = $j('[id=' + fn + '-container], [id=s2id_' + fn + ']');
	if(s2.length) {
		s2.select2('focus');
		if(doScroll) AppGini.scrollToDOMElement(s2[0], -100);
		return;
	}
}

AppGini.scrollTo = function(id, useName) {
	// https://stackoverflow.com/questions/5007530/how-do-i-scroll-to-an-element-using-javascript/11986374#11986374
	var obj;
	if(useName != undefined && useName)
		obj = $j('[name="' + id + '"]').get(0);
	else
		obj = $j('#' + id).get(0);

	AppGini.scrollToDOMElement(obj);
}

AppGini.scrollToDOMElement = function(obj, shift) {
	var curtop = shift ? shift : 0;

	if(!obj.offsetParent) return;

	do {
		curtop += obj.offsetTop;
	} while (obj = obj.offsetParent);

	window.scroll(0, [curtop - $j(window).height() * .25]);
}

/* object for UI handling of client-side validation */
AppGini.errorField = function(id) {
	$j('#' + id).focus()
		.parents('.form-group').addClass('has-error');
	AppGini.scrollTo(id);
}
AppGini.Validation = {
	fieldRequired: function(type, name, caption) {
		var self = AppGini.Validation;
		if(!self._empty[type](name)) return true;

		var mow = modal_window({
			message: '<div class="alert alert-danger">' + AppGini.Translate._map['field not null'] + '</div>',
			title: AppGini.Translate._map['error:'] + ' ' + caption,
			close: function() {
				self._focusError[type](name);
			},
			footer: [{ label: AppGini.Translate._map['ok'] }]
		});

		// focus OK button for better keyboard usability
		$j('#' + mow).one('shown.bs.modal', function() {
			$j(this).find('.modal-footer .btn').eq(0).focus();
		});

		return false;
	},

	_focusError: {
		lookup: function(id) {
			// to focus a select2, open then instantly close!
			var field = $j('#' + id + '-container');
			field.select2('open');
			field.select2('close');
			field.parents('.form-group').addClass('has-error');
			AppGini.scrollTo(id + '-container');
		},
		list: function(id) {
			// to focus a select2, open then instantly close!
			var field = $j('#' + id);
			field.select2('open');
			field.select2('close');
			field.parents('.form-group').addClass('has-error');
			AppGini.scrollTo(id);
		},
		datetime: function(id) {
			AppGini.scrollTo(id);
			$j('#' + id).focus()
				.next().click()
				.parents('.form-group').addClass('has-error');
		},
		text: AppGini.errorField,
		date: AppGini.errorField,
		image: AppGini.errorField,
		file: AppGini.errorField,
		html: function(id) {
			var htmlEditor = $j('#' + id).parents('.form-group').find('.nicEdit-main');
			AppGini.scrollToDOMElement(htmlEditor.get(0));
			htmlEditor.focus();
		},
		checkbox: AppGini.errorField,
		radio: function(id) {
			$j('[name="' + id + '"]').eq(0).focus()
				.parents('.form-group').addClass('has-error');
			AppGini.scrollTo(id, true);
		}
	},
	_empty: {
		text: function(id) { return $j('#' + id).val() == ''; },
		list: function(id) { return $j('#' + id).select2('val').length == 0; },
		lookup: function(id) { return $j('#' + id + '-container').select2('data').id.length == 0; },
		date: function(id) {
			return (
				$j('#' + id).val() == '' ||
				$j('#' + id + '-mm').val() == '' ||
				$j('#' + id + '-dd').val() == ''
			);
		},
		image: function(id) {
			return (
				$j('#' + id).val() == '' &&
				$j('#' + id + '-image').attr('src').match(/blank\.gif/)
			);
		},
		file: function(id) {
			return $j('#' + id).val() == '' && !$j('#' + id + '-link:visible').length;
		},
		html: function(id) {
			var nic = nicEditors.findEditor(id).getContent().trim();
			return $j(nic).text() == '' && !nic.match(/<img /);
		},
		datetime: function(id) { return $j('#' + id).val() == ''; },
		checkbox: function(id) { return !$j('#' + id).prop('checked'); },
		radio: function(id) { return !$j('[name="' + id + '"]:checked').length; }
	}
}
/* function to execute provided callback, passing provided params, if current view id detail view for a new record */
AppGini.newRecord = function(callback, params) {
	if($j('.detail_view').length && !$j('[name=SelectedID').val().length) {
		callback(params);
	}
}

AppGini.updateKeyboardShortcutsStatus = function() {
	var shortcutsEnabled = JSON.parse(localStorage.getItem('AppGini.shortcutKeysEnabled')) || false;
	var img = $j('nav .help-shortcuts-launcher img');

	img.length ? img.attr('src', img.attr('src').replace(/\/keyboard.*/, '/keyboard' + (shortcutsEnabled ? '' : '-disabled') + '.png')) : null;
}

AppGini.handleKeyboardShortcuts = function() {
	// run only once
	if(AppGini._handleKeyboardShortcutsApplied != undefined) return;

	// https://github.com/bitWolfy/jquery.hotkeys/blob/master/README.md#hotkeys-within-inputs
	$j.hotkeys.options.filterInputAcceptingElements = false;
	$j.hotkeys.options.filterContentEditable = false;
	$j.hotkeys.options.filterTextInputs = false;

	/*
		shortcuts are disabled by default
		to enable them by default:

		if(localStorage.getItem('AppGini.shortcutKeysEnabled') === null)
			localStorage.setItem('AppGini.shortcutKeysEnabled', true)
	*/ 

	// code for enabling/disabling shortcuts
	$j('body').on('click', '.enable-shortcuts, .disable-shortcuts', function() {
		localStorage.setItem('AppGini.shortcutKeysEnabled', $j(this).hasClass('enable-shortcuts'));

		AppGini.updateKeyboardShortcutsStatus();

		// close shortcuts window
		var modal = $j('.inpage-modal:visible, .modal:visible');
		if(modal.length) modal.eq(0).agModal('hide');

		// reopen shortcuts window when old one fully removed
		AppGini.once({
			condition: function() { return !$j('.inpage-modal:visible, .modal:visible').length; },
			action: AppGini.showKeyboardShortcuts
		});
	})

	$j('.help-shortcuts-launcher').on('click', AppGini.showKeyboardShortcuts);

	var kmap = AppGini.shortcutKeyMap, trigger;

	for(var k in kmap) {
		/*
		 kmap[k].trigger: false, 'focus', 'click', 'function'
		   false: don't bind any handler to that hotkey
		   'focus': focus on the selector specified by kmap[k].css
		   'click': click on the selector specified by kmap[k].css
		   'function': invoke the function specified by kmap[k].handler
		 */
		if(!kmap[k].trigger) continue;

		(function(k, conf) {
			$j(document).bind('keydown', k, function(e) {
				// don't handle shortcut if shortcuts disabled, except if F1 pressed
				// >> JSON.parse is necessary because localStorage stores booleans as strings :/
				// >> https://stackoverflow.com/a/3263222/1945185
				if(!JSON.parse(localStorage.getItem('AppGini.shortcutKeysEnabled')) && !/F1$/i.test(k)) return;

				if(conf.trigger == 'function' && typeof(conf.handler) == 'function') {
					conf.handler();
					return;
				}

				// find first element matching selector
				var elm = $j(conf.css).eq(0);
				if(!elm.length) return;

				if(conf.trigger == 'focus') AppGini.scrollToDOMElement(elm.get(0));

				if(conf.trigger == 'click') console.info('Shortcut key activated', { key: k, clickTarget: elm });

				if(elm.hasClass('option_list')) {
					elm.select2(conf.trigger);
					return;
				}

				elm.trigger(conf.trigger);
			})
		})(k, kmap[k]);
	}

	AppGini._handleKeyboardShortcutsApplied = true;
}

AppGini.showKeyboardShortcuts = (e) => {
	if(e) e.preventDefault();
	if(AppGini.modalOpen()) return;

	var kmap = AppGini.shortcutKeyMap, keys = [], $t = AppGini.Translate._map;

	for(var k in kmap) {
		var ckmap = kmap[k];
		if(!$j(ckmap.css).length && ckmap.trigger !== false && ckmap.trigger !== 'function') continue;

		// if ckmap.purpose contains a placeholder <x>, replace with target text
		var purpose = ($t[ckmap.purpose] || '<x>')
			.replace('<x>', $j(ckmap.css).eq(0).text().trim().match(/.{0,30}/)[0]);
		if(!purpose) continue;

		keys.push(
			'<div style="margin-bottom: 1em; white-space: nowrap; overflow: hidden;">' +
				'<kbd>' +
					k
						.replace('ArrowLeft', '&larr;')
						.replace('ArrowRight', '&rarr;')
						.replace('ArrowUp', '&uarr;')
						.replace('ArrowDown', '&darr;')
						.split('/').join('</kbd> ' + $t['or'] + ' <kbd>')
						.split('+').join('</kbd> + <kbd>')
						.split(',').join('</kbd> , <kbd>') +
				'</kbd>' +
				'<span class="hspacer-lg">' + purpose + '</span>' +
			'</div>'
		);
	}

	// button to disable/enable shortcut keys
	// related code in AppGini.handleKeyboardShortcuts
	var shortcutsEnabled = JSON.parse(localStorage.getItem('AppGini.shortcutKeysEnabled')) || false;
	var toggler = '<button type="button" class="hspacer-lg btn btn-xs btn-$color $actionClass"><i class="glyphicon glyphicon-$icon"></i> $label</button>'
					.replace('$color', shortcutsEnabled ? 'default' : 'success')
					.replace('$actionClass', shortcutsEnabled ? 'disable-shortcuts' : 'enable-shortcuts')
					.replace('$icon', shortcutsEnabled ? 'ban-circle' : 'ok')
					.replace('$label', shortcutsEnabled ? $t['Disable'] : $t['Enable'])

	var title = '<img style="$style" src="$src"> <span class="text-$color">$title</span> $toggler'
				.replace('$style', 'height: 1.75em; vertical-align: bottom;')
				.replace('$src', $j('.help-shortcuts-launcher img').attr('src'))
				.replace('$color', shortcutsEnabled ? 'success' : 'danger')
				.replace('$title', shortcutsEnabled ? $t['keyboard shortcuts enabled'] : $t['keyboard shortcuts disabled'])
				.replace('$toggler', toggler)


	var modalId = modal_window({
		message: '<div style="' +
					'margin-top: .75em; ' +
					'overflow: auto; ' +
					'height: inherit; ' +
					'width: inherit; ' +
					'columns: 3 25em; ' +
					'column-gap: 4em; ' +
					'column-rule: 1px dotted #ddd;">' + 
						keys.join('') + 
				'</div>',
		title: title,
		size: 'full',
		noAnimation: true
	});

	/*
	 To hide link to shortcuts reference, add this line in hooks/footer-extras.php
	 <script>_noShortcutsReference = true;</script>
	 */
	if(typeof(_noShortcutsReference) == 'undefined')
		$j(
			'<a href="https://bigprof.com/appgini/help/working-with-generated-web-database-application/shortcut-keys" target="_blank">' +
			AppGini.Translate._map['keyboard shorcuts reference'] +
			'</a>'
		).appendTo('#' + modalId + ' .modal-footer');
}

/* copy .warning colors to a separate class .highlighted-record */
AppGini.defineHighlightClass = function() {
	if(AppGini._defineHighlightClassOk != undefined) return;

	AppGini._defineHighlightClassOk = true;
	$j('<div class="bg-warning defineHighlightClass">.</div>').appendTo('.container, .container-fluid');
	var bgColor = $j('.bg-warning').css('background-color'), textColor = $j('.bg-warning').css('color');
	$j('.defineHighlightClass').remove();
	$j('<style>.highlighted-record { background-color: ' + bgColor + ' !important; color: ' + textColor + ' !important; }</style>')
		.appendTo('.container, .container-fluid');
}

/* return true if a non-empty input has focus */
AppGini.inputHasFocus = function() {
	var inp = ['text', 'email', 'search', 'number', 'tel', 'url'].map(function(typ) {
		return 'input[type="' + typ + '"]:focus';
	}).join(',') + ',textarea:focus,[contenteditable]:focus';

	return $j(inp).length > 0 && ($j(inp).val().length || $j(inp).text().length);
}
AppGini.handleClearingDates = function() {
	// run only once
	if(AppGini._handleClearingDatesApplied != undefined) return;
	AppGini._handleClearingDatesApplied = true;

	$j('.detail_view').on('click', '.fd-date-clearer', function() {
		var dateField = $j(this).data('for');
		if(!dateField) return;

		var dropdowns = '#DF-mm, #DF-dd, #DF'.replace(/DF/g, dateField);
		var oldDate = [0, 1, 2].reduce(function(prev, i) { return prev +  $j(dropdowns).eq(i).val(); }, '');

		$j(dropdowns).val('');
		if(oldDate != '') $j(this).parents('form').trigger('change');
	})
}

AppGini.applyNiceditBgColors = function() {
	$j('.nicedit-bg').each(function() {
		var e = $j(this);
		if(e.hasClass('nicedit-bg-applied')) return;

		e.addClass('nicedit-bg-applied').css({ backgroundColor: 'rgb(' + 
			[
				e.data('nicedit_r'),
				e.data('nicedit_g'),
				e.data('nicedit_b')
			].join(',') +
		')' });
	})
}
/**
 * Adds a link or a divider to the profile menu.
 * Examples:
 *    To append a divider to the menu:
 *       AppGini.addToProfileMenu('--')
 * 
 *    To append a text-only link labeled 'Test link' that opens a page 'test'
 *    in the same tab: 
 *       AppGini.addToProfileMenu({ href: 'test', text: 'Test link' })
 * 
 *    To append a link labeled 'Test link' with an image
 *    that opens a page 'test' in the same tab: 
 *       AppGini.addToProfileMenu({
 *          href: 'test',
 *          text: 'Test link',
 *          img: 'path/to/img'
 *       })
 * 
 *    To append a link labeled 'Test link' with a checkmark glyphicon
 *    that opens a page 'test' in a new tab: 
 *       AppGini.addToProfileMenu({
 *          href: 'test',
 *          text: 'Test link',
 *          target: '_blank',
 *          icon: 'ok'
 *       })
 */
AppGini.addToProfileMenu = (link) => {
	const li = $j('<li></li>')

	if(link === '--')
		return li.addClass('divider').appendTo('.profile-menu');

	const linkHtml = $j('<a></a>');

	linkHtml
		.attr('href', link.href ?? '')
		.addClass(link.class ?? '')
		.attr('target', link.target ?? '')
		.text((link.text ?? '').trim())

	if(link.img ?? false)
		$j('<img>')
			.attr('src', link.img)
			.css({
				maxHeight: '1.5em',
				maxWidth: '1.5em',
				marginLeft: '-0.4em',
			})
			.addClass('rspacer-sm')
			.prependTo(linkHtml)

	else if(link.icon ?? false)
		$j(`<i></i>`)
			.addClass(`glyphicon glyphicon-${link.icon} rspacer-md`)
			.prependTo(linkHtml)

	linkHtml.appendTo(li);
	li.appendTo('.profile-menu')
}

/**
 * Creates a CSS class with the provided name and properties.
 * Properties are retrieved from the provided class(es) plus the provided
 * additional properties.
 * 
 * @param {string} className The name of the CSS class to create.
 * @param {string} sourceElmClass The class(es) to retrieve properties from.
 * @param {string[]} sourceElmProps The properties to retrieve from the source class(es).
 * @param {object} additionalCSSProps Additional properties to add to the created class.
 */
AppGini.createCSSClass = (className, sourceElmClass, sourceElmProps = [], additionalCSSProps = {}) => {
	// create a new hidden element with the provided class
	const hiddenElm = document.createElement('div');
	hiddenElm.classList.add('hidden');
	hiddenElm.classList.add(...sourceElmClass.split(' '));
	document.body.appendChild(hiddenElm);

	// get the computed style of the hidden element
	const computedStyle = window.getComputedStyle(hiddenElm);

	// retrieve the provided properties from the computed style
	const props = {};
	sourceElmProps.forEach(prop => {
		props[prop] = computedStyle.getPropertyValue(prop);
	});

	// create a new style element with the provided class and properties
	const styleElm = document.createElement('style');
	styleElm.innerHTML = `.${className} {`;
	Object.entries(props).forEach(([prop, value]) => {
		styleElm.innerHTML += `${prop}: ${value}; `;
	});
	Object.entries(additionalCSSProps).forEach(([prop, value]) => {
		styleElm.innerHTML += `${prop}: ${value}; `;
	});
	styleElm.innerHTML += '}';

	// add the new style element to the head of the document
	document.head.appendChild(styleElm);

	// remove the hidden element
	document.body.removeChild(hiddenElm);
}

/**
 * Detect and update child info elements in the current page.
 * @param {boolean} scheduleNextCall If true, schedule next call to this function.
 */
AppGini.updateChildrenCount = (scheduleNextCall = true) => {
	if(!$j('.count-children').length) return;

	AppGini._childrenCount = AppGini._childrenCount || {};

	// if there are no visible .count-children elements, schedule next call and return
	if(!$j('.count-children:visible').length && scheduleNextCall) {
		setTimeout(AppGini.updateChildrenCount, 1000);
		return;
	}

	// TVP?
	if($j('#current_view').val() == 'TVP' && !AppGini._childRecordsInfoAdaptedToTVP) {
		// show only the count, no link, no add new
		$j('.child-records-info').not('th').html('<div class="text-right count-children"></div>');
		AppGini._childRecordsInfoAdaptedToTVP = true;
	}

	// for each .count-children element, get the nearest parent .child-records-info
	const countEls = {};
	$j('.count-children').each(function() {
		const countEl = $j(this);
		const parentEl = countEl.closest('.child-records-info');
		const childTable = parentEl.data('table');
		const childLookupField = parentEl.data('lookup-field');
		const id = parentEl.data('id');

		if(!childTable || !childLookupField || !id) return;

		countEls[childTable] = countEls[childTable] || {};
		countEls[childTable][childLookupField] = countEls[childTable][childLookupField] || [];
		countEls[childTable][childLookupField].push(id);
	});

	if(Object.keys(countEls).length == 0) return;

	// disable .update-children-count buttons
	$j('.update-children-count').prop('disabled', true);

	// issue an ajax request for each child table > lookup field
	let countsChanged = false;
	const requests = [];
	const startTimestamp = AppGini.unixTimestamp().msec;
	for(const ChildTable in countEls) {
		for(const ChildLookupField in countEls[ChildTable]) {
			const childInfoContainers = $j(`.child-records-info[data-table=${ChildTable}][data-lookup-field=${ChildLookupField}]`);

			requests.push($j.ajax({
				url: `${AppGini.config.url.replace(/\/$/, '')}/parent-children.php`,
				data: {
					ChildTable,
					ChildLookupField,
					IDs: countEls[ChildTable][ChildLookupField],
					Operation: 'get-count'
				},
				type: 'POST',
				success: function(resp) {
					if(resp.status != 'success') return;

					// resp.data is an object with keys = IDs and values = count
					for(const id in resp.data.counts) {
						const currentCell = childInfoContainers.filter(`[data-id=${id}]`).find('.count-children')
						if(currentCell.length == 0) continue;

						if(AppGini._childrenCount[ChildTable]?.[ChildLookupField]?.[id] != resp.data.counts[id]) {
							countsChanged = true;
							currentCell.text(resp.data.counts[id]);

							// highlight changed cell for 1 sec
							currentCell.addClass('bg-warning');
							setTimeout(() => currentCell.removeClass('bg-warning'), 1000);
						}

						AppGini._childrenCount[ChildTable] = AppGini._childrenCount[ChildTable] || {};
						AppGini._childrenCount[ChildTable][ChildLookupField] = AppGini._childrenCount[ChildTable][ChildLookupField] || {};
						AppGini._childrenCount[ChildTable][ChildLookupField][id] = resp.data.counts[id];

					}
				}
			}));
		}
	}

	// when all requests are done, schedule next call
	$j.when(...requests).done(function() {
		// trigger childrenCountChanged event if counts changed
		if(countsChanged)
			$j(document).trigger('childrenCountChanged');

		// enable .update-children-count buttons
		$j('.update-children-count').prop('disabled', false);

		// if elapsed time is > 2 seconds, wait 1 minute before next call, else wait 10 seconds
		const elapsed = AppGini.unixTimestamp().msec - startTimestamp;
		if(scheduleNextCall)
			setTimeout(AppGini.updateChildrenCount, elapsed > 2000 ? 60000 : 10000);
	});
}

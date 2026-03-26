var AppGini = AppGini || {};

AppGini.shortcutKeyMap = {
	// shortcut: { purpose: 'translation string', trigger: ('focus' | 'click' | 'function' | false), css: 'css-selector', handler: function }
	//             => trigger: false means display only but no specific handler
	//             => handler function is called only if trigger is 'function'
	//             => if purpose contains a placeholder <x>, replace with css target's text
	//             => if purpose is an empty string, use element's text as purpose
	//             => if purpose is null, the shortcut is not displayed in the help window

	// show help
	'Shift+F1': { purpose: null, trigger: 'click', css: '.help-shortcuts-launcher' }, // launch help modal
	'Ctrl+F1': { purpose: null, trigger: 'click', css: '.help-shortcuts-launcher' }, // launch help modal
	'Ctrl/Shift+F1': { purpose: 'display this help window', trigger: false }, // for display purpose only

	// top navbar
	'Alt+0': { purpose: 'homepage', trigger: 'focus', css: 'a.navbar-brand' }, // focus on homepage link
	'Alt+1': { purpose: 'open x navigation menu', trigger: 'click', css: '.navbar-nav li.dropdown:nth-child(1) > a:not(.profile-menu-icon), .vertical-nav .panel:nth-child(1) .panel-heading' }, // open first nav menu
	'Alt+2': { purpose: 'open x navigation menu', trigger: 'click', css: '.navbar-nav li.dropdown:nth-child(2) > a:not(.profile-menu-icon), .vertical-nav .panel:nth-child(2) .panel-heading' }, // open 2nd nav menu
	'Alt+3': { purpose: 'open x navigation menu', trigger: 'click', css: '.navbar-nav li.dropdown:nth-child(3) > a:not(.profile-menu-icon), .vertical-nav .panel:nth-child(3) .panel-heading' }, // open 3rd nav menu
	'Alt+4': { purpose: 'open x navigation menu', trigger: 'click', css: '.navbar-nav li.dropdown:nth-child(4) > a:not(.profile-menu-icon), .vertical-nav .panel:nth-child(4) .panel-heading' }, // open 4th nav menu
	'Alt+5': { purpose: 'open x navigation menu', trigger: 'click', css: '.navbar-nav li.dropdown:nth-child(5) > a:not(.profile-menu-icon), .vertical-nav .panel:nth-child(5) .panel-heading' }, // open 5th nav menu
	'Alt+6': { purpose: 'open x navigation menu', trigger: 'click', css: '.navbar-nav li.dropdown:nth-child(6) > a:not(.profile-menu-icon), .vertical-nav .panel:nth-child(6) .panel-heading' }, // open 6th nav menu
	'Alt+7': { purpose: 'open x navigation menu', trigger: 'click', css: '.navbar-nav li.dropdown:nth-child(7) > a:not(.profile-menu-icon), .vertical-nav .panel:nth-child(7) .panel-heading' }, // open 7th nav menu
	'Alt+8': { purpose: 'open x navigation menu', trigger: 'click', css: '.navbar-nav li.dropdown:nth-child(8) > a:not(.profile-menu-icon), .vertical-nav .panel:nth-child(8) .panel-heading' }, // open 8th nav menu
	'Alt+9': { purpose: 'open x navigation menu', trigger: 'click', css: '.navbar-nav li.dropdown:nth-child(9) > a:not(.profile-menu-icon), .vertical-nav .panel:nth-child(9) .panel-heading' }, // open 9th nav menu
	'Alt+M': { purpose: 'import csv', trigger: 'focus', css: '.btn-import-csv' }, // focus on import CSV button
	'Alt+A': { purpose: 'admin area', trigger: 'focus', css: '.btn-admin-area' }, // focus on admin area link (if admin)
	'Alt+P': { purpose: 'user profile', trigger: 'click', css: 'a.profile-menu-icon' }, // focus on user profile link

	// quick search
	'Alt+Q': { purpose: 'quick search', trigger: 'focus', css: '#SearchString' }, // focus quick search
	'Alt+Shift+Q': { purpose: 'clear search', trigger: 'click', css: '#ClearQuickSearch' }, // clear quick search

	// navigate between sections of page
	'F2': { purpose: 'navigate to action buttons', trigger: 'focus', css: '[id$="dv_action_buttons"] .btn, .all_records:visible .btn, #applyFilters, #sendToPrinter, #print, .collapser' }, // focus on first action button in DV, first top button in TV, apply button in filters, cancel button in DVP
	'Shift+F2': { purpose: 'next/previous page', trigger: 'focus', css: '.pagination-section .btn, .pagination-section select' }, // focus first navigation element in TV
	'Ctrl+F2': { purpose: 'go to first record', trigger: 'focus', css: '.table_view .record_selector, .table_view .td a, .children-tabs td a, .panel-body .btn-block:visible' }, // focus first record in TV/chidren table

	// admin info menu
	'Alt+I': { purpose: 'open admin info menu', trigger: 'click', css: '#admin-tools-menu-button > .btn' }, // open admin info menu, and focus first link

	// navigate between sections of child tabs
	'F8': { purpose: 'go to child links', trigger: 'focus', css: '.children-links a:visible' }, // focus first child link button
	'Shift+F8': { purpose: 'navigate between sections of child records', trigger: 'focus', css: '.children-tabs ul.nav-tabs li:nth-child(1) a, .child-tab-print-toggler' }, // focus first child tab
	'Alt+F8': { purpose: 'navigate between sections of child records', trigger: 'focus', css: '.children-tabs .btn-previous:visible:not(:disabled), .children-tabs .btn-next:visible:not(:disabled), .children-tabs ul.nav-tabs li:nth-child(1) a' }, // focus on first navigation button in child tab
	'Ctrl+F8': { purpose: 'navigate between sections of child records', trigger: 'focus', css: '.children-tabs td a, .children-tabs th, .child-panel-print th'}, // focus first record in chidren table

	// navigate next/previous records/pages
	'Ctrl+ArrowLeft': { purpose: '', trigger: 'function', css: '.previous-record, #Previous', handler: function() {
		// skip if a textbox/textarea has focus
		if(AppGini.inputHasFocus()) return;
		$j('.previous-record, #Previous').click();
	}},
	'Ctrl+ArrowRight': { purpose: '', trigger: 'function', css: '.next-record, #Next', handler: function() {
		// skip if a textbox/textarea has focus
		if(AppGini.inputHasFocus()) return;
		$j('.next-record, #Next').click();
	}},
	'Ctrl+ArrowUp': { purpose: 'Previous record', trigger: 'function', handler: function() {
		// if DV, go to previous record
		if($j('.previous-record').length) return $j('.previous-record').trigger('click');

		// skip if not TV or nothing focused
		if(!$j('.record_selector').length || !$j(':focus').length) return;

		var tr = $j(':focus').parents('tr');

		// skip if not focused in table  or first record already
		if(!tr.find('.record_selector').length || !tr.prev('tr').length) return;

		tr.prev('tr').find('.record_selector').focus();
	}},
	'Ctrl+ArrowDown': { purpose: 'Next record', trigger: 'function', handler: function() {
		// if DV, go to next record
		if($j('.next-record').length) return $j('.next-record').trigger('click');

		// skip if not TV or nothing focused
		if(!$j('.record_selector').length || !$j(':focus').length) return;

		var tr = $j(':focus').parents('tr');

		// skip if not focused in table  or last record already
		if(!tr.find('.record_selector').length || !tr.next('tr').length) return;

		tr.next('tr').find('.record_selector').focus();
	}},

	// toggle select all records
	'Alt+Ctrl+S': { purpose: 'Select all records', trigger: 'click', css: '#select_all_records' },

	// open More menu
	'Alt+Ctrl+M': { purpose: '', trigger: 'click', css: '[id=selected_records_more]' },

	// save changes
	'Ctrl+Enter': { purpose: '', trigger: 'click', css: '#update, #insert, #applyFilters' }, // save changes/apply filters (doesn't work in all browsers)
	'Ctrl+Shift+Enter': { purpose: '', trigger: 'click', css: '#insert, #SaveFilter' }, // save as copy/save filter (doesn't work in all browsers) 

	// quit action
	'Alt+X': { purpose: '', trigger: 'click', css: '#deselect, #cancelFilters, .cancel-print, #back' }, // cancel editing/printing/filters and go back

	'Esc': { purpose: 'close this window', trigger: false } // this has no effect except in help window
}


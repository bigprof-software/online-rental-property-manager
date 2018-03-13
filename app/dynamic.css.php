<?php header('Content-type: text/css'); ?>

@media (min-width: 768px){ .container{ width: 90% !important; } }
@media print{
	a[href]:after{ content: "" !important; }
	.container{ width: 98% !important; }
}

.navbar-brand{ text-transform: capitalize; }

table a, .table a { text-decoration: none !important; }

#children-tabs li a{ display: block !important; }

.hidden{ visibility: hidden !important; }

iframe{ border: none; overflow: auto; }

.tab-content{ padding: 10px 20px; border: 1px solid #DDDDDD; border-top: none; }

#pc-loading{ background: none repeat scroll 0 0 yellow; font-family: arial; left: 10px; margin-top: -10px; opacity: 0.85; position: absolute; top: 20px; width: 150px; }

.navbar a.btn { margin-left: 10px; margin-right: 10px; }

.view-on-click a.btn { max-width: 75px; }

/* prevent prototype conflicts */
li.dropdown{ display: block !important; }

.hspacer-xs{ margin-left: 0.1em; margin-right: 0.1em; }
.hspacer-sm{ margin-left: 0.2em; margin-right: 0.2em; }
.hspacer-md{ margin-left: 0.4em; margin-right: 0.4em; }
.hspacer-lg{ margin-left: 0.8em; margin-right: 0.8em; }
.vspacer-xs{ margin-top: 0.1em; margin-bottom: 0.1em; }
.vspacer-sm{ margin-top: 0.2em; margin-bottom: 0.2em; }
.vspacer-md{ margin-top: 0.4em; margin-bottom: 0.4em; }
.vspacer-lg{ margin-top: 0.8em; margin-bottom: 0.8em; }

div.datePicker{ font-size: 1.3em; }
.always_shown{ display: inline !important; }
.text-bold{ font-weight: bold; }
.text-italic{ font-style: italic; }

.form-control, .help-block .alert{ width: 90% !important; }
.input-group .form-control{ width: 100% !important; }
.form-inline .form-control{ width: auto !important; }
.panel .btn{ overflow: hidden; }

.select2-container .select2-choice{ height: 2.4em; line-height: 2.2em; }
.select2-container .select2-choice .select2-arrow b{ background-position: 0 -0.1em; }

.navbar ul.dropdown-menu{ max-height: 400px; overflow-y: auto; }

.date_combo { padding-right: 0 !important; }
.date_combo select { width: 100% !important; padding-left: 0; padding-right: 0; }

img[src="blank.gif"] { max-height: 10px !important; }

.applications_leases-tenants{ white-space: normal !important; max-width: 60px !important; min-width: 60px !important; overflow: hidden;  }
.applications_leases-status{ white-space: normal !important; max-width: 50px !important; min-width: 50px !important; overflow: hidden;  }
.applications_leases-property{ white-space: normal !important; max-width: 80px !important; min-width: 80px !important; overflow: hidden;  }
.applications_leases-unit{ white-space: normal !important; max-width: 60px !important; min-width: 60px !important; overflow: hidden;  }
.applications_leases-type{ white-space: normal !important; max-width: 40px !important; min-width: 40px !important; overflow: hidden;  }
.applications_leases-total_number_of_occupants{ white-space: normal !important; max-width: 70px !important; min-width: 70px !important; overflow: hidden;  }
.applications_leases-start_date{ white-space: normal !important; max-width: 70px !important; min-width: 70px !important; overflow: hidden;  }
.applications_leases-end_date{ white-space: normal !important; max-width: 60px !important; min-width: 60px !important; overflow: hidden;  }
.applications_leases-rent{ white-space: normal !important; max-width: 70px !important; min-width: 70px !important; overflow: hidden;  }
.applications_leases-security_deposit{ white-space: normal !important; max-width: 80px !important; min-width: 80px !important; overflow: hidden;  }
.applications_leases-security_deposit_date{ white-space: normal !important; max-width: 80px !important; min-width: 80px !important; overflow: hidden;  }
.applications_leases-emergency_contact{ white-space: normal !important; max-width: 120px !important; min-width: 120px !important; overflow: hidden;  }
.applications_leases-co_signer_details{ white-space: normal !important; max-width: 100px !important; min-width: 100px !important; overflow: hidden;  }
.applications_leases-notes{ white-space: normal !important; max-width: 100px !important; min-width: 100px !important; overflow: hidden;  }
.applications_leases-agreement{ white-space: normal !important; max-width: 70px !important; min-width: 70px !important; overflow: hidden;  }
.residence_and_rental_history-address{ white-space: normal !important; max-width: 180px !important; min-width: 180px !important; overflow: hidden;  }
.residence_and_rental_history-landlord_or_manager_name{ white-space: normal !important; max-width: 120px !important; min-width: 120px !important; overflow: hidden;  }
.residence_and_rental_history-landlord_or_manager_phone{ white-space: normal !important; max-width: 100px !important; min-width: 100px !important; overflow: hidden;  }
.residence_and_rental_history-monthly_rent{ white-space: normal !important; max-width: 80px !important; min-width: 80px !important; overflow: hidden;  }
.residence_and_rental_history-duration_of_residency_from{ white-space: normal !important; max-width: 100px !important; min-width: 100px !important; overflow: hidden;  }
.residence_and_rental_history-to{ white-space: normal !important; max-width: 80px !important; min-width: 80px !important; overflow: hidden;  }
.residence_and_rental_history-reason_for_leaving{ white-space: normal !important; max-width: 120px !important; min-width: 120px !important; overflow: hidden;  }
.residence_and_rental_history-notes{ white-space: normal !important; max-width: 120px !important; min-width: 120px !important; overflow: hidden;  }
.employment_and_income_history-employer_name{ white-space: normal !important; max-width: 80px !important; min-width: 80px !important; overflow: hidden;  }
.employment_and_income_history-city{ white-space: normal !important; max-width: 100px !important; min-width: 100px !important; overflow: hidden;  }
.employment_and_income_history-employer_phone{ white-space: normal !important; max-width: 100px !important; min-width: 100px !important; overflow: hidden;  }
.employment_and_income_history-employed_from{ white-space: normal !important; max-width: 80px !important; min-width: 80px !important; overflow: hidden;  }
.employment_and_income_history-employed_till{ white-space: normal !important; max-width: 80px !important; min-width: 80px !important; overflow: hidden;  }
.employment_and_income_history-occupation{ white-space: normal !important; max-width: 100px !important; min-width: 100px !important; overflow: hidden;  }
.employment_and_income_history-notes{ white-space: normal !important; max-width: 50px !important; min-width: 50px !important; overflow: hidden;  }
.references-reference_name{ white-space: normal !important; max-width: 160px !important; min-width: 160px !important; overflow: hidden;  }
.references-phone{ white-space: normal !important; max-width: 160px !important; min-width: 160px !important; overflow: hidden;  }
.applicants_and_tenants-last_name{ white-space: normal !important; max-width: 100px !important; min-width: 100px !important; overflow: hidden;  }
.applicants_and_tenants-first_name{ white-space: normal !important; max-width: 100px !important; min-width: 100px !important; overflow: hidden;  }
.applicants_and_tenants-email{ white-space: normal !important; max-width: 100px !important; min-width: 100px !important; overflow: hidden; word-break: break-all; }
.applicants_and_tenants-phone{ white-space: normal !important; max-width: 100px !important; min-width: 100px !important; overflow: hidden;  }
.applicants_and_tenants-birth_date{ white-space: normal !important; max-width: 100px !important; min-width: 100px !important; overflow: hidden;  }
.applicants_and_tenants-driver_license_number{ white-space: normal !important; max-width: 100px !important; min-width: 100px !important; overflow: hidden;  }
.applicants_and_tenants-driver_license_state{ white-space: normal !important; max-width: 100px !important; min-width: 100px !important; overflow: hidden;  }
.applicants_and_tenants-requested_lease_term{ white-space: normal !important; max-width: 100px !important; min-width: 100px !important; overflow: hidden;  }
.applicants_and_tenants-monthly_gross_pay{ white-space: normal !important; max-width: 80px !important; min-width: 80px !important; overflow: hidden;  }
.applicants_and_tenants-additional_income{ white-space: normal !important; max-width: 80px !important; min-width: 80px !important; overflow: hidden;  }
.applicants_and_tenants-assets{ white-space: normal !important; max-width: 80px !important; min-width: 80px !important; overflow: hidden;  }
.applicants_and_tenants-status{ white-space: normal !important; max-width: 100px !important; min-width: 100px !important; overflow: hidden;  }
.applicants_and_tenants-notes{ white-space: normal !important; max-width: 100px !important; min-width: 100px !important; overflow: hidden;  }
.properties-property_name{ white-space: normal !important; max-width: 50px !important; min-width: 50px !important; overflow: hidden;  }
.properties-type{ white-space: normal !important; max-width: 80px !important; min-width: 80px !important; overflow: hidden;  }
.properties-number_of_units{ white-space: normal !important; max-width: 50px !important; min-width: 50px !important; overflow: hidden;  }
.properties-photo{ white-space: normal !important; max-width: 60px !important; min-width: 60px !important; overflow: hidden;  }
.properties-owner{ white-space: normal !important; max-width: 100px !important; min-width: 100px !important; overflow: hidden;  }
.properties-operating_account{ white-space: normal !important; max-width: 120px !important; min-width: 120px !important; overflow: hidden;  }
.properties-property_reserve{ white-space: normal !important; max-width: 70px !important; min-width: 70px !important; overflow: hidden;  }
.properties-street{ white-space: normal !important; max-width: 120px !important; min-width: 120px !important; overflow: hidden;  }
.properties-City{ white-space: normal !important; max-width: 70px !important; min-width: 70px !important; overflow: hidden;  }
.properties-State{ white-space: normal !important; max-width: 50px !important; min-width: 50px !important; overflow: hidden;  }
.properties-ZIP{ white-space: normal !important; max-width: 50px !important; min-width: 50px !important; overflow: hidden;  }
.units-id{ white-space: normal !important; max-width: 150px !important; min-width: 150px !important; overflow: hidden;  }
.units-property{ white-space: normal !important; max-width: 90px !important; min-width: 90px !important; overflow: hidden;  }
.units-unit_number{ white-space: normal !important; max-width: 40px !important; min-width: 40px !important; overflow: hidden;  }
.units-photo{ white-space: normal !important; max-width: 60px !important; min-width: 60px !important; overflow: hidden;  }
.units-status{ white-space: normal !important; max-width: 60px !important; min-width: 60px !important; overflow: hidden;  }
.units-size{ white-space: normal !important; max-width: 60px !important; min-width: 60px !important; overflow: hidden;  }
.units-country{ white-space: normal !important; max-width: 80px !important; min-width: 80px !important; overflow: hidden;  }
.units-street{ white-space: normal !important; max-width: 100px !important; min-width: 100px !important; overflow: hidden;  }
.units-city{ white-space: normal !important; max-width: 55px !important; min-width: 55px !important; overflow: hidden;  }
.units-state{ white-space: normal !important; max-width: 40px !important; min-width: 40px !important; overflow: hidden;  }
.units-postal_code{ white-space: normal !important; max-width: 80px !important; min-width: 80px !important; overflow: hidden;  }
.units-rooms{ white-space: normal !important; max-width: 45px !important; min-width: 45px !important; overflow: hidden;  }
.units-bathroom{ white-space: normal !important; max-width: 70px !important; min-width: 70px !important; overflow: hidden;  }
.units-features{ white-space: normal !important; max-width: 150px !important; min-width: 150px !important; overflow: hidden;  }
.units-rental_amount{ white-space: normal !important; max-width: 60px !important; min-width: 60px !important; overflow: hidden;  }
.units-deposit_amount{ white-space: normal !important; max-width: 50px !important; min-width: 50px !important; overflow: hidden;  }
.units-description{ white-space: normal !important; max-width: 80px !important; min-width: 80px !important; overflow: hidden;  }

/* fixes for glyph icons in some themes */
.glyphicon-camera:before { content: "\e046"; }
.glyphicon-lock:before { content: "\e033"; }
.glyphicon-eur:before { content: "\20ac"; }
.glyphicon-calendar:before { content: "\e109"; }
.glyphicon-bell:before { content: "\e123"; }
.glyphicon-wrench:before { content: "\e136"; }
.glyphicon-briefcase:before { content: "\e139"; }

.navbar-right {
	margin-right: 0 !important;
}

.no-caption .field-caption-tv{  display: none; }
.no-caption dd{ margin-left: 0; margin-right: 0; }

/* compact theme styles */
.container.theme-compact{ font-size: 0.857em; }

.theme-compact .btn {
	font-size: 12px;
	padding: 4px 10px;
}

.theme-compact .btn-lg, .theme-compact .btn-group-lg > .btn {
	font-size: 15px;
	padding: 6px 15px;
}

.theme-compact .form-group {
	margin-bottom: 8px;
}

.theme-compact .form-control {
	font-size: 12px;
	height: auto;
	padding: 4px 6px;
}

.theme-compact .input-sm {
	border-radius: 3px;
	font-size: 12px;
	padding: 2px 6px;
}

.theme-compact select.input-sm {
	height: 25px;
	line-height: 25px;
}

.theme-compact .dropdown-menu {
	font-size: 12px;
}

.theme-compact .table > thead > tr > th, .theme-compact .table > tbody > tr > th, .theme-compact .table > tfoot > tr > th, .theme-compact .table > thead > tr > td, .theme-compact .table > tbody > tr > td, .theme-compact .table > tfoot > tr > td {
	padding: 4px;
}

.theme-compact h1, .theme-compact h2, .theme-compact h3, .theme-compact h4, .theme-compact h5, .theme-compact h6, .theme-compact .h1, .theme-compact .h2, .theme-compact .h3, .theme-compact .h4, .theme-compact .h5, .theme-compact .h6 {
	line-height: 2;
}

.theme-compact h1, .theme-compact .h1 {
	font-size: 27px;
}

.theme-compact h2, .theme-compact .h2 {
	font-size: 24px;
}

.theme-compact h3, .theme-compact .h3 {
	font-size: 20px;
}

.theme-compact h4, .theme-compact .h4 {
	font-size: 16px;
}

.theme-compact .navbar {
	margin-bottom: 13px;
	min-height: 40px;
}

.theme-compact .navbar-fixed-bottom {
	margin-bottom: 0 !important;
}

.theme-compact .navbar-brand {
	font-size: 15px;
	height: 40px;
	padding: 12px;
}

.theme-compact .navbar-nav > li > a {
	padding-bottom: 9px;
	padding-top: 9px;
	line-height: 26px;
}

.theme-compact .navbar-text {
	margin-bottom: 12px;
	margin-top: 14px;
}

.theme-compact .page-header {
	margin: 20px 0 10px;
	padding-bottom: 0;
}

.theme-compact .navbar-nav > li > a { margin-top: 0; margin-bottom: 0; }

.theme-compact .panel-heading {
	padding: 6px;
}

.theme-compact .panel-title {
	font-size: 14px;
}


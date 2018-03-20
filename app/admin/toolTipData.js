var FiltersEnabled = 0; // if your not going to use transitions or filters in any of the tips set this to 0
var spacer="&nbsp; &nbsp; &nbsp; ";

// email notifications to admin
notifyAdminNewMembers0Tip=["", spacer+"No email notifications to admin."];
notifyAdminNewMembers1Tip=["", spacer+"Notify admin only when a new member is waiting for approval."];
notifyAdminNewMembers2Tip=["", spacer+"Notify admin for all new sign-ups."];

// visitorSignup
visitorSignup0Tip=["", spacer+"If this option is selected, visitors will not be able to join this group unless the admin manually moves them to this group from the admin area."];
visitorSignup1Tip=["", spacer+"If this option is selected, visitors can join this group but will not be able to sign in unless the admin approves them from the admin area."];
visitorSignup2Tip=["", spacer+"If this option is selected, visitors can join this group and will be able to sign in instantly with no need for admin approval."];

// applicants_and_tenants table
applicants_and_tenants_addTip=["",spacer+"This option allows all members of the group to add records to the 'Applicants and tenants' table. A member who adds a record to the table becomes the 'owner' of that record."];

applicants_and_tenants_view0Tip=["",spacer+"This option prohibits all members of the group from viewing any record in the 'Applicants and tenants' table."];
applicants_and_tenants_view1Tip=["",spacer+"This option allows each member of the group to view only his own records in the 'Applicants and tenants' table."];
applicants_and_tenants_view2Tip=["",spacer+"This option allows each member of the group to view any record owned by any member of the group in the 'Applicants and tenants' table."];
applicants_and_tenants_view3Tip=["",spacer+"This option allows each member of the group to view all records in the 'Applicants and tenants' table."];

applicants_and_tenants_edit0Tip=["",spacer+"This option prohibits all members of the group from modifying any record in the 'Applicants and tenants' table."];
applicants_and_tenants_edit1Tip=["",spacer+"This option allows each member of the group to edit only his own records in the 'Applicants and tenants' table."];
applicants_and_tenants_edit2Tip=["",spacer+"This option allows each member of the group to edit any record owned by any member of the group in the 'Applicants and tenants' table."];
applicants_and_tenants_edit3Tip=["",spacer+"This option allows each member of the group to edit any records in the 'Applicants and tenants' table, regardless of their owner."];

applicants_and_tenants_delete0Tip=["",spacer+"This option prohibits all members of the group from deleting any record in the 'Applicants and tenants' table."];
applicants_and_tenants_delete1Tip=["",spacer+"This option allows each member of the group to delete only his own records in the 'Applicants and tenants' table."];
applicants_and_tenants_delete2Tip=["",spacer+"This option allows each member of the group to delete any record owned by any member of the group in the 'Applicants and tenants' table."];
applicants_and_tenants_delete3Tip=["",spacer+"This option allows each member of the group to delete any records in the 'Applicants and tenants' table."];

// applications_leases table
applications_leases_addTip=["",spacer+"This option allows all members of the group to add records to the 'Applications/Leases' table. A member who adds a record to the table becomes the 'owner' of that record."];

applications_leases_view0Tip=["",spacer+"This option prohibits all members of the group from viewing any record in the 'Applications/Leases' table."];
applications_leases_view1Tip=["",spacer+"This option allows each member of the group to view only his own records in the 'Applications/Leases' table."];
applications_leases_view2Tip=["",spacer+"This option allows each member of the group to view any record owned by any member of the group in the 'Applications/Leases' table."];
applications_leases_view3Tip=["",spacer+"This option allows each member of the group to view all records in the 'Applications/Leases' table."];

applications_leases_edit0Tip=["",spacer+"This option prohibits all members of the group from modifying any record in the 'Applications/Leases' table."];
applications_leases_edit1Tip=["",spacer+"This option allows each member of the group to edit only his own records in the 'Applications/Leases' table."];
applications_leases_edit2Tip=["",spacer+"This option allows each member of the group to edit any record owned by any member of the group in the 'Applications/Leases' table."];
applications_leases_edit3Tip=["",spacer+"This option allows each member of the group to edit any records in the 'Applications/Leases' table, regardless of their owner."];

applications_leases_delete0Tip=["",spacer+"This option prohibits all members of the group from deleting any record in the 'Applications/Leases' table."];
applications_leases_delete1Tip=["",spacer+"This option allows each member of the group to delete only his own records in the 'Applications/Leases' table."];
applications_leases_delete2Tip=["",spacer+"This option allows each member of the group to delete any record owned by any member of the group in the 'Applications/Leases' table."];
applications_leases_delete3Tip=["",spacer+"This option allows each member of the group to delete any records in the 'Applications/Leases' table."];

// residence_and_rental_history table
residence_and_rental_history_addTip=["",spacer+"This option allows all members of the group to add records to the 'Residence and rental history' table. A member who adds a record to the table becomes the 'owner' of that record."];

residence_and_rental_history_view0Tip=["",spacer+"This option prohibits all members of the group from viewing any record in the 'Residence and rental history' table."];
residence_and_rental_history_view1Tip=["",spacer+"This option allows each member of the group to view only his own records in the 'Residence and rental history' table."];
residence_and_rental_history_view2Tip=["",spacer+"This option allows each member of the group to view any record owned by any member of the group in the 'Residence and rental history' table."];
residence_and_rental_history_view3Tip=["",spacer+"This option allows each member of the group to view all records in the 'Residence and rental history' table."];

residence_and_rental_history_edit0Tip=["",spacer+"This option prohibits all members of the group from modifying any record in the 'Residence and rental history' table."];
residence_and_rental_history_edit1Tip=["",spacer+"This option allows each member of the group to edit only his own records in the 'Residence and rental history' table."];
residence_and_rental_history_edit2Tip=["",spacer+"This option allows each member of the group to edit any record owned by any member of the group in the 'Residence and rental history' table."];
residence_and_rental_history_edit3Tip=["",spacer+"This option allows each member of the group to edit any records in the 'Residence and rental history' table, regardless of their owner."];

residence_and_rental_history_delete0Tip=["",spacer+"This option prohibits all members of the group from deleting any record in the 'Residence and rental history' table."];
residence_and_rental_history_delete1Tip=["",spacer+"This option allows each member of the group to delete only his own records in the 'Residence and rental history' table."];
residence_and_rental_history_delete2Tip=["",spacer+"This option allows each member of the group to delete any record owned by any member of the group in the 'Residence and rental history' table."];
residence_and_rental_history_delete3Tip=["",spacer+"This option allows each member of the group to delete any records in the 'Residence and rental history' table."];

// employment_and_income_history table
employment_and_income_history_addTip=["",spacer+"This option allows all members of the group to add records to the 'Employment and income history' table. A member who adds a record to the table becomes the 'owner' of that record."];

employment_and_income_history_view0Tip=["",spacer+"This option prohibits all members of the group from viewing any record in the 'Employment and income history' table."];
employment_and_income_history_view1Tip=["",spacer+"This option allows each member of the group to view only his own records in the 'Employment and income history' table."];
employment_and_income_history_view2Tip=["",spacer+"This option allows each member of the group to view any record owned by any member of the group in the 'Employment and income history' table."];
employment_and_income_history_view3Tip=["",spacer+"This option allows each member of the group to view all records in the 'Employment and income history' table."];

employment_and_income_history_edit0Tip=["",spacer+"This option prohibits all members of the group from modifying any record in the 'Employment and income history' table."];
employment_and_income_history_edit1Tip=["",spacer+"This option allows each member of the group to edit only his own records in the 'Employment and income history' table."];
employment_and_income_history_edit2Tip=["",spacer+"This option allows each member of the group to edit any record owned by any member of the group in the 'Employment and income history' table."];
employment_and_income_history_edit3Tip=["",spacer+"This option allows each member of the group to edit any records in the 'Employment and income history' table, regardless of their owner."];

employment_and_income_history_delete0Tip=["",spacer+"This option prohibits all members of the group from deleting any record in the 'Employment and income history' table."];
employment_and_income_history_delete1Tip=["",spacer+"This option allows each member of the group to delete only his own records in the 'Employment and income history' table."];
employment_and_income_history_delete2Tip=["",spacer+"This option allows each member of the group to delete any record owned by any member of the group in the 'Employment and income history' table."];
employment_and_income_history_delete3Tip=["",spacer+"This option allows each member of the group to delete any records in the 'Employment and income history' table."];

// references table
references_addTip=["",spacer+"This option allows all members of the group to add records to the 'References' table. A member who adds a record to the table becomes the 'owner' of that record."];

references_view0Tip=["",spacer+"This option prohibits all members of the group from viewing any record in the 'References' table."];
references_view1Tip=["",spacer+"This option allows each member of the group to view only his own records in the 'References' table."];
references_view2Tip=["",spacer+"This option allows each member of the group to view any record owned by any member of the group in the 'References' table."];
references_view3Tip=["",spacer+"This option allows each member of the group to view all records in the 'References' table."];

references_edit0Tip=["",spacer+"This option prohibits all members of the group from modifying any record in the 'References' table."];
references_edit1Tip=["",spacer+"This option allows each member of the group to edit only his own records in the 'References' table."];
references_edit2Tip=["",spacer+"This option allows each member of the group to edit any record owned by any member of the group in the 'References' table."];
references_edit3Tip=["",spacer+"This option allows each member of the group to edit any records in the 'References' table, regardless of their owner."];

references_delete0Tip=["",spacer+"This option prohibits all members of the group from deleting any record in the 'References' table."];
references_delete1Tip=["",spacer+"This option allows each member of the group to delete only his own records in the 'References' table."];
references_delete2Tip=["",spacer+"This option allows each member of the group to delete any record owned by any member of the group in the 'References' table."];
references_delete3Tip=["",spacer+"This option allows each member of the group to delete any records in the 'References' table."];

// rental_owners table
rental_owners_addTip=["",spacer+"This option allows all members of the group to add records to the 'Landlords' table. A member who adds a record to the table becomes the 'owner' of that record."];

rental_owners_view0Tip=["",spacer+"This option prohibits all members of the group from viewing any record in the 'Landlords' table."];
rental_owners_view1Tip=["",spacer+"This option allows each member of the group to view only his own records in the 'Landlords' table."];
rental_owners_view2Tip=["",spacer+"This option allows each member of the group to view any record owned by any member of the group in the 'Landlords' table."];
rental_owners_view3Tip=["",spacer+"This option allows each member of the group to view all records in the 'Landlords' table."];

rental_owners_edit0Tip=["",spacer+"This option prohibits all members of the group from modifying any record in the 'Landlords' table."];
rental_owners_edit1Tip=["",spacer+"This option allows each member of the group to edit only his own records in the 'Landlords' table."];
rental_owners_edit2Tip=["",spacer+"This option allows each member of the group to edit any record owned by any member of the group in the 'Landlords' table."];
rental_owners_edit3Tip=["",spacer+"This option allows each member of the group to edit any records in the 'Landlords' table, regardless of their owner."];

rental_owners_delete0Tip=["",spacer+"This option prohibits all members of the group from deleting any record in the 'Landlords' table."];
rental_owners_delete1Tip=["",spacer+"This option allows each member of the group to delete only his own records in the 'Landlords' table."];
rental_owners_delete2Tip=["",spacer+"This option allows each member of the group to delete any record owned by any member of the group in the 'Landlords' table."];
rental_owners_delete3Tip=["",spacer+"This option allows each member of the group to delete any records in the 'Landlords' table."];

// properties table
properties_addTip=["",spacer+"This option allows all members of the group to add records to the 'Properties' table. A member who adds a record to the table becomes the 'owner' of that record."];

properties_view0Tip=["",spacer+"This option prohibits all members of the group from viewing any record in the 'Properties' table."];
properties_view1Tip=["",spacer+"This option allows each member of the group to view only his own records in the 'Properties' table."];
properties_view2Tip=["",spacer+"This option allows each member of the group to view any record owned by any member of the group in the 'Properties' table."];
properties_view3Tip=["",spacer+"This option allows each member of the group to view all records in the 'Properties' table."];

properties_edit0Tip=["",spacer+"This option prohibits all members of the group from modifying any record in the 'Properties' table."];
properties_edit1Tip=["",spacer+"This option allows each member of the group to edit only his own records in the 'Properties' table."];
properties_edit2Tip=["",spacer+"This option allows each member of the group to edit any record owned by any member of the group in the 'Properties' table."];
properties_edit3Tip=["",spacer+"This option allows each member of the group to edit any records in the 'Properties' table, regardless of their owner."];

properties_delete0Tip=["",spacer+"This option prohibits all members of the group from deleting any record in the 'Properties' table."];
properties_delete1Tip=["",spacer+"This option allows each member of the group to delete only his own records in the 'Properties' table."];
properties_delete2Tip=["",spacer+"This option allows each member of the group to delete any record owned by any member of the group in the 'Properties' table."];
properties_delete3Tip=["",spacer+"This option allows each member of the group to delete any records in the 'Properties' table."];

// property_photos table
property_photos_addTip=["",spacer+"This option allows all members of the group to add records to the 'Property photos' table. A member who adds a record to the table becomes the 'owner' of that record."];

property_photos_view0Tip=["",spacer+"This option prohibits all members of the group from viewing any record in the 'Property photos' table."];
property_photos_view1Tip=["",spacer+"This option allows each member of the group to view only his own records in the 'Property photos' table."];
property_photos_view2Tip=["",spacer+"This option allows each member of the group to view any record owned by any member of the group in the 'Property photos' table."];
property_photos_view3Tip=["",spacer+"This option allows each member of the group to view all records in the 'Property photos' table."];

property_photos_edit0Tip=["",spacer+"This option prohibits all members of the group from modifying any record in the 'Property photos' table."];
property_photos_edit1Tip=["",spacer+"This option allows each member of the group to edit only his own records in the 'Property photos' table."];
property_photos_edit2Tip=["",spacer+"This option allows each member of the group to edit any record owned by any member of the group in the 'Property photos' table."];
property_photos_edit3Tip=["",spacer+"This option allows each member of the group to edit any records in the 'Property photos' table, regardless of their owner."];

property_photos_delete0Tip=["",spacer+"This option prohibits all members of the group from deleting any record in the 'Property photos' table."];
property_photos_delete1Tip=["",spacer+"This option allows each member of the group to delete only his own records in the 'Property photos' table."];
property_photos_delete2Tip=["",spacer+"This option allows each member of the group to delete any record owned by any member of the group in the 'Property photos' table."];
property_photos_delete3Tip=["",spacer+"This option allows each member of the group to delete any records in the 'Property photos' table."];

// units table
units_addTip=["",spacer+"This option allows all members of the group to add records to the 'Units' table. A member who adds a record to the table becomes the 'owner' of that record."];

units_view0Tip=["",spacer+"This option prohibits all members of the group from viewing any record in the 'Units' table."];
units_view1Tip=["",spacer+"This option allows each member of the group to view only his own records in the 'Units' table."];
units_view2Tip=["",spacer+"This option allows each member of the group to view any record owned by any member of the group in the 'Units' table."];
units_view3Tip=["",spacer+"This option allows each member of the group to view all records in the 'Units' table."];

units_edit0Tip=["",spacer+"This option prohibits all members of the group from modifying any record in the 'Units' table."];
units_edit1Tip=["",spacer+"This option allows each member of the group to edit only his own records in the 'Units' table."];
units_edit2Tip=["",spacer+"This option allows each member of the group to edit any record owned by any member of the group in the 'Units' table."];
units_edit3Tip=["",spacer+"This option allows each member of the group to edit any records in the 'Units' table, regardless of their owner."];

units_delete0Tip=["",spacer+"This option prohibits all members of the group from deleting any record in the 'Units' table."];
units_delete1Tip=["",spacer+"This option allows each member of the group to delete only his own records in the 'Units' table."];
units_delete2Tip=["",spacer+"This option allows each member of the group to delete any record owned by any member of the group in the 'Units' table."];
units_delete3Tip=["",spacer+"This option allows each member of the group to delete any records in the 'Units' table."];

// unit_photos table
unit_photos_addTip=["",spacer+"This option allows all members of the group to add records to the 'Unit photos' table. A member who adds a record to the table becomes the 'owner' of that record."];

unit_photos_view0Tip=["",spacer+"This option prohibits all members of the group from viewing any record in the 'Unit photos' table."];
unit_photos_view1Tip=["",spacer+"This option allows each member of the group to view only his own records in the 'Unit photos' table."];
unit_photos_view2Tip=["",spacer+"This option allows each member of the group to view any record owned by any member of the group in the 'Unit photos' table."];
unit_photos_view3Tip=["",spacer+"This option allows each member of the group to view all records in the 'Unit photos' table."];

unit_photos_edit0Tip=["",spacer+"This option prohibits all members of the group from modifying any record in the 'Unit photos' table."];
unit_photos_edit1Tip=["",spacer+"This option allows each member of the group to edit only his own records in the 'Unit photos' table."];
unit_photos_edit2Tip=["",spacer+"This option allows each member of the group to edit any record owned by any member of the group in the 'Unit photos' table."];
unit_photos_edit3Tip=["",spacer+"This option allows each member of the group to edit any records in the 'Unit photos' table, regardless of their owner."];

unit_photos_delete0Tip=["",spacer+"This option prohibits all members of the group from deleting any record in the 'Unit photos' table."];
unit_photos_delete1Tip=["",spacer+"This option allows each member of the group to delete only his own records in the 'Unit photos' table."];
unit_photos_delete2Tip=["",spacer+"This option allows each member of the group to delete any record owned by any member of the group in the 'Unit photos' table."];
unit_photos_delete3Tip=["",spacer+"This option allows each member of the group to delete any records in the 'Unit photos' table."];

/*
	Style syntax:
	-------------
	[TitleColor,TextColor,TitleBgColor,TextBgColor,TitleBgImag,TextBgImag,TitleTextAlign,
	TextTextAlign,TitleFontFace,TextFontFace, TipPosition, StickyStyle, TitleFontSize,
	TextFontSize, Width, Height, BorderSize, PadTextArea, CoordinateX , CoordinateY,
	TransitionNumber, TransitionDuration, TransparencyLevel ,ShadowType, ShadowColor]

*/

toolTipStyle=["white","#00008B","#000099","#E6E6FA","","images/helpBg.gif","","","","\"Trebuchet MS\", sans-serif","","","","3",400,"",1,2,10,10,51,1,0,"",""];

applyCssFilter();

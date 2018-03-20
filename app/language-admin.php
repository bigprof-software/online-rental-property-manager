<?php

	// IMPORTANT:
	// ==========
	// When translating, only translate the strings that are
	// TO THE RIGHT OF the equal sign (=).
	//
	// Do NOT translate the strings between square brackets ([]) or <>
	//
	// Also, leave the text between < and > untranslated.
	//
	// =====================================================

	// incHeader.php
	$Translation['membership management'] = "Membership Management";
	$Translation['password mismatch'] = "Password doesn\'t match.";
	$Translation['error'] = "Error";
	$Translation['invalid email'] = "Invalid Email Address";
	$Translation['sending mails'] = "Sending mails might take some time. Please don't close this page until you see the 'Done' message.";
	$Translation['complete step 4'] = "Please complete step 4 by selecting the member you want to transfer records to.";
	$Translation['info'] = "Info";
	$Translation['sure move member'] = 'Are you sure you want to move member \'<MEMBER>\' and his data from group \'<OLDGROUP>\' to group \'<NEWGROUP>\'?';
	$Translation['sure move data of member'] = 'Are you sure you want to move data of member \'<OLDMEMBER>\' from group \'<OLDGROUP>\' to member \'<NEWMEMBER>\' from group \'<NEWGROUP>\'?';
	$Translation['sure move all members'] = 'Are you sure you want to move all members and data from group \'<OLDGROUP>\' to group \'<NEWGROUP>\'?';
	$Translation['sure move data of all members'] = 'Are you sure you want to move data of all members of group \'<OLDGROUP>\' to member \'<MEMBER>\' from group \'<NEWGROUP>\'?';
	$Translation['toggle navigation'] = "Toggle navigation";
	$Translation['admin area'] = "Admin Area";
	$Translation['groups'] = "Groups";
	$Translation['view groups'] = "View Groups";
	$Translation['add group'] = "Add Group";
	$Translation['edit anonymous permissions'] = "Edit Anonymous Permissions";
	$Translation['members'] = "Members";
	$Translation['view members'] = "View Members";
	$Translation['add member'] = "Add Member";
	$Translation["view members' records"] = "View Members' Records";  
	$Translation["utilities"] = "Utilities"; 
	$Translation["admin settings"] = "Admin Settings"; 
	$Translation["rebuild thumbnails"] = "Rebuild Thumbnails"; 
	$Translation['rebuild fields'] = "Rebuild fields";
	$Translation['import CSV'] = "Import CSV data";
	$Translation['batch transfer'] = "Batch Transfer Wizard";
	$Translation['mail all users'] = "Mail All Users";
	$Translation['AppGini forum'] = "AppGini Community Forum";
	$Translation["user's area"] = 'User\'s area';
	$Translation["sign out"] = "Sign out";
	$Translation["attention"] = "Attention!";
	$Translation['security risk admin'] = 'You are using the default admin username and password. This is a huge security risk. Please change at least the admin password from the <a href="pageSettings.php">Admin Settings</a> page <em>immediately</em>.';
	$Translation['security risk'] = 'You are using the default admin password. This is a huge security	risk. Please change the admin password from the <a href="pageSettings.php">Admin Settings</a> page <em>immediately</em>.' ;
	$Translation['plugins'] = 'Plugins';

	//pageAssignOwners.php
	$Translation["assigned table records to group"] = "Assigned <NUMBER> records of table '<TABLE>' to group '<GROUP>'";
	$Translation["assigned table records to group and member"] = "Assigned <NUMBER> records of table '<TABLE>' to group '<GROUP>' , member '<MEMBERID>'";
	$Translation['data ownership assign'] = "Assign ownership to data that has no owners";
	$Translation['records ownership done'] = "All records in all tables have owners now.<br>Back to <a href='pageHome.php'>Admin homepage</a>.";
	$Translation['select group'] = "Select group";
	$Translation['data ownership'] = "Sometimes, you might have tables with data that were entered before implementing this AppGini membership management system, or entered using other applications unaware of AppGini ownership system. This data currently has no owners. This page allows you to assign owner groups and owner members to this data.";
	$Translation["table"] = "Table";
	$Translation["records with no owners"] = "Records with no owners";
	$Translation["new owner group"] = "New owner group";
	$Translation["new owner member"] = "New owner member*";	
	$Translation["cancel"] = "Cancel";
	$Translation["assign new owners"] = "Assign new owners";
	$Translation["please wait"] = "Please wait ...";	
	$Translation["if no owner member assigned"] = '* If you assign no owner member here, you can still use the <a href="pageTransferOwnership.php">Batch Transfer Wizard</a> later to do so.';
	
	//pageDeleteGroup.php
	$Translation["can not delete group remove members"] = 'Can\'t delete this group. Please remove members first.';
	$Translation["can not delete group transfer records"] = 'Can\'t delete this group. Please transfer its data records to another group first..';
	
	//pageEditGroup.php
	$Translation["group exists error"] = "Error: Group name already exists. You must choose a unique group name.";
	$Translation["group not found error"] = "Error: Group not found!";								 	
	$Translation["edit group"] = "Edit Group '<GROUPNAME>'";
	$Translation["add new group"] = "Add New Group";
	$Translation["anonymous group attention"] = "Attention! This is the anonymous group.";
	$Translation["show tool tips"] = "Show tool tips as mouse moves over options";
	$Translation["group name"] = "Group name";
	$Translation["readonly group name"] = "The name of the anonymous group is read-only here.";
	$Translation["anonymous group name"] = "If you name the group '<ANONYMOUSGROUP>', it will be considered the anonymous group<br>that defines the permissions of guest visitors that do not log into the system.";
	$Translation["description"] = "Description";
	$Translation["allow visitors sign up"] = 'Allow visitors to sign up?';
	$Translation["admin add users"] = "No. Only the admin can add users.";
	$Translation["admin approve users"] = "Yes, and the admin must approve them.";
	$Translation["automatically approve users"] = "Yes, and automatically approve them.";
	$Translation["group table permissions"] = "Table permissions for this group";
	$Translation["no"] = "No";
	$Translation["owner"] = "Owner";
	$Translation["group"] = "Group";
	$Translation["all"] = "All";
	$Translation["insert"] = "Insert";
	$Translation["view"] = "View";
	$Translation["edit"] = "Edit";
	$Translation["delete"] = "Delete";
	$Translation["save changes"] = "Save changes";
	
	//pageEditMember.php
	$Translation["username error"] = "Error: Username already exists or is invalid. Make sure you provide a username containing 4 to 20 valid characters.";
	$Translation["member not found"] = "Error: Member not found!";
	$Translation["user has special permissions"] = "This user has special permissions that override his group permissions.";
	$Translation["user has group permissions"] = 'This user inherits the <a href="pageEditGroup.php?groupID=<GROUPID>">permissions of his group</a>.';
	$Translation["set user special permissions"] = 'Set special permissions for this user';
	$Translation["sure continue"] = "If you made any changes to this member and did not save them yet, they will be lost if you continue. Are you sure you want to continue?";
	$Translation["edit member"] = "Edit Member <MEMBERID>" ;
	$Translation["add new member"] = "Add New Member";
	$Translation["anonymous guest member"] = "Attention! This is the anonymous (guest) member.";
	$Translation["admin member"] = 'Attention! This is the admin member. You can\'t change the username, password or email of this member here, but you can do so in the <a href="pageSettings.php">admin settings</a> page.';
	$Translation["member username"] = "Member username";
	$Translation["check availability"] = "Check availability";
	$Translation["read only username"] = "The username of the guest member is read-only.";
	$Translation["password"] = "Password";
	$Translation["change password"] = "Type a password only if you want to change this member's<br>password. Otherwise, leave this field empty.";
	$Translation["confirm password"] = "Confirm password";
	$Translation["email"] = "Email";
	$Translation["approved"] = "Approved?";
	$Translation["banned"] = "Banned?";
	$Translation["comments"] = "Comments";
	$Translation["back to members"] = "Back to members";
	$Translation["member added"] = "Member <USERNAME> added successfully";
	
	//pageEditMemberPermissions.php
	$Translation["member permissions saved"] = "Member permissions have been saved successfully.";
	$Translation["member permissions reset"] = "Member permissions have been reset to the same as his group.";
	$Translation["user table permissions"] = "Table permissions for user <a href='pageEditMember.php?memberID=<MEMBER>' title='View member details'><MEMBERID></a> of group <a href='pageEditGroup.php?groupID=<GROUPID>'  title='View group details and permissions'><GROUP></a>";
	$Translation["no member permissions"] = 'This member doesn\'t currently have any special permissions. This list shows the permissions of his group.';
	$Translation["reset member permissions"] = "Reset member permissions";
	$Translation["remove special permissions"] = 'This would remove all special permissions of this user and he will have the same permissions as his group. Are you sure you want to do that?';
	
	//pageEditOwnership.php
	$Translation["invalid table"] = "Invalid table.";
	$Translation["invalid primary key"] = "Invalid primary key value";
	$Translation["record not found"] = "Record not found ... if it was imported externally, try assigning an owner from the admin area.";
	$Translation["invalid username"] = "Invalid username";
	$Translation["record not found error"] = "Error: Record not found!";
	$Translation["edit Record Ownership"] = "Edit Record Ownership";
	$Translation["owner group"] = "Owner group";
	$Translation["view all records by group"] = "View all records by this group";
	$Translation["owner member"] = "Owner member";
	$Translation["view all records by member"] = "View all records by this member";
	$Translation["switch record ownership"] = "If you want to switch ownership of this record to a member of another group, you must change the owner group and save changes first.";
	$Translation["record created on"] = "Record created on";
	$Translation["record modified on"] = "Record modified on";
	$Translation["view all records of table"] = "View all records of this table";
	$Translation["record data"] = "Record data";
	$Translation["print"] = "Print";
	$Translation["could not retrieve field list"] = "Couldn't retrieve field list from '<TABLENAME>'";
	$Translation["field name"] = "Field name";
	$Translation["value"] = "Value";
	
	//pageHome.php
	$Translation["visitor sign up"] = '<a href="../membership_signup.php" target="_blank">Visitor sign up</a> is disabled because there are no groups where visitors can sign up currently. To enable visitor sign-up, set at least one group to allow visitor sign-up.';
	$Translation["table data without owner"] = 'You have data in one or more tables that doesn\'t have an owner. To assign an owner group for this data, <a href="pageAssignOwners.php">click here</a>.';
	$Translation["membership management homepage"] = "Membership Management Homepage";
	$Translation["newest updates"] = "Newest Updates";
	$Translation["view record details"] = "View record details";
	$Translation["newest entries"] = "Newest Entries";
	$Translation["available add-ons"] = "Available add-ons";
	$Translation["more info"] = "More info";
	$Translation["close"] = "Close";
	$Translation["view add-ons"] = "View all add-ons";
	$Translation["top members"] = "Top Members";
	$Translation["edit member details"] = "Edit member details";
	$Translation["view member records"] = "View member's data records";
	$Translation["records"] = "records";
	$Translation["members stats"] = "Members Stats";
	$Translation["total groups"] = "Total groups";
	$Translation["active members"] = "Active members";
	$Translation["view active members"] = "View active members";
	$Translation["members awaiting approval"] = "Members awaiting approval";
	$Translation["view members awaiting approval"] = "View members awaiting approval";
	$Translation["banned members"] = "Banned members";
	$Translation["view banned members"] = "View banned members";
	$Translation["total members"] = "Total members";
	$Translation["view all members"] = "View all members";
	$Translation["BigProf tweets"]  = "Tweets By BigProf Software";
	$Translation["follow BigProf"] = "Follow @bigprof";
	$Translation["loading bigprof feed"] = "Loading @bigprof feed ...";
	$Translation["remove feed"] = "Remove this feed";
	
	//pageMail.php
	$Translation["can not send mail"] = "You can not send emails currently. The configured sender email address is not valid.	Please <a href='pageSettings.php'>correct it first</a> then try again.";
	$Translation["all groups"] = "All groups";
	$Translation["no recipient"] = "Couldn't find recipient. Please make sure you provide a valid recipient.";
	$Translation["invalid subject line"] = "Invalid subject line.";
	$Translation["no recipient found"] = "Couldn't find any recipients. Please make sure you provide a valid recipient.";
	$Translation["mail queue not saved"] = "Couldn't save mail queue. Please make sure the directory '<CURRDIR>' is writeable (chmod 755 or chmod 777).";
	$Translation["send mail"]  = "Send mail message to a member/group";
	$Translation["send mail to all members"] = "You are sending an email to all members. This could take a lot of time and affect your server performance. If you have a huge number of members, we don't recommend sending an email to all of them at once.";
	$Translation["from"] = "From";
	$Translation["change setting"] = "Change this setting";
	$Translation["to"] = "To";
	$Translation["subject"] = "Subject";
	$Translation["message"] = "Message";
	$Translation["send message"] = "Send Message";
	
	//pagePrintRecord.php
	$Translation["record details"] = "Membership Management -- Record details";
	$Translation['table name'] = "Table: <TABLENAME>";
	
	//pageRebuildFields.php
	$Translation['create or update table'] = "An attempt to <ACTION> the field <i><FIELD></i> in <i><TABLE></i> table was made by executing this query: <pre><QUERY></pre> Results are shown below.";

	$Translation['view or rebuild fields'] = "View/Rebuild fields";
	$Translation['show deviations only'] = "Show deviations only";
	$Translation['show all fields'] = "Show all fields";
	$Translation['compare tables page'] = "This page compares the tables and fields structure/schema as designed in AppGini to the actual database structure and allows you to fix any deviations.";
	$Translation['field'] = "Field";
	$Translation['AppGini definition'] = "AppGini definition";
	$Translation['database definition'] = "Current definition in the database";
	$Translation['table name title'] = "<TABLENAME> table";
	$Translation['does not exist'] = "Doesn't exist!";
	$Translation['create field'] = "Create the field by running an ADD COLUMN query.";
	$Translation['create it'] = "Create it";
	$Translation['fix field'] = "Fix the field by running an ALTER COLUMN query so that its definition becomes the same as that in AppGini.";
	$Translation['fix it'] = "Fix it";
	$Translation['field update warning'] = "DANGER!! In some cases, this might lead to data loss, truncation, or corruption. It might be a better idea sometimes to update the field in AppGini to match that in the database. Would you still like to continue?";
	$Translation['no deviations found'] = "No deviations found. All fields OK!";
	$Translation['error fields'] = "Found <CREATENUM> non-existing fields that need to be created.<br>Found <UPDATENUM> deviating fields that might need to be updated.";
	
	//pageRebuildThumbnails.php
	$Translation['rebuild thumbnails'] = "Rebuild thumbnails";
	$Translation['thumbnails utility'] = "Use this utility if you have one or more image fields in a table that don't have thumbnails or have thumbnails with incorrect dimensions.";
	$Translation['rebuild thumbnails of table'] = "Rebuild thumbnails of table";
	$Translation['rebuild'] = "Rebuild";
	$Translation['rebuild thumbnails of table_name'] = "Rebuilding thumbnails of '<i><TABLENAME></i>' table ...";
	$Translation['do not close page message'] = "Don't close this page until you see a confirmation message that all thumbnails have been built.";	
	$Translation['rebuild thumbnails status'] = "Status: still rebuilding thumbnails, please wait ...";
	$Translation['building field thumbnails'] =  "Building thumbnails for '<i><FIELD></i>' field...";
	$Translation['done'] = "Done.";
	$Translation['finished status'] = "Status: finished. You can close this page now.";
	
	//pageSender.php
	$Translation['invalid mail queue'] = "Invalid mail queue.";
	$Translation['sending message failed'] = " -- Sending message to '<EMAIL>': Failed.";
	$Translation['sending message ok'] = " -- Sending message to '<EMAIL>': Ok.";
	$Translation['done!'] = "Done!";
	$Translation['close page'] = "You may close this page now or browse to some other page.";
	$Translation['mail log'] = "Mail log:";
	
	//pageSettings.php
	$Translation['invalid security token'] = 'Invalid security token! Please <a href="pageSettings.php">reload the page</a> and try again.';
	$Translation['unique admin username error'] = "The new admin username is already taken by another member. Please make sure the new admin username is unique.";	
	$Translation['unique anonymous username error'] = 'The new anonymous username is already taken by another member. Please make sure the username provided is unique.';
	$Translation['unique anonymous group name error'] = 'The new anonymous group name is already in use by another group. Please make sure the group name provided is unique.';
	$Translation['admin password mismatch'] = '"Admin password" and "Confirm password" don\'t match.';
	$Translation['invalid sender email'] = 'Invalid "Sender email".';
	$Translation['errors occurred'] = "The following errors occured:";
	$Translation['go back'] = 'Please <a href="pageSettings.php" onclick="history.go(-1); return false;">go back</a> to correct the above error(s) and try again.';
	$Translation['record updated automatically'] = "Record updated automatically on <DATE>";
	$Translation['admin settings saved'] = "Admin settings saved successfully.<br>Back to <a href=\"pageSettings.php\">Admin settings</a>.";
	$Translation['admin settings not saved'] = "Admin settings were NOT saved successfully. Failure reason: <ERROR><br>Back to <a href=\"pageSettings.php\" onclick=\"history.go(-1); return false;\">Admin settings</a>.";
	$Translation['show tool tips'] = 'Show tool tips as mouse moves over options';
	$Translation['admin username'] = "Admin username";
	$Translation['admin password'] = "Admin password";
	$Translation['change admin password'] = "Type a password only if you want to change the admin password.";
	$Translation['sender email'] = "Sender email";
	$Translation['sender name and email'] = "Sender name and email are used in the 'To' field when sending";
	$Translation['email messages'] = "email messages to groups or members.";
	$Translation['admin notifications'] = "Admin notifications";
	$Translation['no email notifications'] = "No email notifications to admin.";
	$Translation['member waiting approval'] = "Notify admin only when a new member is waiting for approval.";
	$Translation['new sign-ups'] = "Notify admin for all new sign-ups.";
	$Translation['sender name'] = "Sender name";
	$Translation['members custom field 1'] = "Members custom field 1";
	$Translation['members custom field 2'] = "Members custom field 2";
	$Translation['members custom field 3'] = "Members custom field 3";
	$Translation['members custom field 4'] = "Members custom field 4";
	$Translation['member approval email subject'] = "Member approval<br>email subject";
	$Translation['member approval email subject control'] = "When the admin approves a member, the member is notified by<br>email that he is approved. You can control the subject of the<br>approval email in this box,  and the content in the box below.";
	$Translation['member approval email message'] = "Member approval<br>email message";
	$Translation['MySQL date'] = "MySQL date<br>formatting string";
	$Translation['MySQL reference'] = 'Please refer to <a href="http://dev.mysql.com/doc/refman/5.0/en/date-and-time-functions.html#function_date-format" target="_blank">the MySQL reference</a> for possible formats.';
	$Translation['PHP short date'] = "PHP short date<br>formatting string";
	$Translation['PHP manual'] = 'Please refer to <a href="http://www.php.net/manual/en/function.date.php" target="_blank">the PHP manual</a> for possible formats.'; 
	$Translation['PHP long date'] = "PHP long date<br>formatting string";
	$Translation['groups per page'] = "Groups per page";
	$Translation['members per page'] = "Members per page";
	$Translation['records per page'] = "Records per page";
	$Translation['default sign-up mode'] = "Default sign-up mode<br>for new groups";
	$Translation['no sign-up allowed'] = "No sign-up allowed. Only the admin can add members.";
	$Translation['admin approve members'] = "Sign-up allowed, but the admin must approve members.";
	$Translation['automatically approve members'] = "Sign-up allowed, and automatically approve members.";
	$Translation['anonymous group'] = "Name of the anonymous<br>group";
	$Translation['anonymous user name'] = "Name of the anonymous<br>user";
	$Translation['hide twitter feed'] = "Hide Twitter feed in admin homepage?";
	$Translation['twitter feed'] = "Our Twitter feed helps keep you informed of our latest news, useful resources, new releases, and many other helpful tips.";
	
	//pageTransferOwnership.php
	$Translation['invalid source member'] = "Invalid source member selected.";
	$Translation['invalid destination member'] = "Invalid destination member selected.";
	$Translation['moving member'] = "Moving member '<MEMBERID>' and his data from group '<SOURCEGROUP>' to group '<DESTINATIONGROUP>' ...";
	$Translation['data records transferred'] = "Member '<MEMBERID>' now belongs to group '<NEWGROUP>'. Data records transferred: <DATARECORDS>.";
	$Translation['moving data'] = "Moving data of member '<SOURCEMEMBER>' from group '<SOURCEGROUP>' to member '<DESTINATIONMEMBER>' from group '<DESTINATIONGROUP>' ...";
	$Translation['member records status'] = "Member '<SOURCEMEMBER>' of group '<SOURCEGROUP>' had <DATABEFORE> data records. <TRANSFERSTATUS> to member '<DESTINATIONMEMBER>' of group '<DESTINATIONGROUP>'.";
	$Translation['moving all group members'] = "Moving all members and data of group '<SOURCEGROUP>' to group '<DESTINATIONGROUP>' ...";
	$Translation['failed transferring group members'] = "Operation failed. No members were transferred from group '<SOURCEGROUP>' to '<DESTINATIONGROUP>'.";
	$Translation['group members transferred'] = "All members of group '<SOURCEGROUP>' now belong to '<DESTINATIONGROUP>'. ";
	$Translation['failed transfer data records'] = "However, data records failed to transfer.";
	$Translation['data records were transferred'] = "<DATABEFORE> data records were transferred.";
	$Translation['moving group data to member'] = "Moving data of all members of group '<SOURCEGROUP>' to member '<DESTINATIONMEMBER>' from group '<DESTINATIONGROUP>' ...";
	$Translation['moving group data to member status'] = "<NUMBER> record(s) were transferred from group '<SOURCEGROUP>' to member '<DESTINATIONMEMBER>' of group '<DESTINATIONGROUP>'";
	$Translation['status'] = "STATUS:";
	$Translation['batch transfer link'] = 'To repeat the same batch transfer again later you can <a href= "pageTransferOwnership.php?sourceGroupID=<SOURCEGROUP>&amp;sourceMemberID=<SOURCEMEMBER>&amp;destinationGroupID=<DESTINATIONGROUP>&amp;destinationMemberID=<DESTINATIONMEMBER>&amp;moveMembers=<MOVEMEMBERS>">bookmark or copy this link</a>.';
	$Translation['ownership batch transfer'] = "Batch Transfer Of Ownership";
	$Translation['step 1'] = "STEP 1:";
	$Translation['batch transfer wizard'] = "The batch transfer wizard allows you to transfer data records of one or all members of a group (the <i>source group</i>) to a member of another group (the <i>destination member</i> of the <i>destination group</i>)";
	$Translation['source group'] = "Source group";
	$Translation['update'] = "Update";
	$Translation['next step'] = "Next Step";
	$Translation['group statistics'] = "This group has <MEMBERS> members, and <RECORDS> data records.";
	$Translation['step 2'] = "STEP 2:";
	$Translation['source member message'] = "The source member could be one member or all members of the source group.";
	$Translation['source member'] = "Source member";
	$Translation['all group members'] = "All members of '<GROUPNAME>'";
	$Translation['member statistics'] = "This member has <RECORDS> data records.";
	$Translation['step 3'] = "STEP 3:";
	$Translation['destination group message'] = "The destination group could be the same or different from the source group. Only groups that have members are listed below.";
	$Translation['destination group'] = "Destination group";
	$Translation['step 4'] = "STEP 4:";
	$Translation['destination member message'] = "The destination member will be the new owner of the data records of the source	member.";
	$Translation['destination member'] = "Destination member";
	$Translation['begin transfer'] = "Begin Transfer";	
	$Translation['move records'] = "You could either move records from the source member(s) to a member in the destination group, or move the source member(s), together with their data records to the destination group.";
	$Translation['move data records to member'] = "Move data records to this member:";
	$Translation['move source member to group'] = "Move source member(s) and all his/their data records to the '<GROUPNAME>' group.";
	
	//pageUploadCSV.php
	$Translation['file not found error'] = "Error: File '<FILENAME>' not found.";
	$Translation['preview and confirm CSV data'] = "Preview the CSV data then confirm to import it ...";
	$Translation['display csv file rows'] = "Displaying the first 10 rows of the CSV file ...";
	$Translation['change CSV settings'] = 'Change CSV settings';
	$Translation['import CSV data'] = 'Confirm and import CSV data &gt;';
	$Translation['apply CSV settings'] = 'Apply CSV Settings';
	$Translation['importing CSV data'] = 'Importing CSV data ...';
	$Translation['start at estimated record'] = "Starting at record <RECORDNUMBER> of <RECORDS> total estimated records ...";
	$Translation['table backed up'] = "Table '<TABLE>' backed up as '<TABLENAME>'.";
	$Translation['table backup not done'] = "Table '<TABLE>' is empty, so no backup was done.";
	$Translation['importing batch'] = 'Importing batch <BATCH> of <BATCHNUM>: ';
	$Translation['ok'] = 'Ok';
	$Translation['records inserted or updated successfully'] = "<RECORDS> records inserted/updated in <SECONDS> seconds.";
	$Translation['mission accomplished'] = "Mission accomplished!";
	$Translation['assign a records owner'] = "Assign an owner to the imported records &gt;";
	$Translation['please wait and do not close'] = "Please wait and don't close this page ...";
	$Translation['hide advanced options'] = "Hide advanced options";
	$Translation['show advanced options'] = "Show advanced options";
	$Translation['import CSV to database'] = "Import a CSV file to the database";
	$Translation['import CSV to database page'] = "This page allows you to upload a CSV file (for example, one generated from MS Excel) and import it to one of the tables of the database. This makes it so easy to bulk-populate the database with data from other sources rather than manually entering every single record.";
	$Translation['populate table from CSV'] = "This is the table that you want to populate with data from the CSV file.";
	$Translation['CSV file'] = "CSV file";
	$Translation['preview CSV data'] = "Preview CSV data &gt;";
	$Translation['no table name provided'] = "No table name provided.";
	$Translation['can not open CSV'] = "Can't open csv file '<FILENAME>'.";
	$Translation['empty CSV file'] = "The csv file '<FILENAME>' is empty.";		
	$Translation['no CSV file data'] = "The csv file '<FILENAME>' has no data to read." ;
	$Translation['field separator'] = "Field separator";
	$Translation['default comma'] = "The default is comma (,)";
	$Translation['field delimiter'] = "Field delimiter";
	$Translation['default double-quote'] = 'The default is double-quote (")';
	$Translation['maximum characters per line'] = "Maximum characters per line";
	$Translation['trouble importing CSV'] = "If you have trouble importing the CSV file, try increasing this value.";
	$Translation['ignore lines number'] = "Number of lines to ignore";
	$Translation['skip lines number'] = "Change this value if you want to skip a specific number of lines in the CSV file.";
	$Translation['first line field names'] = "The first line of the file contains field names";
	$Translation['field names must match'] = "Field names must <b>exactly</b> match those in the database.";
	$Translation['update table records'] = "Update table records if their primary key values match those in the CSV file.";
	$Translation['ignore CSV table records'] = "If not checked, records in the CSV file having the same primary key values as those in the table <b>will be ignored</b>";
	$Translation['back up the table'] = "Back up the table before importing CSV data into it.";
	
	//pageViewGroups.php
	$Translation['no matching results found'] = "No matching results found.";
	$Translation['search groups'] = "Search groups";
	$Translation['find'] = "Find";
	$Translation['reset'] = "Reset";
	$Translation['members count'] = "Members count";
	$Translation['Edit group'] = "Edit group";
	$Translation['confirm delete group'] = "Are you sure you want to completely delete this group?";
	$Translation['delete group'] = "Delete group";
	$Translation['view group records'] = "View group records";
	$Translation['view group members'] = "View group members";
	$Translation['send message to group'] = "Send message to group";
	$Translation['previous'] = "Previous";
	$Translation['displaying groups'] = "Displaying groups <GROUPNUM1> to <GROUPNUM2> of <GROUPS>";
	$Translation['next'] = "Next";
	$Translation['key'] = "Key:";	
	$Translation['edit group details'] = "Edit group details and permissions.";
	$Translation['add member to group'] = "Add a new member to group.";
	$Translation['view data records'] = "View all data records entered by the group's members.";
	$Translation['list group members'] = "List all members of a group.";
	$Translation['send email to all members'] = "Send an email message to all members of a group.";
	
	//pageViewMembers.php
	$Translation['search members'] = "Search members <SEARCH> in <HTMLSELECT>";
	$Translation['all fields'] = "All fields";
	$Translation['any'] = "Any";
	$Translation['waiting approval'] = "Waiting approval";
	$Translation['active'] = "Active";
	$Translation['Banned'] = "Banned";
	$Translation['username'] = "Username";
	$Translation['sign up date'] = "Sign up date";
	$Translation['Status'] = "Status";
	$Translation['Edit member'] = "Edit member";	
	$Translation['sure delete user'] = "Are you sure you want to delete user \'<USERNAME>\'?";
	$Translation['delete member'] = "Delete member";
	$Translation["approve this member"] = "Approve this member";
	$Translation["unban this member"] = "Unban this member";
	$Translation["ban this member"] = "Ban this member";
	$Translation["View member records"] = "View member's records";
	$Translation["send message to member"] = "Send message to member";
	$Translation['displaying members'] = "Displaying members <MEMBERNUM1> to <MEMBERNUM2> of <MEMBERS>";
	$Translation['activate member'] = "Activate new/banned member.";
	$Translation['ban member'] = "Ban (suspend) member.";
	$Translation['view entered member records'] = "View all data records entered by member.";
	$Translation['send email to member'] = "Send an email message to member.";
	
	//pageViewRecords.php
	$Translation['data records'] = "Data Records";
	$Translation['show records'] = "Show records from";
	$Translation['all tables'] = "All tables";
	$Translation['sort records'] = "Sort records by";
	$Translation['date created'] = "Date created";
	$Translation['date modified'] = "Date modified";
	$Translation['newer first'] = "Newer first";
	$Translation['older first'] = "Older first";
	$Translation['created'] = "Created";
	$Translation['modified'] = "Modified";
	$Translation['data'] = "Data";
	$Translation['change record ownership'] = "Change ownership of this record";
	$Translation['sure delete record'] = "Are you sure you want to delete this record?";
	$Translation['delete record'] = "Delete this record";
	$Translation['displaying records'] = "Displaying records <RECORDNUM1> to <RECORDNUM2> of <RECORDS>";

	/* Added in AppGini 5.51 */
	$Translation['maintenance mode admin notification'] = 'Maintenance mode is enabled! You can disable it from the admin home page.';
	$Translation['maintenance mode message'] = 'Maintenance mode message';
	$Translation['maintenance mode'] = 'Maintenance mode';
	$Translation['OFF'] = 'OFF';
	$Translation['ON'] = 'ON';
	$Translation['enable maintenance mode?'] = 'Are you sure you want to enable maintenance mode? Only admin users can access the site in this mode!';
	$Translation['disable maintenance mode?'] = 'Are you sure you want to disable maintenance mode? All users will be able to access the site!';
	
	/* Added in AppGini 5.60 */
	$Translation['csv file upload error'] = 'An error occured while processing the requested CSV file.';
	$Translation['back and retry'] = 'Go back and retry';
	$Translation['upload or choose csv file'] = 'Upload a CSV file or open an existing one';
	$Translation['choose csv upload'] = 'Choose a CSV file to upload';
	$Translation['no file chosen yet'] = 'No file chosen yet';
	$Translation['start upload'] = 'Start upload';
	$Translation['select a table'] = 'Select a table';
	$Translation['error reading csv data'] = 'An error occured while reading CSV data. Try resetting/adjusting the CSV settings.';
	$Translation['belongs to'] = 'Belongs to';
	$Translation['skip column'] = 'Skip this column';
	$Translation['connection failed retrying'] = 'Connection failed. Retrying in <SECONDS> seconds ...';
	$Translation['connection failed timeout'] = 'Connection timed out. Retry later.';
	$Translation['sure delete csv'] = 'Are you sure you want to delete the CSV file [CSVFILE] from the server?';
	$Translation['invalid csv file selected'] = 'Invalid file chosen. Must be a CSV file.';
	$Translation['couldnt delete csv file'] = 'Couldn\'t delete this CSV file.';
	$Translation['error backing up table'] = 'Error: Couldn\'t back up table <TABLE>.';
	$Translation['no columns selected'] = 'Please select at least one column to import and make sure each selected column belongs to a UNIQUE field.';
	$Translation['csrf token expired or invalid'] = 'Oops! Something went wrong with this page. Please go back and retry.';
	$Translation['back to groups'] = 'Back to groups';
	$Translation['member updated'] = "Member <USERNAME> updated successfully";
	$Translation['fix errors before submitting'] = 'Please fix the highlighted errors before submitting the page!';

	/* Added in AppGini 5.62 */
	$Translation['mail_function'] = 'Method of sending emails';
	$Translation['smtp_server'] = 'SMTP server';
	$Translation['smtp_encryption'] = 'SMTP encryption';
	$Translation['smtp_port'] = 'SMTP port';
	$Translation['smtp_port_hint'] = 'Typical values are 25 (for non-encrypted SMTP), 465 (used in many cases with SSL encryption) or 587 (typical with TLS encryption)';
	$Translation['smtp_user'] = 'SMTP username';
	$Translation['smtp_pass'] = 'SMTP password';
	$Translation['configure mail settings'] = 'Configure mail settings';
	$Translation['display debugging info'] = 'Display debugging info';
	$Translation['debugging info hint'] = 'Debugging info is useful if you are having trouble sending emails through the configured SMTP server';

	/* Added in AppGini 5.70 */
	$Translation['create backup file'] = 'Create Backup File';
	$Translation['database backups'] = 'Database backups';
	$Translation['no backups found'] = 'No backups found. You can create a new backup by clicking the "Create Backup File" button.';
	$Translation['available backups'] = 'Available backups';
	$Translation['restore backup'] = 'Restore';
	$Translation['delete backup'] = 'Delete';
	$Translation['backup restored'] = 'Backup restored successfully.';
	$Translation['backup deleted'] = 'Backup deleted successfully.';
	$Translation['restore error'] = 'An error occured while restoring backup.';
	$Translation['backup delete error'] = 'An error occured while deleting backup.';
	$Translation['confirm delete backup'] = 'Are you sure you want to delete this backup file?';
	$Translation['confirm restore'] = 'Restoring your database from a backup file would OVERWRITE all existing data AND users/passwords, reverting all to the state when the backup was taken.\n\nDuring the restore process, maintenance mode will be enabled to prevent users from modifying the database, and will be disabled once the restore is finished.\n\nAre you sure you want to continue?';
	$Translation['confirm backup'] = 'During the backup process, maintenance mode will be enabled to prevent users from modifying the database, and will be disabled once the backup is finished.\n\nAre you sure you want to continue?';
	$Translation['cant create backup folder'] = 'Error: Can\'t create or write to admin/backups folder. Please check your folder permissions and ownership or contact your server administrator for help.';
	$Translation['fix all'] = 'FIX ALL FIELDS';
	$Translation['backup before fix'] = 'It\'s highly recommended that you create a database backup first before attempting to make any fixes here.';
	$Translation['about backups'] = 'Backups are performed using mysqldump commandline tool. If no backups are being created, it could be due to the web server not having permission to run mysqldump.';

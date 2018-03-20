<?php

	// IMPORTANT:
	// ==========
	// When translating, only translate the strings that are
	// TO THE RIGHT OF the equal sign (=).
	//
	// Do NOT translate the strings between square brackets ([])
	//
	// Also, leave the text between < and > untranslated.
	//
	// =====================================================
	// PLEASE NOTE:
	// ============
	// When a new version of AppGini is released, new strings
	// might be added to the "defaultLang.php" file. To translate
	// them, simply copy them to this file ("language.php") and 
	// translate them here. Do NOT translate them directly in 
	// the "defaultLang.php" file.
	// =====================================================
		


	// datalist.php
	$Translation['quick search'] = "Quick Search";
	$Translation['records x to y of z'] = "Records <FirstRecord> to <LastRecord> of <RecordCount>";
	$Translation['filters'] = "Filters";
	$Translation['filter'] = "Filter";
	$Translation['filtered field'] = "Filtered field";
	$Translation['comparison operator'] = "Comparison Operator";
	$Translation['comparison value'] = "Comparison Value";
	$Translation['and'] = "And";
	$Translation['or'] = "Or";
	$Translation['equal to'] = "Equal to";
	$Translation['not equal to'] = "Not equal to";
	$Translation['greater than'] = "Greater than";
	$Translation['greater than or equal to'] = "Greater than or equal to";
	$Translation['less than'] = "Less than";
	$Translation['less than or equal to'] = "Less than or equal to";
	$Translation['like'] = "Like";
	$Translation['not like'] = "Not like";
	$Translation['is empty'] = "Is empty";
	$Translation['is not empty'] = "Is not empty";
	$Translation['apply filters'] = "Apply filters";
	$Translation['save filters'] = "Save and apply filters";
	$Translation['saved filters title'] = "HTML Code For The Applied Filters";
	$Translation['saved filters instructions'] = "Copy the code below and paste it to an HTML file to save the filter you just defined so that you can return to it at any time in the future without having to redefine it. You can save this HTML code on your computer or on any server and access this prefiltered table view through it.";
	$Translation['hide code'] = "Hide this code";
	$Translation['printer friendly view'] = "Printer-friendly view";
	$Translation['save as csv'] = "Download as csv file (comma-separated values)";
	$Translation['edit filters'] = "Edit filters";
	$Translation['clear filters'] = "Clear filters";
	$Translation['order by'] = 'Order by';
	$Translation['go to page'] = 'Go to page:';
	$Translation['none'] = 'None';
	$Translation['Select all records'] = 'Select all records';
	$Translation['With selected records'] = 'With selected records';
	$Translation['Print Preview Detail View'] = 'Print Preview Detail View';
	$Translation['Print Preview Table View'] = 'Print Preview Table View';
	$Translation['Print'] = 'Print';
	$Translation['Cancel Printing'] = 'Cancel Printing';
	$Translation['Cancel Selection'] = 'Cancel Selection';
	$Translation['Maximum records allowed to enable this feature is'] = 'Maximum records allowed to enable this feature is';
	$Translation['No matches found!'] = 'No matches found!';
	$Translation['Start typing to get suggestions'] = 'Start typing to get suggestions.';

	// _dml.php
	$Translation['are you sure?'] = 'Are you sure you want to delete this record?';
	$Translation['add new record'] = 'Add new record';
	$Translation['update record'] = 'Update record';
	$Translation['delete record'] = 'Delete record';
	$Translation['deselect record'] = 'Deselect record';
	$Translation["couldn't delete"] = 'Could not delete the record due to the presence of <RelatedRecords> related record(s) in table [<TableName>]';
	$Translation['confirm delete'] = 'This record has <RelatedRecords> related record(s) in table [<TableName>]. Do you still want to delete it? <Delete> &nbsp; <Cancel>';
	$Translation['yes'] = 'Yes';
	$Translation['no'] = 'No';
	$Translation['pkfield empty'] = ' field is a primary key field and cannot be empty.';
	$Translation['upload image'] = 'Upload new file ';
	$Translation['select image'] = 'Select an image ';
	$Translation['remove image'] = 'Remove file';
	$Translation['month names'] = 'January,February,March,April,May,June,July,August,September,October,November,December';
	$Translation['field not null'] = 'You cannot leave this field empty.';
	$Translation['*'] = '*';
	$Translation['today'] = 'Today';
	$Translation['Hold CTRL key to select multiple items from the above list.'] = 'Hold CTRL key to select multiple items from the above list.';
	$Translation['Save New'] = 'Save New';
	$Translation['Save As Copy'] = 'Save As Copy';
	$Translation['Deselect'] = 'Cancel';
	$Translation['Add New'] = 'Add New';
	$Translation['Delete'] = 'Delete';
	$Translation['Cancel'] = 'Cancel';
	$Translation['Print Preview'] = 'Print Preview';
	$Translation['Save Changes'] = 'Save Changes';
	$Translation['CSV'] = 'Save CSV';
	$Translation['Reset Filters'] = 'Show All';
	$Translation['Find It'] = 'Find It';
	$Translation['Previous'] = 'Previous';
	$Translation['Next'] = 'Next';
	$Translation['Back'] = 'Back';

	// lib.php
	$Translation['select a table'] = "Jump to ...";
	$Translation['homepage'] = "Homepage";
	$Translation['error:'] = "Error:";
	$Translation['sql error:'] = "SQL error:";
	$Translation['query:'] = "Query:";
	$Translation['< back'] = "Back";
	$Translation["if you haven't set up"] = "If you haven't set up the database yet, you can do so by clicking <a href='setup.php'>here</a>.";
	$Translation['file too large']="Error: The file you uploaded exceeds the maximum allowed size of <MaxSize> KB";
	$Translation['invalid file type']="Error: This file type is not allowed. Only <FileTypes> files can be uploaded";

	// setup.php
	$Translation['goto start page'] = "Back to start page";
	$Translation['no db connection'] = "Couldn't establish a database connection.";
	$Translation['no db name'] = "Couldn't access the database named '<DBName>' on this server.";
	$Translation['provide connection data'] = "Please provide the following data to connect to the database:";
	$Translation['mysql server'] = "MySQL server (host)";
	$Translation['mysql username'] = "MySQL Username";
	$Translation['mysql password'] = "MySQL password";
	$Translation['mysql db'] = "Database name";
	$Translation['connect'] = "Connect";
	$Translation['couldnt save config'] = "Couldn't save connection data into 'config.php'.<br />Please make sure that the folder:<br />'".dirname(__FILE__)."'<br />is writable (chmod 775 or chmod 777).";
	$Translation['setup performed'] = "Setup already performed on";
	$Translation['delete md5'] = "If you want to force setup to run again, you should first delete the file 'setup.md5' from this folder.";
	$Translation['table exists'] = "Table <b><TableName></b> exists, containing <NumRecords> records.";
	$Translation['failed'] = "Failed";
	$Translation['ok'] = "Ok";
	$Translation['mysql said'] = "MySQL said:";
	$Translation['table uptodate'] = "Table is up-to-date.";
	$Translation['couldnt count'] = "Couldn't count records of table <b><TableName></b>";
	$Translation['creating table'] = "Creating table <b><TableName></b> ... ";

	// separateDVTV.php
	$Translation['please wait'] = "Please wait";

	// _view.php
	$Translation['tableAccessDenied']="Sorry! You don't have permission to access this table. Please contact the admin.";

	// incCommon.php
	$Translation['not signed in']="You are not signed in";
	$Translation['sign in']="Sign In";
	$Translation['signed as']="Signed in as";
	$Translation['sign out']="Sign Out";
	$Translation['admin setup needed']="Admin setup was not performed. Please log in to the <a href=admin/>admin control panel</a> to perform the setup.";
	$Translation['db setup needed']="Program setup was not performed yet. Please log in to the <a href=setup.php>setup page</a> first.";
	$Translation['new record saved']="The new record has been saved successfully.";
	$Translation['record updated']="The changes have been saved successfully.";

	// index.php
	$Translation['admin area']="Admin Area";
	$Translation['login failed']="Your previous login attempt failed. Try again.";
	$Translation['sign in here']="Sign In Here";
	$Translation['remember me']="Remember me";
	$Translation['username']="Username";
	$Translation['password']="Password";
	$Translation['go to signup']="Don't have a username? <br />&nbsp; <a href=membership_signup.php>Sign up here</a>";
	$Translation['forgot password']="Forgot your password? <a href=membership_passwordReset.php>Click here</a>";
	$Translation['browse as guest']="<a href=index.php>Continue browsing as a guest</a>";
	$Translation['no table access']="You don't have enough permissions to access any page here. Please sign in first.";
	$Translation['signup']="Sign up";

	// checkMemberID.php
	$Translation['user already exists']="Username '<MemberID>' already exists. Try another username.";
	$Translation['user available']="Username '<MemberID>' is available and you can take it.";
	$Translation['empty user']="Please type a username in the box first then click 'Check availability'.";

	// membership_thankyou.php
	$Translation['thanks']="Thank you for signing up!";
	$Translation['sign in no approval']="If you have chosen a group that doesn't require admin approval, you can sign in right now <a href=index.php?signIn=1>here</a>.";
	$Translation['sign in wait approval']="If you have chosen a group that requires admin approval, please wait for an email confirming your approval.";

	// membership_signup.php
	$Translation['username empty']="You must provide a username. Please go back and type a username";
	$Translation['password invalid']="You must provide a password of 4 characters or more, without spaces. Please go back and type a valid password";
	$Translation['password no match']="Password doesn't match. Please go back and correct the password";
	$Translation['username exists']="Username already exists. Please go back and choose a different username.";
	$Translation['email invalid']="Invalid email address. Please go back and correct your email address.";
	$Translation['group invalid']="Invalid group. Please go back and correct the group selection.";
	$Translation['sign up here']="Sign Up Here!";
	$Translation['registered? sign in']="Already registered? <a href=index.php?signIn=1>Sign in here</a>.";
	$Translation['sign up disabled']="Sorry! Sign-up is temporarily disabled by admin. Try again later.";
	$Translation['check availability']="Check if this username is available";
	$Translation['confirm password']="Confirm Password";
	$Translation['email']="Email Address";
	$Translation['group']="Group";
	$Translation['groups *']="If you choose to sign up to a group marked with an asterisk (*), you won't be able to log in until the admin approves you. You'll receive an email when you are approved.";
	$Translation['sign up']="Sign Up";

	// membership_passwordReset.php
	$Translation['password reset']="Password Reset Page";
	$Translation['password reset details']="Enter your username or email address below. We'll then send a special link to your email. After you click on that link, you'll be asked to enter a new password.";
	$Translation['password reset subject']="Password reset instructions";
	$Translation['password reset message']="Dear member, \n If you have requested to reset/change your password, please click on this link: \n <ResetLink> \n\n If you didn't request a password reset/change, please ignore this message. \n\n Regards.";
	$Translation['password reset ready']="An email with password reset instructions has been sent to your registered email address. Please follow the instructions in that email message.<br /><br />If you don't receive this email within 5 minutes, try resetting your password again, and make sure you enter a correct username or email address.";
	$Translation['password reset invalid']="Invalid username or password. <a href=membership_passwordReset.php>Try again</a>, or go <a href=index.php>back to homepage</a>.";
	$Translation['password change']="Password Change Page";
	$Translation['new password']="New password";
	$Translation['password reset done']="Your password was changed successfully. You can <a href=index.php?signOut=1>log in with the new password here</a>.";

    $Translation['Loading ...']='Loading ...';
    $Translation['No records found']='No records found';
    $Translation['You can add children records after saving the main record first']='You can add child records after saving the main record first';

    $Translation['ascending'] = 'Ascending';
    $Translation['descending'] = 'Descending';
    $Translation['then by'] = 'Then by';

	// membership_profile
	$Translation['Legend'] = 'Legend';
	$Translation['Table'] = 'Table';
	$Translation['Edit'] = 'Edit';
	$Translation['View'] = 'View';
	$Translation['Only your own records'] = 'Only your own records';
	$Translation['All records owned by your group'] = 'All records owned by your group';
	$Translation['All records'] = 'All records';
	$Translation['Not allowed'] = 'Not allowed';
	$Translation['Your info'] = 'Your info';
	$Translation['Hello user'] = 'Hello %s!';
	$Translation['Your access permissions'] = 'Your access permissions';
	$Translation['Update profile'] = 'Update profile';
	$Translation['Update password'] = 'Update password';
	$Translation['Change your password'] = 'Change your password';
	$Translation['Old password'] = 'Old Password';
	$Translation['Password strength: weak'] = 'Password strength: weak';
	$Translation['Password strength: good'] = 'Password strength: good';
	$Translation['Password strength: strong'] = 'Password strength: strong';
	$Translation['Wrong password'] = 'Wrong password';
	$Translation['Your profile was updated successfully'] = 'Your profile was updated successfully';
	$Translation['Your password was changed successfully'] = 'Your password was changed successfully';
	$Translation['Your IP address'] = 'Your IP address';
	
	/* Added in AppGini 4.90 */
	$Translation['Records to display'] = 'Records to display';
	
	/* Added in AppGini 5.10 */
	$Translation['Setup Data'] = 'Setup Data';
	$Translation['Database Information'] = 'Database Information';
	$Translation['Admin Information'] = 'Admin Information';
	$Translation['setup intro 1'] = 'There doesn\'t seem to be a configuration file. This is necessary for the application to work.<br><br>This setup page will help you create that file. But in some server configurations this might not work. In that case you might need to adjust the folder permissions, or create the config file manually.';
	$Translation['setup intro 2'] = 'Welcome to your new AppGini application! Before getting started, we need some information about your database. You will need to know the following before proceeding:<ol><li>Database server (host)</li><li>Database name</li><li>Database username</li><li>Database password</li></ol>The above items were probably supplied to you by your web hosting provider. If you do not have this information, then you will need to contact them or refer to their service documentation before you can continue here. If you\'re ready, let\'s start!';
	$Translation['setup finished'] = '<b>Success!</b><br><br>Your AppGini application has been installed. Here are some suggestions to begin using it:';
	$Translation['setup next 1'] = 'Start using your application to add data, or work with existing data, if any.';
	$Translation['setup next 2'] = 'Import existing data into your application from a CSV file.';
	$Translation['setup next 3'] = 'Go to the admin homepage where you can change many other application settings.';
	$Translation['db_name help'] = 'The name of the database you want to run your AppGini application in.';
	$Translation['db_server help'] = '<i>localhost</i> works on most servers. If not, you should be able to get this info from your web hosting provider.';
	$Translation['db_username help'] = 'Your MySQL username';
	$Translation['db_password help'] = 'Your MySQL password';
	$Translation['username help'] = 'Specify the admin username you\'d like to use to access the admin area. Must be four characters or more.';
	$Translation['password help'] = 'Specify a strong password to access the admin area.';
	$Translation['email help'] = 'Enter the email address where you want admin notifications to be sent.';
	$Translation['Continue'] = 'Continue ...';
	$Translation['Lets go'] = 'Let\'s go!';
	$Translation['Submit'] = 'Submit';
	$Translation['Hide'] = 'Hide help';
	$Translation['Database info is correct'] = '&#10003; Database info is correct!';
	$Translation['Database connection error'] = '&#10007; Database connection error!';
	$Translation['The following errors occured'] = 'The following errors occured';
	$Translation['failed to create config instructions'] = 'This is most probably due to folder permissions that are set to prevent creating files by your web server. Don\'t worry! You can still create the config file manually.<br><br>Just paste the following code into a text editor and save the file as "config.php", then upload it using FTP or any other method to the folder %s on your server.';
	$Translation['Only show records having filterer'] = 'Only show records where %s is %s';
	
	/* Added in AppGini 5.20 */
	$Translation['You don\'t have enough permissions to delete this record'] = 'You don\'t have enough permissions to delete this record';
	$Translation['Couldn\'t delete this record'] = 'Couldn\'t delete this record';
	$Translation['The record has been deleted successfully'] = 'The record has been deleted successfully';
	$Translation['Couldn\'t save changes to the record'] = 'Couldn\'t save changes to the record';
	$Translation['Couldn\'t save the new record'] = 'Couldn\'t save the new record';
	
	/* Added in AppGini 5.30 */
	$Translation['More'] = 'More';
	$Translation['Confirm deleting multiple records'] = 'Confirm deleting multiple records';
	$Translation['<n> records will be deleted. Are you sure you want to do this?'] = '<n> records will be deleted. Are you sure you want to do this?';
	$Translation['Yes, delete them!'] = 'Yes, delete them!';
	$Translation['No, keep them.'] = 'No, keep them.';
	$Translation['Deleting record <i> of <n>'] = 'Deleting record <i> of <n>';
	$Translation['Delete progress'] = 'Delete progress';
	$Translation['Show/hide details'] = 'Show/hide details';
	$Translation['Connection error'] = 'Connection error';
	$Translation['Add more actions'] = 'Add more actions';
	$Translation['Update progress'] = 'Update progress';
	$Translation['Change owner'] = 'Change owner';
	$Translation['Updating record <i> of <n>'] = 'Updating record <i> of <n>';
	$Translation['Change owner of <n> selected records to'] = 'Change owner of <n> selected records to';

	/* Added in AppGini 5.40 */
	$Translation['username invalid'] = 'Username <MemberID> already exists or is invalid. Make sure you provide a username containing 4 to 20 valid characters.';
	$Translation['permalink'] = 'Permalink';
	$Translation['invalid provider'] = 'Invalid provider!';
	$Translation['invalid url'] = 'Invalid URL!';
	$Translation['cant retrieve coordinates from url'] = 'Can\'t retrieve coordinates from URL!';

	/* Added in AppGini 5.51 */
	$Translation['maintenance mode admin notification'] = 'Maintenance mode is enabled! You can disable it from the admin home page.';
	$Translation['unique field error'] = 'This value already exists or is invalid. Please make sure to specify a unique valid value.';

	/* Added in AppGini 5.60 */
	$Translation['show all user records from table'] = 'Show all records of this user from "<tablename>" table';
	$Translation['show all group records from table'] = 'Show all records of this group from "<tablename>" table';
	$Translation['email this user'] = 'Email this user';
	$Translation['email this group'] = 'Email this group';
	$Translation['owner'] = 'Owner';
	$Translation['created'] = 'Created';
	$Translation['last modified'] = 'Last modified';
	$Translation['record has no owner'] = 'This record has no assigned owner. You can assign an owner from the admin area.';
	$Translation['admin-only info'] = 'The above info is displayed because you are currently signed in as the super admin. Other users won\'t see this.';
	$Translation['discard changes confirm'] = 'Discard changes to this record?';

	/* Added in AppGini 5.70 */
	$Translation['hide/show columns'] = 'Hide/Show columns';
	$Translation['next column'] = 'Next column';
	$Translation['previous column'] = 'Previous column';
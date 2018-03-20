<?php
	########################################################################
	/*
	~~~~~~ LIST OF FUNCTIONS ~~~~~~
		getTableList() -- returns an associative array of all tables in this application in the format tableName=>tableCaption
		getThumbnailSpecs($tableName, $fieldName, $view) -- returns an associative array specifying the width, height and identifier of the thumbnail file.
		createThumbnail($img, $specs) -- $specs is an array as returned by getThumbnailSpecs(). Returns true on success, false on failure.
		makeSafe($string)
		checkPermissionVal($pvn)
		sql($statment, $o)
		sqlValue($statment)
		getLoggedAdmin()
		checkUser($username, $password)
		logOutUser()
		getPKFieldName($tn)
		getCSVData($tn, $pkValue, $stripTag=true)
		errorMsg($msg)
		redirect($URL, $absolute=FALSE)
		htmlRadioGroup($name, $arrValue, $arrCaption, $selectedValue, $selClass="", $class="", $separator="<br>")
		htmlSelect($name, $arrValue, $arrCaption, $selectedValue, $class="", $selectedClass="")
		htmlSQLSelect($name, $sql, $selectedValue, $class="", $selectedClass="")
		isEmail($email) -- returns $email if valid or false otherwise.
		notifyMemberApproval($memberID) -- send an email to member acknowledging his approval by admin, returns false if no mail is sent
		setupMembership() -- check if membership tables exist or not. If not, create them.
		thisOr($this_val, $or) -- return $this_val if it has a value, or $or if not.
		getUploadedFile($FieldName, $MaxSize=0, $FileTypes='csv|txt', $NoRename=false, $dir='')
		toBytes($val)
		convertLegacyOptions($CSVList)
		getValueGivenCaption($query, $caption)
		undo_magic_quotes($str)
		time24($t) -- return time in 24h format
		time12($t) -- return time in 12h format
		application_url($page) -- return absolute URL of provided page
		is_ajax() -- return true if this is an ajax request, false otherwise
		array_trim($arr) -- recursively trim provided value/array
		is_allowed_username($username, $exception = false) -- returns username if valid and unique, or false otherwise (if exception is provided and same as username, no uniqueness check is performed)
		csrf_token($validate) -- csrf-proof a form
		get_plugins() -- scans for installed plugins and returns them in an array ('name', 'title', 'icon' or 'glyphicon', 'admin_path')
		maintenance_mode($new_status = '') -- retrieves (and optionally sets) maintenance mode status
		html_attr($str) -- prepare $str to be placed inside an HTML attribute
		Request($var) -- class for providing sanitized values of given request variable (->sql, ->attr, ->html, ->url, and ->raw)
		Notification() -- class for providing a standardized html notifications functionality
		sendmail($mail) -- sends an email using PHPMailer as specified in the assoc array $mail( ['to', 'name', 'subject', 'message', 'debug'] ) and returns true on success or an error message on failure
		safe_html($str) -- sanitize HTML strings, and apply nl2br() to non-HTML ones
		get_tables_info($skip_authentication = false) -- retrieves table properties as a 2D assoc array ['table_name' => ['prop1' => 'val', ..], ..]
		getLoggedMemberID() -- returns memberID of logged member. If no login, returns anonymous memberID
		getLoggedGroupID() -- returns groupID of logged member, or anonymous groupID
		getMemberInfo() -- returns an array containing the currently signed-in member's info
		get_group_id($user = '') -- returns groupID of given user, or current one if empty
		prepare_sql_set($set_array, $glue = ', ') -- Prepares data for a SET or WHERE clause, to be used in an INSERT/UPDATE query
		insert($tn, $set_array) -- Inserts a record specified by $set_array to the given table $tn
		update($tn, $set_array, $where_array) -- Updates a record identified by $where_array to date specified by $set_array in the given table $tn
		set_record_owner($tn, $pk, $user) -- Set/update the owner of given record
	~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	*/
	########################################################################
	function get_tables_info($skip_authentication = false){
		static $all_tables = array(), $accessible_tables = array();

		/* return cached results, if found */
		if(($skip_authentication || getLoggedAdmin()) && count($all_tables)) return $all_tables;
		if(!$skip_authentication && count($accessible_tables)) return $accessible_tables;

		/* table groups */
		$tg = array(
			'Leases',
			'Assets/Setup'
		);

		$all_tables = array(
			/* ['table_name' => [table props assoc array] */   
				'applicants_and_tenants' => array(
					'Caption' => 'Applicants and tenants',
					'Description' => 'List of applicants/tenants and their current status. Each tenant might have an application or a lease.',
					'tableIcon' => 'resources/table_icons/account_balances.png',
					'group' => $tg[0],
					'homepageShowCount' => 1
				),
				'applications_leases' => array(
					'Caption' => 'Applications/Leases',
					'Description' => 'This is the application form filled by an applicant to apply for leasing a unit. The application might be changed to a lease when approved.',
					'tableIcon' => 'resources/table_icons/curriculum_vitae.png',
					'group' => $tg[0],
					'homepageShowCount' => 1
				),
				'residence_and_rental_history' => array(
					'Caption' => 'Residence and rental history',
					'Description' => 'Records of the tenant residence and rental history.',
					'tableIcon' => 'resources/table_icons/document_comment_above.png',
					'group' => $tg[0],
					'homepageShowCount' => 1
				),
				'employment_and_income_history' => array(
					'Caption' => 'Employment and income history',
					'Description' => 'Records of the employment and income history of the tenant.',
					'tableIcon' => 'resources/table_icons/cash_stack.png',
					'group' => $tg[0],
					'homepageShowCount' => 1
				),
				'references' => array(
					'Caption' => 'References',
					'Description' => 'List of references for each tenant.',
					'tableIcon' => 'resources/table_icons/application_from_storage.png',
					'group' => $tg[0],
					'homepageShowCount' => 1
				),
				'rental_owners' => array(
					'Caption' => 'Landlords',
					'Description' => 'Listing of landlords/owners of rental properties, and all the properties owned by each.',
					'tableIcon' => 'resources/table_icons/administrator.png',
					'group' => $tg[1],
					'homepageShowCount' => 1
				),
				'properties' => array(
					'Caption' => 'Properties',
					'Description' => 'Listing of all properties. Each property has an owner and consists of one or more rental units.',
					'tableIcon' => 'resources/table_icons/application_home.png',
					'group' => $tg[1],
					'homepageShowCount' => 1
				),
				'property_photos' => array(
					'Caption' => 'Property photos',
					'Description' => '',
					'tableIcon' => 'resources/table_icons/camera_link.png',
					'group' => $tg[1],
					'homepageShowCount' => 0
				),
				'units' => array(
					'Caption' => 'Units',
					'Description' => 'Listing of all units, its details and current status.',
					'tableIcon' => 'resources/table_icons/change_password.png',
					'group' => $tg[1],
					'homepageShowCount' => 1
				),
				'unit_photos' => array(
					'Caption' => 'Unit photos',
					'Description' => '',
					'tableIcon' => 'resources/table_icons/camera_link.png',
					'group' => $tg[1],
					'homepageShowCount' => 1
				)
		);

		if($skip_authentication || getLoggedAdmin()) return $all_tables;

		foreach($all_tables as $tn => $ti){
			$arrPerm = getTablePermissions($tn);
			if($arrPerm[0]) $accessible_tables[$tn] = $ti;
		}

		return $accessible_tables;
	}
	#########################################################
	if(!function_exists('getTableList')){
		function getTableList($skip_authentication = false){
			$arrTables = array(   
				'applicants_and_tenants' => 'Applicants and tenants',
				'applications_leases' => 'Applications/Leases',
				'residence_and_rental_history' => 'Residence and rental history',
				'employment_and_income_history' => 'Employment and income history',
				'references' => 'References',
				'rental_owners' => 'Landlords',
				'properties' => 'Properties',
				'property_photos' => 'Property photos',
				'units' => 'Units',
				'unit_photos' => 'Unit photos'
			);

			return $arrTables;
		}
	}
	########################################################################
	function getThumbnailSpecs($tableName, $fieldName, $view){
		if($tableName=='properties' && $fieldName=='photo' && $view=='tv')
			return array('width'=>250, 'height'=>250, 'identifier'=>'_tv');
		elseif($tableName=='properties' && $fieldName=='photo' && $view=='dv')
			return array('width'=>250, 'height'=>250, 'identifier'=>'_dv');
		elseif($tableName=='property_photos' && $fieldName=='photo' && $view=='tv')
			return array('width'=>100, 'height'=>100, 'identifier'=>'_tv');
		elseif($tableName=='property_photos' && $fieldName=='photo' && $view=='dv')
			return array('width'=>250, 'height'=>250, 'identifier'=>'_dv');
		elseif($tableName=='units' && $fieldName=='photo' && $view=='tv')
			return array('width'=>250, 'height'=>250, 'identifier'=>'_tv');
		elseif($tableName=='units' && $fieldName=='photo' && $view=='dv')
			return array('width'=>250, 'height'=>250, 'identifier'=>'_dv');
		elseif($tableName=='unit_photos' && $fieldName=='photo' && $view=='tv')
			return array('width'=>100, 'height'=>100, 'identifier'=>'_tv');
		elseif($tableName=='unit_photos' && $fieldName=='photo' && $view=='dv')
			return array('width'=>250, 'height'=>250, 'identifier'=>'_dv');
		return FALSE;
	}
	########################################################################
	function createThumbnail($img, $specs){
		$w=$specs['width'];
		$h=$specs['height'];
		$id=$specs['identifier'];
		$path=dirname($img);

		// image doesn't exist or inaccessible?
		if(!$size=@getimagesize($img))   return FALSE;

		// calculate thumbnail size to maintain aspect ratio
		$ow=$size[0]; // original image width
		$oh=$size[1]; // original image height
		$twbh=$h/$oh*$ow; // calculated thumbnail width based on given height
		$thbw=$w/$ow*$oh; // calculated thumbnail height based on given width
		if($w && $h){
			if($twbh>$w) $h=$thbw;
			if($thbw>$h) $w=$twbh;
		}elseif($w){
			$h=$thbw;
		}elseif($h){
			$w=$twbh;
		}else{
			return FALSE;
		}

		// dir not writeable?
		if(!is_writable($path))  return FALSE;

		// GD lib not loaded?
		if(!function_exists('gd_info'))  return FALSE;
		$gd=gd_info();

		// GD lib older than 2.0?
		preg_match('/\d/', $gd['GD Version'], $gdm);
		if($gdm[0]<2)    return FALSE;

		// get file extension
		preg_match('/\.[a-zA-Z]{3,4}$/U', $img, $matches);
		$ext=strtolower($matches[0]);

		// check if supplied image is supported and specify actions based on file type
		if($ext=='.gif'){
			if(!$gd['GIF Create Support'])   return FALSE;
			$thumbFunc='imagegif';
		}elseif($ext=='.png'){
			if(!$gd['PNG Support'])  return FALSE;
			$thumbFunc='imagepng';
		}elseif($ext=='.jpg' || $ext=='.jpe' || $ext=='.jpeg'){
			if(!$gd['JPG Support'] && !$gd['JPEG Support'])  return FALSE;
			$thumbFunc='imagejpeg';
		}else{
			return FALSE;
		}

		// determine thumbnail file name
		$ext=$matches[0];
		$thumb=substr($img, 0, -5).str_replace($ext, $id.$ext, substr($img, -5));

		// if the original image smaller than thumb, then just copy it to thumb
		if($h>$oh && $w>$ow){
			return (@copy($img, $thumb) ? TRUE : FALSE);
		}

		// get image data
		if(!$imgData=imagecreatefromstring(implode('', file($img)))) return FALSE;

		// finally, create thumbnail
		$thumbData=imagecreatetruecolor($w, $h);

		//preserve transparency of png and gif images
		if($thumbFunc=='imagepng'){
			if(($clr=@imagecolorallocate($thumbData, 0, 0, 0))!=-1){
				@imagecolortransparent($thumbData, $clr);
				@imagealphablending($thumbData, false);
				@imagesavealpha($thumbData, true);
			}
		}elseif($thumbFunc=='imagegif'){
			@imagealphablending($thumbData, false);
			$transIndex=imagecolortransparent($imgData);
			if($transIndex>=0){
				$transClr=imagecolorsforindex($imgData, $transIndex);
				$transIndex=imagecolorallocatealpha($thumbData, $transClr['red'], $transClr['green'], $transClr['blue'], 127);
				imagefill($thumbData, 0, 0, $transIndex);
			}
		}

		// resize original image into thumbnail
		if(!imagecopyresampled($thumbData, $imgData, 0, 0 , 0, 0, $w, $h, $ow, $oh)) return FALSE;
		unset($imgData);

		// gif transparency
		if($thumbFunc=='imagegif' && $transIndex>=0){
			imagecolortransparent($thumbData, $transIndex);
			for($y=0; $y<$h; ++$y)
				for($x=0; $x<$w; ++$x)
					if(((imagecolorat($thumbData, $x, $y)>>24) & 0x7F) >= 100)   imagesetpixel($thumbData, $x, $y, $transIndex);
			imagetruecolortopalette($thumbData, true, 255);
			imagesavealpha($thumbData, false);
		}

		if(!$thumbFunc($thumbData, $thumb))  return FALSE;
		unset($thumbData);

		return TRUE;
	}
	########################################################################
	function makeSafe($string, $is_gpc = true){
		if($is_gpc) $string = (get_magic_quotes_gpc() ? stripslashes($string) : $string);
		if(!db_link()){ sql("select 1+1", $eo); }

		// prevent double escaping
		$na = explode(',', "\x00,\n,\r,',\",\x1a");
		$escaped = true;
		$nosc = true; // no special chars exist
		foreach($na as $ns){
			$dan = substr_count($string, $ns);
			$esdan = substr_count($string, "\\{$ns}");
			if($dan != $esdan) $escaped = false;
			if($dan) $nosc = false;
		}
		if($nosc){
			// find unescaped \
			$dan = substr_count($string, '\\');
			$esdan = substr_count($string, '\\\\');
			if($dan != $esdan * 2) $escaped = false;
		}

		return ($escaped ? $string : db_escape($string));
	}
	########################################################################
	function checkPermissionVal($pvn){
		// fn to make sure the value in the given POST variable is 0, 1, 2 or 3
		// if the value is invalid, it default to 0
		$pvn=intval($_POST[$pvn]);
		if($pvn!=1 && $pvn!=2 && $pvn!=3){
			return 0;
		}else{
			return $pvn;
		}
	}
	########################################################################
	if(!function_exists('sql')){
		function sql($statment, &$o){

			/*
				Supported options that can be passed in $o options array (as array keys):
				'silentErrors': If true, errors will be returned in $o['error'] rather than displaying them on screen and exiting.
			*/

			global $Translation;
			static $connected = false, $db_link;

			$dbServer = config('dbServer');
			$dbUsername = config('dbUsername');
			$dbPassword = config('dbPassword');
			$dbDatabase = config('dbDatabase');

			ob_start();

			if(!$connected){
				/****** Connect to MySQL ******/
				if(!extension_loaded('mysql') && !extension_loaded('mysqli')){
					echo Notification::placeholder();
					echo Notification::show(array(
						'message' => 'PHP is not configured to connect to MySQL on this machine. Please see <a href="http://www.php.net/manual/en/ref.mysql.php">this page</a> for help on how to configure MySQL.',
						'class' => 'danger',
						'dismiss_seconds' => 7200
					));
					$e=ob_get_contents(); ob_end_clean(); if($o['silentErrors']){ $o['error']=$e; return FALSE; }else{ echo $e; exit; }
				}

				if(!($db_link = @db_connect($dbServer, $dbUsername, $dbPassword))){
					echo Notification::placeholder();
					echo Notification::show(array(
						'message' => db_error($db_link, true),
						'class' => 'danger',
						'dismiss_seconds' => 7200
					));
					$e=ob_get_contents(); ob_end_clean(); if($o['silentErrors']){ $o['error']=$e; return FALSE; }else{ echo $e; exit; }
				}

				/****** Select DB ********/
				if(!db_select_db($dbDatabase, $db_link)){
					echo Notification::placeholder();
					echo Notification::show(array(
						'message' => db_error($db_link),
						'class' => 'danger',
						'dismiss_seconds' => 7200
					));
					$e=ob_get_contents(); ob_end_clean(); if($o['silentErrors']){ $o['error']=$e; return FALSE; }else{ echo $e; exit; }
				}

				$connected = true;
			}

			if(!$result = @db_query($statment, $db_link)){
				if(!stristr($statment, "show columns")){
					// retrieve error codes
					$errorNum = db_errno($db_link);
					$errorMsg = htmlspecialchars(db_error($db_link));

					if(getLoggedAdmin()) $errorMsg .= "<pre class=\"ltr\">{$Translation['query:']}\n" . htmlspecialchars($statment) . "</pre><i class=\"text-right\">{$Translation['admin-only info']}</i>";

					echo Notification::placeholder();
					echo Notification::show(array(
						'message' => $errorMsg,
						'class' => 'danger',
						'dismiss_seconds' => 7200
					));
					$e = ob_get_contents(); ob_end_clean(); if($o['silentErrors']){ $o['error'] = $errorMsg; return false; }else{ echo $e; exit; }
				}
			}

			ob_end_clean();
			return $result;
		}
	}
	########################################################################
	function sqlValue($statment){
		// executes a statment that retreives a single data value and returns the value retrieved
		if(!$res=sql($statment, $eo)){
			return FALSE;
		}
		if(!$row=db_fetch_row($res)){
			return FALSE;
		}
		return $row[0];
	}
	########################################################################
	function getLoggedAdmin(){
		// checks session variables to see whether the admin is logged or not
		// if not, it returns FALSE
		// if logged, it returns the user id

		$adminConfig = config('adminConfig');

		if($_SESSION['adminUsername']!=''){
			return $_SESSION['adminUsername'];
		}elseif($_SESSION['memberID']==$adminConfig['adminUsername']){
			$_SESSION['adminUsername']=$_SESSION['memberID'];
			return $_SESSION['adminUsername'];
		}else{
			return FALSE;
		}
	}
	########################################################################
	function checkUser($username, $password){
		// checks given username and password for validity
		// if valid, registers the username in a session and returns true
		// else, return FALSE and destroys session

		$adminConfig = config('adminConfig');
		if($username != $adminConfig['adminUsername'] || md5($password) != $adminConfig['adminPassword']){
			return FALSE;
		}

		$_SESSION['adminUsername'] = $username;
		$_SESSION['memberGroupID'] = sqlValue("select groupID from membership_users where memberID='" . makeSafe($username) ."'");
		$_SESSION['memberID'] = $username;
		return TRUE;
	}
	########################################################################
	function logOutUser(){
		// destroys current session
		if(isset($_COOKIE[session_name()])){
			setcookie(session_name(), '', time() - 42000, '/');
		}
		if(isset($_COOKIE[session_name() . '_rememberMe'])){
			setcookie(session_name() . '_rememberMe', '', time() - 42000);
		}
		session_destroy();
		$_SESSION = array();
	}
	########################################################################
	function getPKFieldName($tn){
		// get pk field name of given table

		$stn = makeSafe($tn, false);
		if(!$res = sql("show fields from `$stn`", $eo)){
			return false;
		}

		while($row = db_fetch_assoc($res)){
			if($row['Key'] == 'PRI'){
				return $row['Field'];
			}
		}

		return false;
	}
	########################################################################
	function getCSVData($tn, $pkValue, $stripTags=true){
		// get pk field name for given table
		if(!$pkField=getPKFieldName($tn)){
			return "";
		}

		// get a concat string to produce a csv list of field values for given table record
		if(!$res=sql("show fields from `$tn`", $eo)){
			return "";
		}
		while($row=db_fetch_assoc($res)){
			$csvFieldList.="`{$row['Field']}`,";
		}
		$csvFieldList=substr($csvFieldList, 0, -1);

		$csvData=sqlValue("select CONCAT_WS(', ', $csvFieldList) from `$tn` where `$pkField`='" . makeSafe($pkValue, false) . "'");

		return ($stripTags ? strip_tags($csvData) : $csvData);
	}
	########################################################################
	function errorMsg($msg){
		echo "<div class=\"alert alert-danger\">{$msg}</div>";
	}
	########################################################################
	function redirect($url, $absolute = false){
		$fullURL = ($absolute ? $url : application_url($url));
		if(!headers_sent()) header("Location: {$fullURL}");

		echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0;url={$fullURL}\">";
		echo "<br><br><a href=\"{$fullURL}\">Click here</a> if you aren't automatically redirected.";
		exit;
	}
	########################################################################
	function htmlRadioGroup($name, $arrValue, $arrCaption, $selectedValue, $selClass = "text-primary", $class = "", $separator = "<br>"){
		if(!is_array($arrValue)) return '';

		ob_start();
		?>
		<div class="radio %%CLASS%%"><label>
			<input type="radio" name="%%NAME%%" id="%%ID%%" value="%%VALUE%%" %%CHECKED%%> %%LABEL%%
		</label></div>
		<?php
		$template = ob_get_contents();
		ob_end_clean();

		$out = '';
		for($i = 0; $i < count($arrValue); $i++){
			$replacements = array(
				'%%CLASS%%' => html_attr($arrValue[$i] == $selectedValue ? $selClass :$class),
				'%%NAME%%' => html_attr($name),
				'%%ID%%' => html_attr($name . $i),
				'%%VALUE%%' => html_attr($arrValue[$i]),
				'%%LABEL%%' => $arrCaption[$i],
				'%%CHECKED%%' => ($arrValue[$i]==$selectedValue ? " checked" : "")
			);
			$out .= str_replace(array_keys($replacements), array_values($replacements), $template);
		}

		return $out;
	}
	########################################################################
	function htmlSelect($name, $arrValue, $arrCaption, $selectedValue, $class="", $selectedClass=""){
		if($selectedClass==""){
			$selectedClass=$class;
		}
		if(is_array($arrValue)){
			$out="<select name=\"$name\" id=\"$name\">";
			for($i=0; $i<count($arrValue); $i++){
				$out.="<option value=\"".$arrValue[$i]."\"".($arrValue[$i]==$selectedValue ? " selected class=\"$class\"" : " class=\"$selectedClass\"").">".$arrCaption[$i]."</option>";
			}
			$out.="</select>";
		}
		return $out;
	}
	########################################################################
	function htmlSQLSelect($name, $sql, $selectedValue, $class="", $selectedClass=""){
		$arrVal[]='';
		$arrCap[]='';
		if($res=sql($sql, $eo)){
			while($row=db_fetch_row($res)){
				$arrVal[]=$row[0];
				$arrCap[]=$row[1];
			}
			return htmlSelect($name, $arrVal, $arrCap, $selectedValue, $class, $selectedClass);
		}else{
			return "";
		}
	}
	########################################################################
	function bootstrapSelect($name, $arrValue, $arrCaption, $selectedValue, $class = '', $selectedClass = ''){
		if($selectedClass == '') $selectedClass = $class;

		$out = "<select class=\"form-control\" name=\"{$name}\" id=\"{$name}\">";
		if(is_array($arrValue)){
			for($i = 0; $i < count($arrValue); $i++){
				$selected = "class=\"{$class}\"";
				if($arrValue[$i] == $selectedValue) $selected = "selected class=\"{$selectedClass}\"";
				$out .= "<option value=\"{$arrValue[$i]}\" {$selected}>{$arrCaption[$i]}</option>";
			}
		}
		$out .= '</select>';

		return $out;
	}
	########################################################################
	function bootstrapSQLSelect($name, $sql, $selectedValue, $class = '', $selectedClass = ''){
		$arrVal[] = '';
		$arrCap[] = '';
		if($res = sql($sql, $eo)){
			while($row = db_fetch_row($res)){
				$arrVal[] = $row[0];
				$arrCap[] = $row[1];
			}
			return bootstrapSelect($name, $arrVal, $arrCap, $selectedValue, $class, $selectedClass);
		}

		return '';
	}
	########################################################################
	function isEmail($email){
		if(preg_match('/^([*+!.&#$¦\'\\%\/0-9a-z^_`{}=?~:-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,45})$/i', $email)){
			return $email;
		}else{
			return FALSE;
		}
	}
	########################################################################
	function notifyMemberApproval($memberID){
		$adminConfig = config('adminConfig');
		$memberID = strtolower($memberID);

		$email = sqlValue("select email from membership_users where lcase(memberID)='{$memberID}'");

		return sendmail(array(
			'to' => $email,
			'name' => $memberID,
			'subject' => $adminConfig['approvalSubject'],
			'message' => nl2br($adminConfig['approvalMessage'])
		));
	}
	########################################################################
	function setupMembership(){
		// run once per request
		static $executed = false;
		if($executed) return;
		$executed = true;

		/* abort if current page is one of the following exceptions */
		$exceptions = array('pageEditMember.php', 'membership_passwordReset.php', 'membership_profile.php', 'membership_signup.php', 'pageChangeMemberStatus.php', 'pageDeleteGroup.php', 'pageDeleteMember.php', 'pageEditGroup.php', 'pageEditMemberPermissions.php', 'pageRebuildFields.php', 'pageSettings.php');
		if(in_array(basename($_SERVER['PHP_SELF']), $exceptions)) return;

		$eo = array('silentErrors' => true);

		$adminConfig = config('adminConfig');
		$today = @date('Y-m-d');

		$membership_tables = array(
			'membership_groups' => "CREATE TABLE IF NOT EXISTS membership_groups (groupID int unsigned NOT NULL auto_increment, name varchar(20), description text, allowSignup tinyint, needsApproval tinyint, PRIMARY KEY (groupID)) CHARSET " . mysql_charset,
			'membership_users' => "CREATE TABLE IF NOT EXISTS membership_users (memberID varchar(20) NOT NULL, passMD5 varchar(40), email varchar(100), signupDate date, groupID int unsigned, isBanned tinyint, isApproved tinyint, custom1 text, custom2 text, custom3 text, custom4 text, comments text, PRIMARY KEY (memberID)) CHARSET " . mysql_charset,
			'membership_grouppermissions' => "CREATE TABLE IF NOT EXISTS membership_grouppermissions (permissionID int unsigned NOT NULL auto_increment,  groupID int, tableName varchar(100), allowInsert tinyint, allowView tinyint NOT NULL DEFAULT '0', allowEdit tinyint NOT NULL DEFAULT '0', allowDelete tinyint NOT NULL DEFAULT '0', PRIMARY KEY (permissionID)) CHARSET " . mysql_charset,
			'membership_userrecords' => "CREATE TABLE IF NOT EXISTS membership_userrecords (recID bigint unsigned NOT NULL auto_increment, tableName varchar(100), pkValue varchar(255), memberID varchar(20), dateAdded bigint unsigned, dateUpdated bigint unsigned, groupID int, PRIMARY KEY (recID)) CHARSET " . mysql_charset,
			'membership_userpermissions' => "CREATE TABLE IF NOT EXISTS membership_userpermissions (permissionID int unsigned NOT NULL auto_increment,  memberID varchar(20) NOT NULL, tableName varchar(100), allowInsert tinyint, allowView tinyint NOT NULL DEFAULT '0', allowEdit tinyint NOT NULL DEFAULT '0', allowDelete tinyint NOT NULL DEFAULT '0', PRIMARY KEY (permissionID)) CHARSET " . mysql_charset 
		);

		// get db tables
		$tables = array();
		$res = sql("show tables", $eo);

		if(!$res){
			include_once(dirname(__FILE__) . '/../header.php');
			echo $eo['error'];
			include_once(dirname(__FILE__) . '/../footer.php');
			exit;
		}

		while($row = db_fetch_array($res)) $tables[] = $row[0];

		// check if membership tables exist or not
		foreach($membership_tables as $tn => $tdef){
			if(!in_array($tn, $tables)){
				sql($tdef, $eo);
			}
		}

		// check membership_users definition
		$membership_users = array();
		$res = sql("show columns from membership_users", $eo);
		while($row = db_fetch_assoc($res)) $membership_users[$row['Field']] = $row;

		if(!in_array('pass_reset_key', array_keys($membership_users))) @db_query("ALTER TABLE membership_users ADD COLUMN pass_reset_key VARCHAR(100)");
		if(!in_array('pass_reset_expiry', array_keys($membership_users))) @db_query("ALTER TABLE membership_users ADD COLUMN pass_reset_expiry INT UNSIGNED");
		if(!$membership_users['groupID']['Key']) @db_query("ALTER TABLE membership_users ADD INDEX groupID (groupID)");

		// create membership indices if not existing
		$membership_userrecords = array();
		$res = sql("show keys from membership_userrecords", $eo);
		while($row = db_fetch_assoc($res)) $membership_userrecords[$row['Key_name']][$row['Seq_in_index']] = $row;

		if(!$membership_userrecords['pkValue'][1]) @db_query("ALTER TABLE membership_userrecords ADD INDEX pkValue (pkValue)");
		if(!$membership_userrecords['tableName'][1]) @db_query("ALTER TABLE membership_userrecords ADD INDEX tableName (tableName)");
		if(!$membership_userrecords['memberID'][1]) @db_query("ALTER TABLE membership_userrecords ADD INDEX memberID (memberID)");
		if(!$membership_userrecords['groupID'][1]) @db_query("ALTER TABLE membership_userrecords ADD INDEX groupID (groupID)");
		if(!$membership_userrecords['tableName_pkValue'][1] || !$membership_userrecords['tableName_pkValue'][2]) @db_query("ALTER IGNORE TABLE membership_userrecords ADD UNIQUE INDEX tableName_pkValue (tableName, pkValue)");

		// retreive anonymous and admin groups and their permissions
		$anon_group = $adminConfig['anonymousGroup'];
		$anon_user = strtolower($adminConfig['anonymousMember']);
		$admin_group = 'Admins';
		$admin_user = strtolower($adminConfig['adminUsername']);
		$groups_permissions = array();
		$res = sql(
			"select g.groupID, g.name, gp.tableName, gp.allowInsert, gp.allowView, gp.allowEdit, gp.allowDelete " .
			"from membership_groups g left join membership_grouppermissions gp on g.groupID=gp.groupID " .
			"where g.name='" . makeSafe($admin_group) . "' or g.name='" . makeSafe($anon_group) . "' " .
			"order by g.groupID, gp.tableName", $eo
		);
		while($row = db_fetch_assoc($res)) $groups_permissions[] = $row;

		// check anonymous group and user and create if necessary
		$anon_group_id = false;
		foreach($groups_permissions as $group){
			if($group['name'] == $anon_group){
				$anon_group_id = $group['groupID'];
				break;
			}
		}

		if(!$anon_group_id){
			sql("insert into membership_groups set name='" . makeSafe($anon_group) . "', allowSignup=0, needsApproval=0, description='Anonymous group created automatically on " . @date("Y-m-d") . "'", $eo);
			$anon_group_id = db_insert_id();
		}

		if($anon_group_id){
			$anon_user_db = sqlValue("select lcase(memberID) from membership_users where lcase(memberID)='" . makeSafe($anon_user) . "' and groupID='{$anon_group_id}'");
			if(!$anon_user_db || $anon_user_db != $anon_user){
				sql("delete from membership_users where groupID='{$anon_group_id}'", $eo);
				sql("insert into membership_users set memberID='" . makeSafe($anon_user) . "', signUpDate='{$today}', groupID='{$anon_group_id}', isBanned=0, isApproved=1, comments='Anonymous member created automatically on {$today}'", $eo);
			}
		}

		// check admin group and user and create if necessary
		$admin_group_id = false;
		foreach($groups_permissions as $group){
			if($group['name'] == $admin_group){
				$admin_group_id = $group['groupID'];
				break;
			}
		}

		if(!$admin_group_id){
			sql("insert into membership_groups set name='" . makeSafe($admin_group) . "', allowSignup=0, needsApproval=1, description='Admin group created automatically on {$today}'", $eo);
			$admin_group_id = db_insert_id();
		}

		if($admin_group_id){
			// check that admins can access all tables
			$all_tables = getTableList(true);
			$tables_ok = $perms_ok = array();
			foreach($all_tables as $tn => $tc) $tables_ok[$tn] = $perms_ok[$tn] = false;

			foreach($groups_permissions as $group){
				if($group['name'] == $admin_group){
					if(isset($tables_ok[$group['tableName']])){
						$tables_ok[$group['tableName']] = true;
						if($group['allowInsert'] == 1 && $group['allowDelete'] == 3 && $group['allowEdit'] == 3 && $group['allowView'] == 3){
							$perms_ok[$group['tableName']] = true;
						}
					}
				}
			}

			// if any table has no record in Admins permissions, create one for it
			$grant_sql = array();
			foreach($tables_ok as $tn => $status){
				if(!$status) $grant_sql[] = "({$admin_group_id}, '{$tn}')";
			}

			if(count($grant_sql)){
				sql("insert into membership_grouppermissions (groupID, tableName) values " . implode(',', $grant_sql), $eo);
			}

			// check admin permissions and update if necessary
			$perms_sql = array();
			foreach($perms_ok as $tn => $status){
				if(!$status) $perms_sql[] = "'{$tn}'";
			}

			if(count($perms_sql)){
				sql("update membership_grouppermissions set allowInsert=1, allowView=3, allowEdit=3, allowDelete=3 where groupID={$admin_group_id} and tableName in (" . implode(',', $perms_sql) . ")", $eo);
			}

			// check if super admin is stored in the users table and add him if not
			$admin_user_exists = sqlValue("select count(1) from membership_users where lcase(memberID)='" . makeSafe($admin_user)."' and groupID='{$admin_group_id}'");
			if(!$admin_user_exists){
				sql("insert into membership_users set memberID='" . makeSafe($admin_user) . "', passMD5='{$adminConfig['adminPassword']}', email='{$adminConfig['senderEmail']}', signUpDate='{$today}', groupID='{$admin_group_id}', isBanned=0, isApproved=1, comments='Admin member created automatically on {$today}'", $eo);
			}
		}
	}

	########################################################################
	function thisOr($this_val, $or = '&nbsp;'){
		return ($this_val != '' ? $this_val : $or);
	}
	########################################################################
	function getUploadedFile($FieldName, $MaxSize=0, $FileTypes='csv|txt', $NoRename=false, $dir=''){
		$currDir=dirname(__FILE__);
		if(is_array($_FILES)){
			$f = $_FILES[$FieldName];
		}else{
			return 'Your php settings don\'t allow file uploads.';
		}

		if(!$MaxSize){
			$MaxSize=toBytes(ini_get('upload_max_filesize'));
		}

		if(!is_dir("$currDir/csv")){
			@mkdir("$currDir/csv");
		}

		$dir=(is_dir($dir) && is_writable($dir) ? $dir : "$currDir/csv/");

		if($f['error']!=4 && $f['name']!=''){
			if($f['size']>$MaxSize || $f['error']){
				return 'File size exceeds maximum allowed of '.intval($MaxSize / 1024).'KB';
			}
			if(!preg_match('/\.('.$FileTypes.')$/i', $f['name'], $ft)){
				return 'File type not allowed. Only these file types are allowed: '.str_replace('|', ', ', $FileTypes);
			}

			if($NoRename){
				$n  = str_replace(' ', '_', $f['name']);
			}else{
				$n  = microtime();
				$n  = str_replace(' ', '_', $n);
				$n  = str_replace('0.', '', $n);
				$n .= $ft[0];
			}

			if(!@move_uploaded_file($f['tmp_name'], $dir . $n)){
				return 'Couldn\'t save the uploaded file. Try chmoding the upload folder "'.$dir.'" to 777.';
			}else{
				@chmod($dir.$n, 0666);
				return $dir.$n;
			}
		}
		return 'An error occured while uploading the file. Please try again.';
	}
	########################################################################
	function toBytes($val){
		$val = trim($val);
		$last = strtolower($val{strlen($val)-1});
		switch($last){
			 // The 'G' modifier is available since PHP 5.1.0
			 case 'g':
					$val *= 1024;
			 case 'm':
					$val *= 1024;
			 case 'k':
					$val *= 1024;
		}

		return $val;
	}
	########################################################################
	function convertLegacyOptions($CSVList){
		$CSVList=str_replace(';;;', ';||', $CSVList);
		$CSVList=str_replace(';;', '||', $CSVList);
		return $CSVList;
	}
	########################################################################
	function getValueGivenCaption($query, $caption){
		if(!preg_match('/select\s+(.*?)\s*,\s*(.*?)\s+from\s+(.*?)\s+order by.*/i', $query, $m)){
			if(!preg_match('/select\s+(.*?)\s*,\s*(.*?)\s+from\s+(.*)/i', $query, $m)){
				return '';
			}
		}

		// get where clause if present
		if(preg_match('/\s+from\s+(.*?)\s+where\s+(.*?)\s+order by.*/i', $query, $mw)){
			$where="where ($mw[2]) AND";
			$m[3]=$mw[1];
		}else{
			$where='where';
		}

		$caption=makeSafe($caption);
		return sqlValue("SELECT $m[1] FROM $m[3] $where $m[2]='$caption'");
	}
	########################################################################
	function undo_magic_quotes($str){
		return (get_magic_quotes_gpc() ? stripslashes($str) : $str);
	}
	########################################################################
	function time24($t = false){
		if($t === false) $t = date('Y-m-d H:i:s');
		return date('H:i:s', strtotime($t));
	}
	########################################################################
	function time12($t = false){
		if($t === false) $t = date('Y-m-d H:i:s');
		return date('h:i:s A', strtotime($t));
	}
	########################################################################
	function application_url($page = '', $s = false){
		if($s === false) $s = $_SERVER;
		$ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on');
		$http = ($ssl ? 'https:' : 'http:');
		$port = $s['SERVER_PORT'];
		$port = ((!$ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':' . $port;
		$host = (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : $s['SERVER_NAME'] . $port);
		$uri = dirname($s['SCRIPT_NAME']);

		/* app folder name (without the ending /admin part) */
		$app_folder_is_admin = false;
		$app_folder = substr(dirname(__FILE__), 0, -6);
		if(substr($app_folder, -6, 6) == '/admin' || substr($app_folder, -6, 6) == '\\admin')
			$app_folder_is_admin = true;

		if(substr($uri, -12, 12) == '/admin/admin') $uri = substr($uri, 0, -6);
		elseif(substr($uri, -6, 6) == '/admin' && !$app_folder_is_admin) $uri = substr($uri, 0, -6);
		elseif($uri == '/' || $uri == '\\') $uri = '';

		return "{$http}//{$host}{$uri}/{$page}";
	}
	########################################################################
	function is_ajax(){
		return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
	}
	########################################################################
	function array_trim($arr){
		if(!is_array($arr)) return trim($arr);
		return array_map('array_trim', $arr);
	}
	########################################################################
	function is_allowed_username($username, $exception = false){
		$username = trim(strtolower($username));
		if(!preg_match('/^[a-z0-9][a-z0-9 _.@]{3,19}$/', $username) || preg_match('/(@@|  |\.\.|___)/', $username)) return false;

		if($username == $exception) return $username;

		if(sqlValue("select count(1) from membership_users where lcase(memberID)='{$username}'")) return false;
		return $username;
	}
	########################################################################
	/*
		if called without parameters, looks for a non-expired token in the user's session (or creates one if
		none found) and returns html code to insert into the form to be protected.

		if set to true, validates token sent in $_REQUEST against that stored in the session
		and returns true if valid or false if invalid, absent or expired.

		usage:
			1. in a new form that needs csrf proofing: echo csrf_token();
			   >> in case of ajax requests and similar, retrieve token directly
			      by calling csrf_token(false, true);
			2. when validating a submitted form: if(!csrf_token(true)){ reject_submission_somehow(); }
	*/
	function csrf_token($validate = false, $token_only = false){
		$token_age = 60 * 60;
		/* retrieve token from session */
		$csrf_token = (isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : false);
		$csrf_token_expiry = (isset($_SESSION['csrf_token_expiry']) ? $_SESSION['csrf_token_expiry'] : false);

		if(!$validate){
			/* create a new token if necessary */
			if($csrf_token_expiry < time() || !$csrf_token){
				$csrf_token = md5(uniqid(rand(), true));
				$csrf_token_expiry = time() + $token_age;
				$_SESSION['csrf_token'] = $csrf_token;
				$_SESSION['csrf_token_expiry'] = $csrf_token_expiry;
			}

			if($token_only) return $csrf_token;
			return '<input type="hidden" id="csrf_token" name="csrf_token" value="' . $csrf_token . '">';
		}

		/* validate submitted token */
		$user_token = (isset($_REQUEST['csrf_token']) ? $_REQUEST['csrf_token'] : false);
		if($csrf_token_expiry < time() || !$user_token || $user_token != $csrf_token){
			return false;
		}

		return true;
	}
	########################################################################
	function get_plugins(){
		$plugins = array();
		$plugins_path = dirname(__FILE__) . '/../plugins/';

		if(!is_dir($plugins_path)) return $plugins;

		$pd = dir($plugins_path);
		while(false !== ($plugin = $pd->read())){
			if(!is_dir($plugins_path . $plugin) || in_array($plugin, array('projects', 'plugins-resources', '.', '..'))) continue;

			$info_file = "{$plugins_path}{$plugin}/plugin-info.json";
			if(!is_file($info_file)) continue;

			$plugins[] = json_decode(file_get_contents($info_file), true);
			$plugins[count($plugins) - 1]['admin_path'] = "../plugins/{$plugin}";
		}
		$pd->close();

		return $plugins;
	}
	########################################################################
	function maintenance_mode($new_status = ''){
		$maintenance_file = dirname(__FILE__) . '/.maintenance';

		if($new_status === true){
			/* turn on maintenance mode */
			@touch($maintenance_file);
		}elseif($new_status === false){
			/* turn off maintenance mode */
			@unlink($maintenance_file);
		}

		/* return current maintenance mode status */
		return is_file($maintenance_file);
	}
	########################################################################
	function handle_maintenance($echo = false){
		if(!maintenance_mode()) return;

		global $Translation;
		$adminConfig = config('adminConfig');

		$admin = getLoggedAdmin();
		if($admin){
			return ($echo ? '<div class="alert alert-danger" style="margin: 5em auto -5em;"><b>' . $Translation['maintenance mode admin notification'] . '</b></div>' : '');
		}

		if(!$echo) exit;

		exit('<div class="alert alert-danger" style="margin-top: 5em; font-size: 2em;"><i class="glyphicon glyphicon-exclamation-sign"></i> ' . $adminConfig['maintenance_mode_message'] . '</div>');
	}
	#########################################################
	function html_attr($str){
		if(version_compare(PHP_VERSION, '5.2.3') >= 0) return htmlspecialchars($str, ENT_QUOTES, datalist_db_encoding, false);
		return htmlspecialchars($str, ENT_QUOTES, datalist_db_encoding);
	}
	#########################################################
	class Request{
		var $sql, $url, $attr, $html, $raw;

		function __construct($var, $filter = false){
			$this->Request($var, $filter);
		}

		function Request($var, $filter = false){
			$unsafe = (isset($_REQUEST[$var]) ? $_REQUEST[$var] : '');
			if(get_magic_quotes_gpc()) $unsafe = stripslashes($unsafe);

			if($filter){
				$unsafe = call_user_func($filter, $unsafe);
			}

			$this->sql = makeSafe($unsafe, false);
			$this->url = urlencode($unsafe);
			$this->attr = html_attr($unsafe);
			$this->html = html_attr($unsafe);
			$this->raw = $unsafe;
		}
	}
	#########################################################
	class Notification{
		/*
			Usage:
			* in the main document, initiate notifications support using this PHP code:
				echo Notification::placeholder();

			* whenever you want to show a notifcation, use this PHP code:
				echo Notification::show(array(
					'message' => 'Notification text to display',
					'class' => 'danger', // or other bootstrap state cues, 'default' if not provided
					'dismiss_seconds' => 5, // optional auto-dismiss after x seconds
					'dismiss_days' => 7, // optional dismiss for x days if closed by user -- must provide an id
					'id' => 'xyz' // optional string to identify the notification -- must use for 'dismiss_days' to work
				));
		*/
		protected static $placeholder_id; /* to force a single notifcation placeholder */

		protected function __construct(){} /* to prevent initialization */

		public static function placeholder(){
			if(self::$placeholder_id) return ''; // output placeholder code only once

			self::$placeholder_id = 'notifcation-placeholder-' . rand(10000000, 99999999);

			ob_start();
			?>

			<div class="notifcation-placeholder" id="<?php echo self::$placeholder_id; ?>"></div>
			<script>
				$j(function(){
					if(window.show_notification != undefined) return;

					window.show_notification = function(options){
						/* wait till all dependencies ready */
						if(window.notifications_ready == undefined){
							var op = options;
							setTimeout(function(){ show_notification(op); }, 20);
							return;
						}

						var dismiss_class = '';
						var dismiss_icon = '';
						var cookie_name = 'hide_notification_' + options.id;
						var notif_id = 'notifcation-' + Math.ceil(Math.random() * 1000000);

						/* apply provided notficiation id if unique in page */
						if(options.id != undefined){
							if(!$j('#' + options.id).length) notif_id = options.id;
						}

						/* notifcation should be hidden? */
						if(Cookies.get(cookie_name) != undefined) return;

						/* notification should be dismissable? */
						if(options.dismiss_seconds > 0 || options.dismiss_days > 0){
							dismiss_class = ' alert-dismissible';
							dismiss_icon = '<button type="button" class="close" data-dismiss="alert">&times;</button>';
						}

						/* remove old dismissed notficiations */
						$j('.alert-dismissible.invisible').remove();

						/* append notification to notifications container */
						$j(
							'<div class="alert alert-' + options['class'] + dismiss_class + '" id="' + notif_id + '">' + 
								dismiss_icon +
								options.message + 
							'</div>'
						).appendTo('#<?php echo self::$placeholder_id; ?>');

						var this_notif = $j('#' + notif_id);

						/* dismiss after x seconds if requested */
						if(options.dismiss_seconds > 0){
							setTimeout(function(){ this_notif.addClass('invisible'); }, options.dismiss_seconds * 1000);
						}

						/* dismiss for x days if requested and user dismisses it */
						if(options.dismiss_days > 0){
							var ex_days = options.dismiss_days;
							this_notif.on('closed.bs.alert', function(){
								/* set a cookie not to show this alert for ex_days */
								Cookies.set(cookie_name, '1', { expires: ex_days });
							});
						}
					}

					/* cookies library already loaded? */
					if(undefined != window.Cookies){
						window.notifications_ready = true;
						return;
					}

					/* load cookies library */
					$j.ajax({
						url: '<?php echo PREPEND_PATH; ?>resources/jscookie/js.cookie.js',
						dataType: 'script',
						cache: true,
						success: function(){ window.notifications_ready = true; }
					});
				})
			</script>

			<?php
			$html = ob_get_contents();
			ob_end_clean();

			return $html;            
		}

		protected static function default_options(&$options){
			if(!isset($options['message'])) $options['message'] = 'Notification::show() called without a message!';

			if(!isset($options['class'])) $options['class'] = 'default';

			if(!isset($options['dismiss_seconds']) || isset($options['dismiss_days'])) $options['dismiss_seconds'] = 0;

			if(!isset($options['dismiss_days'])) $options['dismiss_days'] = 0;
			if(!isset($options['id'])){
				$options['id'] = 0;
				$options['dismiss_days'] = 0;
			}
		}

		/**
		 *  @brief Notification::show($options) displays a notification
		 *  
		 *  @param $options assoc array
		 *  
		 *  @return html code for displaying the notifcation
		 */
		public static function show($options = array()){
			self::default_options($options);

			ob_start();
			?>
			<script>
				$j(function(){
					show_notification(<?php echo json_encode($options); ?>);
				})
			</script>
			<?php
			$html = ob_get_contents();
			ob_end_clean();

			return $html;
		}
	}
	#########################################################
	function sendmail($mail){
		if(!isset($mail['to'])) return 'No recipient defined';
		if(!isEmail($mail['to'])) return 'Invalid recipient email';

		$mail['subject'] = isset($mail['subject']) ? $mail['subject'] : '';
		$mail['message'] = isset($mail['message']) ? $mail['message'] : '';
		$mail['name'] = isset($mail['name']) ? $mail['name'] : '';
		$mail['debug'] = isset($mail['debug']) ? min(4, max(0, intval($mail['debug']))) : 0;

		$cfg = config('adminConfig');
		$smtp = ($cfg['mail_function'] == 'smtp');

		if(!class_exists('PHPMailer')){
			$curr_dir = dirname(__FILE__);
			include("{$curr_dir}/../resources/PHPMailer/class.phpmailer.php");
			if($smtp) include("{$curr_dir}/../resources/PHPMailer/class.smtp.php");
		}

		$pm = new PHPMailer;
		$pm->CharSet = datalist_db_encoding;

		if($smtp){
			$pm->isSMTP();
			$pm->SMTPDebug = $mail['debug'];
			$pm->Debugoutput = 'html';
			$pm->Host = $cfg['smtp_server'];
			$pm->Port = $cfg['smtp_port'];
			$pm->SMTPAuth = true;
			$pm->SMTPSecure = $cfg['smtp_encryption'];
			$pm->Username = $cfg['smtp_user'];
			$pm->Password = $cfg['smtp_pass'];
		}

		$pm->setFrom($cfg['senderEmail'], $cfg['senderName']);
		$pm->addAddress($mail['to'], $mail['name']);
		$pm->Subject = $mail['subject'];

		/* if message already contains html tags, don't apply nl2br */
		if($mail['message'] == strip_tags($mail['message']))
			$mail['message'] = nl2br($mail['message']);

		$pm->msgHTML($mail['message'], realpath("{$curr_dir}/.."));

		/* if sendmail_handler(&$pm) is defined (in hooks/__global.php) */
		if(function_exists('sendmail_handler')) sendmail_handler($pm);

		if(!$pm->send()) return $pm->ErrorInfo;

		return true;
	}
	#########################################################
	function safe_html($str){
		/* if $str has no HTML tags, apply nl2br */
		if($str == strip_tags($str)) return nl2br($str);

		$hc = new CI_Input();
		$hc->charset = datalist_db_encoding;

		return $hc->xss_clean($str);
	}
	#########################################################
	function getLoggedGroupID(){
		if($_SESSION['memberGroupID']!=''){
			return $_SESSION['memberGroupID'];
		}else{
			if(!setAnonymousAccess()) return false;
			return getLoggedGroupID();
		}
	}
	#########################################################
	function getLoggedMemberID(){
		if($_SESSION['memberID']!=''){
			return strtolower($_SESSION['memberID']);
		}else{
			if(!setAnonymousAccess()) return false;
			return getLoggedMemberID();
		}
	}
	#########################################################
	function setAnonymousAccess(){
		$adminConfig = config('adminConfig');
		$anon_group_safe = addslashes($adminConfig['anonymousGroup']);
		$anon_user_safe = strtolower(addslashes($adminConfig['anonymousMember']));

		$eo = array('silentErrors' => true);

		$res = sql("select groupID from membership_groups where name='{$anon_group_safe}'", $eo);
		if(!$res){ return false; }
		$row = db_fetch_array($res); $anonGroupID = $row[0];

		$_SESSION['memberGroupID'] = ($anonGroupID ? $anonGroupID : 0);

		$res = sql("select lcase(memberID) from membership_users where lcase(memberID)='{$anon_user_safe}' and groupID='{$anonGroupID}'", $eo);
		if(!$res){ return false; }
		$row = db_fetch_array($res); $anonMemberID = $row[0];

		$_SESSION['memberID'] = ($anonMemberID ? $anonMemberID : 0);

		return true;
	}
	#########################################################
	function getMemberInfo($memberID = ''){
		static $member_info = array();

		if(!$memberID){
			$memberID = getLoggedMemberID();
		}

		// return cached results, if present
		if(isset($member_info[$memberID])) return $member_info[$memberID];

		$adminConfig = config('adminConfig');
		$mi = array();

		if($memberID){
			$res = sql("select * from membership_users where memberID='" . makeSafe($memberID) . "'", $eo);
			if($row = db_fetch_assoc($res)){
				$mi = array(
					'username' => $memberID,
					'groupID' => $row['groupID'],
					'group' => sqlValue("select name from membership_groups where groupID='{$row['groupID']}'"),
					'admin' => ($adminConfig['adminUsername'] == $memberID ? true : false),
					'email' => $row['email'],
					'custom' => array(
						$row['custom1'], 
						$row['custom2'], 
						$row['custom3'], 
						$row['custom4']
					),
					'banned' => ($row['isBanned'] ? true : false),
					'approved' => ($row['isApproved'] ? true : false),
					'signupDate' => @date('n/j/Y', @strtotime($row['signupDate'])),
					'comments' => $row['comments'],
					'IP' => $_SERVER['REMOTE_ADDR']
				);

				// cache results
				$member_info[$memberID] = $mi;
			}
		}

		return $mi;
	}
	#########################################################
	function get_group_id($user = ''){
		$mi = getMemberInfo($user);
		return $mi['groupID'];
	}
	#########################################################
	/**
	 *  @brief Prepares data for a SET or WHERE clause, to be used in an INSERT/UPDATE query
	 *  
	 *  @param [in] $set_array Assoc array of field names => values
	 *  @param [in] $glue optional glue. Set to ' AND ' or ' OR ' if preparing a WHERE clause
	 *  @return SET string
	 */
	function prepare_sql_set($set_array, $glue = ', '){
		$fnvs = array();
		foreach($set_array as $fn => $fv){
			if($fv === null){ $fnvs[] = "{$fn}=NULL"; continue; }

			$sfv = makeSafe($fv);
			$fnvs[] = "{$fn}='{$sfv}'";
		}
		return implode($glue, $fnvs);
	}
	#########################################################
	/**
	 *  @brief Inserts a record to the database
	 *  
	 *  @param [in] $tn table name where the record would be inserted
	 *  @param [in] $set_array Assoc array of field names => values to be inserted
	 *  @return boolean indicating success/failure
	 */
	function insert($tn, $set_array){
		$set = prepare_sql_set($set_array);
		if(!count($set)) return false;

		return sql("INSERT INTO `{$tn}` SET {$set}", $eo);
	}
	#########################################################
	/**
	 *  @brief Updates a record in the database
	 *  
	 *  @param [in] $tn table name where the record would be inserted
	 *  @param [in] $set_array Assoc array of field names => values to be inserted
	 *  @param [in] $where_array Assoc array of field names => values used to build the WHERE clause
	 *  @return boolean indicating success/failure
	 */
	function update($tn, $set_array, $where_array){
		$set = prepare_sql_set($set_array);
		if(!count($set)) return false;

		$where = prepare_sql_set($where_array, ' AND ');
		if(!$where) $where = '1=1';

		return sql("UPDATE `{$tn}` SET {$set} WHERE {$where}", $eo);
	}
	#########################################################
	/**
	 *  @brief Set/update the owner of given record
	 *  
	 *  @param [in] $tn name of table
	 *  @param [in] $pk primary key value
	 *  @param [in] $user username to set as owner
	 *  @return boolean indicating success/failure
	 */
	function set_record_owner($tn, $pk, $user){
		$fields = array(
			'memberID' => strtolower($user),
			'dateUpdated' => time(),
			'groupID' => get_group_id($user)
		);

		$where_array = array('tableName' => $tn, 'pkValue' => $pk);
		$where = prepare_sql_set($where_array, ' AND ');
		if(!$where) return false;

		/* do we have an ownership record? */
		$existing_owner = sqlValue("select LCASE(memberID) from membership_userrecords where {$where}");
		if($existing_owner == $user) return true; // owner already set to $user

		/* update owner */
		if($existing_owner){
			$res = update('membership_userrecords', $fields, $where_array);
			return ($res ? true : false);
		}

		/* add new ownership record */
		$fields = array_merge($fields, $where_array, array('dateAdded' => time()));
		$res = insert('membership_userrecords', $fields);
		return ($res ? true : false);
	}

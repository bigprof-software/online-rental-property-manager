<?php
	error_reporting(E_ERROR | E_WARNING | E_PARSE);

	if(!defined('datalist_db_encoding')) define('datalist_db_encoding', 'UTF-8');
	if(function_exists('set_magic_quotes_runtime')) @set_magic_quotes_runtime(0);
	ob_start();
	$currDir = dirname(__FILE__);
	include("{$currDir}/../db.php");
	include("{$currDir}/../settings-manager.php");

	// check if initial setup was performed or not
	detect_config();
	migrate_config();

	$adminConfig = config('adminConfig');
	include("{$currDir}/incFunctions.php");
	@include_once("{$currDir}/../hooks/__global.php");
	include("{$currDir}/../language.php");
	include("{$currDir}/../defaultLang.php");
	include("{$currDir}/../language-admin.php");

	/* trim $_POST, $_GET, $_REQUEST */
	if(count($_POST)) $_POST = array_trim($_POST);
	if(count($_GET)) $_GET = array_trim($_GET);
	if(count($_REQUEST)) $_REQUEST = array_trim($_REQUEST);

	// check sessions config
	$noPathCheck=True;
	$arrPath=explode(';', ini_get('session.save_path'));
	$save_path=$arrPath[count($arrPath)-1];
	if(!$noPathCheck && !is_dir($save_path)){
		?>
		<link rel="stylesheet" href="adminStyles.css">
		<center>
		<div class="alert alert-danger">
			Your site is not configured to support sessions correctly. Please edit your php.ini file and change the value of <i>session.save_path</i> to a valid path.
			<br><br>
			Current session.save_path value is '<?php echo $save_path; ?>'.
			</div>
			</center>
		<?php
		exit;
	}
	if(session_id()){ session_write_close(); }
	$configured_save_handler = @ini_get('session.save_handler');
	if($configured_save_handler != 'memcache' && $configured_save_handler != 'memcached')
		@ini_set('session.save_handler', 'files');
	@ini_set('session.serialize_handler', 'php');
	@ini_set('session.use_cookies', '1');
	@ini_set('session.use_only_cookies', '1');
	@ini_set('session.cookie_httponly', '1');
	@ini_set('session.use_strict_mode', '1');
	@session_cache_expire(2);
	@session_cache_limiter($_SERVER['REQUEST_METHOD'] == 'POST' ? 'private' : 'nocache');
	@session_name('rental_property_manager');
	session_start();


	// check if membership system exists
	setupMembership();


	########################################################################

	// do we have an admin log out request?
	if(isset($_GET['signOut'])){
		logOutUser();
		redirect('../index.php?signIn=1');
	}

	// is there a logged user?
	if(!$uname=getLoggedAdmin()){
		// is there a user trying to log in?
		if(!checkUser($_POST['username'], $_POST['password'])){
			// display login form
			?><META HTTP-EQUIV="Refresh" CONTENT="0;url=../index.php?signIn=1"><?php
			exit;
		}else{
			redirect('admin/pageHome.php');
		}
	}

?>
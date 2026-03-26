<?php
define('PREPEND_PATH', '');
include(__DIR__ . "/lib.php");

$rid = Request::val('rid');

if(!$rid) {
	redirect('index.php');
}

// 1. Validate Request ID from DB
if(!TFA::checkRequest($rid)) {
	// Invalid or expired request
	redirect('index.php?loginFailed=1&reason=expired');
}

$error = '';

// 2. Process Form Submission
if(isset($_POST['otp'])) {
	$status = TFA::verify($rid, $_POST['otp']);

	switch($status) {
		case 'success':
			redirect('index.php');
			break;

		case 'expired':
			redirect('index.php?loginFailed=1&reason=expired');
			break;

		case 'invalid':
			$error = $Translation['2fa_error_invalid_code'];
			break;
	}
}

$x = new stdClass();
$x->TableTitle = $Translation['2fa_page_title'];

include(__DIR__ . "/header.php"); // header uses PREPEND_PATH
?>

<div class="page-header">
	<h1><?php echo $x->TableTitle; ?></h1>
</div>

<div class="text-center" style="margin: 2em 0;">
	<i class="glyphicon glyphicon-lock text-success" style="font-size: 5em; vertical-align: middle;"></i>
	<i class="glyphicon glyphicon-chevron-right rtl-mirror text-success hspacer-sm" style="font-size: 3em; vertical-align: middle;"></i>
	<i class="glyphicon glyphicon-envelope text-success" style="font-size: 6em; vertical-align: middle;"></i>
</div>

<div class="row">
	<div class="col-md-6 col-md-offset-3">
		<div class="panel panel-default panel-2fa">
			<div class="panel-heading">
				<h2 class="panel-title font-bold" style="font-size: 1.25em;"><?php echo $Translation['2fa_instruction']; ?></h2>
			</div>
			<div class="panel-body">
				<p class="text-muted"><?php echo $Translation['2fa_instruction_sub']; ?></p>

				<?php if($error): ?>
					<div class="alert alert-danger"><?php echo $error; ?></div>
				<?php endif; ?>

				<form method="post" action="?rid=<?php echo html_attr($rid); ?>">
					<div class="form-group">
						<label><?php echo $Translation['2fa_label_code']; ?></label>
						<input type="text" name="otp" class="form-control" placeholder="<?php echo TFA::placeholderOTP(); ?>" required autocomplete="off" autofocus>
					</div>
					<button type="submit" class="btn btn-success btn-block btn-lg"><?php echo $Translation['2fa_btn_verify']; ?></button>
					<div style="margin-top: 10px; text-align: center;">
						<a href="<?= PREPEND_PATH ?>index.php?signOut=1"><?php echo $Translation['2fa_btn_cancel']; ?></a>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>


<?php include(__DIR__ . "/footer.php"); ?>


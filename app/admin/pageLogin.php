<!doctype html public "-//W3C//DTD html 4.0 //en">
<html>
	<head>
		<title>Sign In First!</title>
		</head>
	<body style="font-family:verdana; font-size:13px;">
		<br><br>
		<center style="font-size: 40px; font-family: garamond, georgia, serif; font-weight: bold; color: white; background-color: #c0c0c0;">A D M I N &nbsp; A R E A</center><br>
		<center><b><?php echo ($_POST['username']!='' ? "Invalid login data." : "You must sign in before accessing this page."); ?></b></center>
		<br>
		<form method="post" action="pageHome.php">
			<div align="center"><div style="width: 260px; text-align: center; border: solid 1px black; padding: 10px; background-color: #E6E6FA;">
			<table border="0" cellspacing="0" cellpadding="0" align="center">
				<tr>
					<td align="right" style="font-family:verdana; font-size:10px;">
						<b>Username</b>
						&nbsp;
						</td>
					<td align="left">
						<input type="text" name="username" value="" size="20">
						</td>
					</tr>
				<tr>
					<td align="right" style="font-family:verdana; font-size:10px;">
						<b>Password</b>
						&nbsp;
						</td>
					<td align="left">
						<input type="password" name="password" value="" size="20">
						</td>
					</tr>
				<tr>
					<td colspan="2" align="center">
						<input type="submit" value="Sign In">
						</td>
				</table>
				</div></div>
			</form><br>
			<?php
				if($adminConfig['adminUsername']=='admin' && $adminConfig['adminPassword']==md5('admin')){
					?>
					<center>Default username: <i>admin</i><br>Default password: <i>admin</i></center><br>
					<?php
				}
			?>
			<center><a href="../"><img src="images/home.gif" border=0 alt="Go to users' area" title="Go to users' area"></a></center>
		</body>
	</html>
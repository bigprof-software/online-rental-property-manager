<?php
	$currDir = dirname(__FILE__);
	require("{$currDir}/incCommon.php");
	$GLOBALS['page_title'] = $Translation['ownership batch transfer'];
	include("{$currDir}/incHeader.php");

	/* we need the following variables:
		$sourceGroupID
		$sourceMemberID (-1 means "all")
		$destinationGroupID
		$destinationMemberID

		if $sourceGroupID!=$destinationGroupID && $sourceMemberID==-1, an additional var:
		$moveMembers (=0 or 1)
	*/

	// validate input vars
	$sourceGroupID = intval($_GET['sourceGroupID']);
	$sourceMemberID = makeSafe(strtolower($_GET['sourceMemberID']));
	$destinationGroupID = intval($_GET['destinationGroupID']);
	$destinationMemberID = makeSafe(strtolower($_GET['destinationMemberID']));
	$moveMembers = intval($_GET['moveMembers']);
	$statuses = array();

	// transfer operations
	if($sourceGroupID && $sourceMemberID && $destinationGroupID && ($destinationMemberID || $moveMembers) && isset($_GET['beginTransfer'])){
		/* validate everything:
			1. Make sure sourceMemberID belongs to sourceGroupID
			2. if moveMembers is false, make sure destinationMemberID belongs to destinationGroupID
		*/
		if(!sqlValue("select count(1) from membership_users where lcase(memberID)='$sourceMemberID' and groupID='$sourceGroupID'")){
			if($sourceMemberID!=-1){
				errorMsg($Translation['invalid source member']);
				include("{$currDir}/incFooter.php");
			}
		}
		if(!$moveMembers){
			if(!sqlValue("select count(1) from membership_users where lcase(memberID)='$destinationMemberID' and groupID='$destinationGroupID'")){
				errorMsg($Translation['invalid destination member']);
				include("{$currDir}/incFooter.php");
			}
		}

		// get group names
		$sourceGroup=sqlValue("select name from membership_groups where groupID='$sourceGroupID'");
		$destinationGroup=sqlValue("select name from membership_groups where groupID='$destinationGroupID'");

		// begin transfer
		if($moveMembers && $sourceMemberID != -1){
			$originalValues = array('<MEMBERID>', '<SOURCEGROUP>', '<DESTINATIONGROUP>');
			$replaceValues = array($sourceMemberID, $sourceGroup, $destinationGroup);
			$statuses[] = str_replace($originalValues, $replaceValues, $Translation['moving member']);

			// change source member group
			sql("update membership_users set groupID='$destinationGroupID' where lcase(memberID)='$sourceMemberID' and groupID='$sourceGroupID'", $eo);
			$newGroup = sqlValue("select name from membership_users u, membership_groups g where u.groupID=g.groupID and lcase(u.memberID)='$sourceMemberID'");

			// change group of source member's data
			sql("update membership_userrecords set groupID='$destinationGroupID' where lcase(memberID)='$sourceMemberID' and groupID='$sourceGroupID'", $eo);
			$dataRecs = sqlValue("select count(1) from membership_userrecords where lcase(memberID)='$sourceMemberID' and groupID='$destinationGroupID'");

			// status
			$originalValues =  array('<MEMBERID>', '<NEWGROUP>', '<DATARECORDS>');
			$replaceValues = array($sourceMemberID, $newGroup, $dataRecs);
			$statuses[] = str_replace($originalValues, $replaceValues, $Translation['data records transferred']);

		}elseif(!$moveMembers && $sourceMemberID != -1){
			$originalValues = array('<SOURCEMEMBER>', '<SOURCEGROUP>', '<DESTINATIONMEMBER>', '<DESTINATIONGROUP>');
			$replaceValues = array($sourceMemberID, $sourceGroup, $destinationMemberID, $destinationGroup);
			$statuses[] = str_replace($originalValues, $replaceValues, $Translation['moving data']);

			// change group and owner of source member's data
			$srcDataRecsBef = sqlValue("select count(1) from membership_userrecords where lcase(memberID)='$sourceMemberID' and groupID='$sourceGroupID'");
			sql("update membership_userrecords set groupID='$destinationGroupID', memberID='$destinationMemberID' where lcase(memberID)='$sourceMemberID' and groupID='$sourceGroupID'", $eo);
			$srcDataRecsAft = sqlValue("select count(1) from membership_userrecords where lcase(memberID)='$sourceMemberID' and groupID='$sourceGroupID'");

			// status
			$originalValues = array('<SOURCEMEMBER>', '<SOURCEGROUP>', '<DATABEFORE>','<TRANSFERSTATUS>', '<DESTINATIONMEMBER>', '<DESTINATIONGROUP>');
			$transferStatus = ($srcDataRecsAft>0 ? "No records were transferred" : "These records now belong");
			$replaceValues = array($sourceMemberID, $sourceGroup, $srcDataRecsBef, $transferStatus ,$destinationMemberID, $destinationGroup);
			$statuses[] = str_replace ($originalValues, $replaceValues, $Translation['member records status']);

		}elseif($moveMembers){
			$originalValues =  array('<SOURCEGROUP>', '<DESTINATIONGROUP>');
			$replaceValues = array(  $sourceGroup ,$destinationGroup);
			$statuses[] = str_replace($originalValues, $replaceValues, $Translation['moving all group members']);

			// change source members group
			sql("update membership_users set groupID='$destinationGroupID' where groupID='$sourceGroupID'", $eo);
			$srcGroupMembers=sqlValue("select count(1) from membership_users where groupID='$sourceGroupID'");

			// change group of source member's data
			if(!$srcGroupMembers){
				$dataRecsBef=sqlValue("select count(1) from membership_userrecords where groupID='$sourceGroupID'");
				sql("update membership_userrecords set groupID='$destinationGroupID' where groupID='$sourceGroupID'", $eo);
				$dataRecsAft=sqlValue("select count(1) from membership_userrecords where groupID='$sourceGroupID'");
			}

			// status
			$originalValues =  array('<SOURCEGROUP>', '<DESTINATIONGROUP>');
			$replaceValues = array($sourceGroup ,$destinationGroup);
			if($srcGroupMembers){
				$statuses[] = str_replace($originalValues, $replaceValues, $Translation['failed transferring group members']);
			}else{
				$statuses[] = str_replace($originalValues, $replaceValues, $Translation['group members transferred']);

				if($dataRecsAft){
					$statuses[] = $Translation['failed transfer data records'];
				}else{
					$statuses[] = str_replace('<DATABEFORE>', $dataRecsBef, $Translation['data records were transferred']);
				}
			}

		}else{
			$originalValues =  array('<SOURCEGROUP>', '<DESTINATIONMEMBER>', '<DESTINATIONGROUP>');
			$replaceValues = array(  $sourceGroup, $destinationMemberID, $destinationGroup);
			$statuses[] = str_replace ($originalValues, $replaceValues, $Translation['moving group data to member']);

			// change group of source member's data
			$recsBef = sqlValue("select count(1) from membership_userrecords where lcase(memberID)='$destinationMemberID'");
			sql("update membership_userrecords set groupID='$destinationGroupID', memberID='$destinationMemberID' where groupID='$sourceGroupID'", $eo);
			$recsAft = sqlValue("select count(1) from membership_userrecords where lcase(memberID)='$destinationMemberID'");

			// status
			$originalValues =  array( '<NUMBER>', '<SOURCEGROUP>', '<DESTINATIONMEMBER>', '<DESTINATIONGROUP>');
			$recordsNumber = intval($recsAft-$recsBef);
			$replaceValues = array($recordsNumber,  $sourceGroup, $destinationMemberID, $destinationGroup);
			$statuses[] = str_replace($originalValues, $replaceValues, $Translation['moving group data to member status']);
		}

		// display status and a batch bookmark for later instant reuse of the wizard
		$status = implode('<br>', $statuses);
		echo Notification::show(array(
			'message' => "<b>{$Translation['status']}</b><br>{$status}",
			'class' => 'info',
			'dismiss_seconds' => 3600
		));
		?>
		<div>
			<?php 
				$originalValues =  array('<SOURCEGROUP>', '<SOURCEMEMBER>', '<DESTINATIONGROUP>', '<DESTINATIONMEMBER>', '<MOVEMEMBERS>');
				$replaceValues = array($sourceGroupID, urlencode($sourceMemberID), $destinationGroupID, urlencode($destinationMemberID), $moveMembers);
				echo str_replace ($originalValues, $replaceValues, $Translation['batch transfer link']);
			?>
		</div>

		<a href="pageTransferOwnership.php" class="btn btn-default btn-lg vspacer-lg"><i class="glyphicon glyphicon-chevron-left"></i> <?php echo $Translation['batch transfer']; ?></a>

		<?php

		// quit
		include("{$currDir}/incFooter.php");
	}
?>

<div class="page-header"><h1><?php echo $Translation['ownership batch transfer']; ?></h1></div>

<form method="get" action="pageTransferOwnership.php" class="form-horizontal">

	<div id="step-1" class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">
				<b><?php echo $Translation['step 1']; ?></b>
				<?php if($sourceGroupID){ ?>
					<span class="pull-right text-success">
						<i class="glyphicon glyphicon-ok"></i> 
						<?php echo $Translation['source group']; ?>:
						<b><?php echo sqlValue("select name from membership_groups where groupID='{$sourceGroupID}'"); ?></b>
					</span>
				<?php } ?>
			</h3>
		</div>
		<div class="panel-body">
			<div class="step-details"><?php echo $Translation['batch transfer wizard']; ?></div>
			<div class="form-group">
				<label for="sourceGroupID" class="control-label col-sm-2"><?php echo $Translation['source group']; ?></label>
				<div class="col-sm-6 col-md-4">
					<?php echo htmlSQLSelect("sourceGroupID", "select distinct g.groupID, g.name from membership_groups g, membership_users u where g.groupID=u.groupID order by g.name", $sourceGroupID); ?>
					<?php if($sourceGroupID){ ?>
						<span class="help-block text-info">
							<i class="glyphicon glyphicon-info-sign"></i> 
							<?php 
								$originalValues =  array( '<MEMBERS>', '<RECORDS>');
								$membersNum = sqlValue("select count(1) from membership_users where groupID='$sourceGroupID'"); 
								$recordsNum = sqlValue("select count(1) from membership_userrecords where groupID='$sourceGroupID'");
								$replaceValues = array($membersNum, $recordsNum);
								echo str_replace($originalValues, $replaceValues, $Translation['group statistics']);
							?>
						</span>
					<?php } ?>
				</div>
				<div class="col-sm-4 col-md-2">
					<button type="submit" class="btn btn-block btn-primary">
						<i class="glyphicon glyphicon-ok"></i> 
						<?php echo ($sourceGroupID ? $Translation['update'] : $Translation['next step']); ?>
					</button>
				</div>
			</div>
		</div>
	</div>

	<?php if($sourceGroupID){ ?>
		<div id="step-2" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">
					<b><?php echo $Translation['step 2'] ; ?></b>
					<?php if($sourceMemberID){ ?>
						<span class="pull-right text-success">
							<i class="glyphicon glyphicon-ok"></i> 
							<?php echo $Translation['source member']; ?>:
							<b><?php echo $sourceMemberID; ?></b>
						</span>
					<?php } ?>
				</h3>
			</div>
			<div class="panel-body">
				<div class="step-details"><?php echo $Translation['source member message'] ; ?></div>
				<div class="form-group">
					<label for="sourceGroupID" class="control-label col-sm-2"><?php echo $Translation['source member'] ; ?></label>
					<div class="col-sm-6 col-md-4">
						<?php
							$arrVal[] = '';
							$arrCap[] = '';
							$arrVal[] = '-1';
							$arrCap[] = str_replace ('<GROUPNAME>', html_attr(sqlValue("select name from membership_groups where groupID='$sourceGroupID'")), $Translation['all group members']);
							if($res = sql("select lcase(memberID), lcase(memberID) from membership_users where groupID='$sourceGroupID' order by memberID", $eo)){
								while($row = db_fetch_row($res)){
									$arrVal[] = $row[0];
									$arrCap[] = $row[1];
								}
								echo htmlSelect("sourceMemberID", $arrVal, $arrCap, $sourceMemberID);
							}
						?>
						<?php if($sourceMemberID){ ?>
							<span class="help-block text-info">
								<i class="glyphicon glyphicon-info-sign"></i> 
								<?php
									$recordsNum = sqlValue("select count(1) from membership_userrecords where ".($sourceMemberID==-1 ? "groupID='$sourceGroupID'" : "memberID='$sourceMemberID'"));
									echo str_replace ('<RECORDS>', $recordsNum, $Translation['member statistics']);
								?>
							</span>
						<?php } ?>
					</div>
					<div class="col-sm-4 col-md-2">
						<button type="submit" class="btn btn-block btn-primary">
							<i class="glyphicon glyphicon-ok"></i> 
							<?php echo ($sourceMemberID ? $Translation['update'] : $Translation['next step']); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>

	<?php if($sourceMemberID){ ?>
		<div id="step-3" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">
					<b><?php echo $Translation['step 3']; ?></b>
					<?php if($destinationGroupID){ ?>
						<span class="pull-right text-success">
							<i class="glyphicon glyphicon-ok"></i> 
							<?php echo $Translation['destination group']; ?>:
							<b><?php echo sqlValue("select name from membership_groups where groupID='{$destinationGroupID}'"); ?></b>
						</span>
					<?php } ?>
				</h3>
			</div>
			<div class="panel-body">
				<div class="step-details"><?php echo $Translation['destination group message']; ?></div>
				<div class="form-group">
					<label for="destinationGroupID" class="control-label col-sm-2"><?php echo $Translation['destination group']; ?></label>
					<div class="col-sm-6 col-md-4">
						<?php echo htmlSQLSelect("destinationGroupID", "select distinct membership_groups.groupID, name from membership_groups, membership_users where membership_groups.groupID=membership_users.groupID order by name", $destinationGroupID); ?>
						<span class="help-block"></span>
					</div>
					<div class="col-sm-4 col-md-2">
						<button type="submit" class="btn btn-block btn-primary">
							<i class="glyphicon glyphicon-ok"></i> 
							<?php echo ($destinationGroupID ? $Translation['update'] : $Translation['next step']); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>

	<?php if($destinationGroupID && $destinationGroupID == $sourceGroupID){ /* source group same as destination */ ?>
		<div id="step-4" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">
					<b><?php echo $Translation['step 4'] ; ?></b>
					<?php if($destinationMemberID){ ?>
						<span class="pull-right text-success">
							<i class="glyphicon glyphicon-ok"></i> 
							<?php echo $Translation['destination member']; ?>:
							<b><?php echo $destinationMemberID; ?></b>
						</span>
					<?php } ?>
				</h3>
			</div>
			<div class="panel-body">
				<div class="step-details"><?php echo $Translation['destination member message'] ; ?></div>
				<div class="form-group">
					<label for="sourceGroupID" class="control-label col-sm-2"><?php echo $Translation['destination member'] ; ?></label>
					<div class="col-sm-6 col-md-4">
						<?php
							echo htmlSQLSelect("destinationMemberID", "select lcase(memberID), lcase(memberID) from membership_users where groupID='$destinationGroupID' and lcase(memberID)!='$sourceMemberID' order by memberID", $destinationMemberID);
						?>
					</div>
				</div>
			</div>
		</div>

	<?php }elseif($destinationGroupID){ /* source group not same as destination */ ?>
		<div id="step-4" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">
					<b><?php echo $Translation['step 4'] ; ?></b>
				</h3>
			</div>
			<div class="panel-body">
				<div class="step-details">
					<?php
						$anon_group_safe = makeSafe($adminConfig['anonymousGroup'], false);
						$noMove = ($sourceGroupID == sqlValue("select groupID from membership_groups where name='{$anon_group_safe}'"));
						$destinationHasMembers = sqlValue("select count(1) from membership_users where groupID='{$destinationGroupID}'");

						if(!$noMove){
							echo $Translation['move records'] ; 
						}
					?>
				</div>

				<?php if($destinationHasMembers){ ?>
					<div class="form-group">
						<label class="col-xs-12 col-sm-8 col-md-6">
							<input type="radio" name="moveMembers" id="dontMoveMembers" value="0" <?php echo ($moveMembers ? "" : "checked"); ?>>
							<?php 
								echo $Translation['move data records to member'] . ' '; 
								echo htmlSQLSelect("destinationMemberID", "select lcase(memberID), lcase(memberID) from membership_users where groupID='$destinationGroupID' order by memberID", $destinationMemberID);
							?>
						</label>
					</div>
				<?php } ?>

				<?php if(!$noMove){ ?>
					<div class="form-group">
						<label class="col-md-10">
							<input type="radio" name="moveMembers" id="moveMembers" value="1" <?php echo ($moveMembers || !$destinationHasMembers ? "checked" : ""); ?>>
							<?php echo str_replace('<GROUPNAME>', sqlValue("select name from membership_groups where groupID='$destinationGroupID'"), $Translation['move source member to group']); ?>
						</label>
					</div>
				<?php } ?>

			</div>
		</div>
	<?php } ?>

	<?php if($destinationGroupID){ ?>
		<div class="row">
			<div class="col-sm-8 col-sm-offset-2 col-md-6 col-md-offset-3 col-lg-4 col-lg-offset-4">
				<button type="submit" name="beginTransfer" value="1" class="btn btn-lg btn-success btn-block" onClick="return jsConfirmTransfer();">
					<i class="glyphicon glyphicon-ok"></i> 
					<?php echo $Translation['begin transfer']; ?>
				</button>
			</div>
		</div>
	<?php } ?>
</form>

<style>
	.step-details{ margin: 1em 0; font-size: 1.2em; }
	form{ margin-bottom: 2em; }
</style>

<script>
	$j(function(){
		$j('select').addClass('form-control').attr('style', 'width: 100% !important;');

		/* when a select is changed, automatically apply the change */
		$j('select').change(function(){
			$j(this).parent().next().children('.btn').click();
		})
	})
</script>

<?php
	include("{$currDir}/incFooter.php");
?>

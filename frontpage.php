<?php
	include_once 'include/user.php';
	
	redirect_if_not_logged_in();
    #redirect_if_not_role('FullAdmin');
    $franchise = get_current_user_franchise();
		if(!current_user_has_role($franchise, 'FullAdmin') && !current_user_has_role($franchise,'Franchisee')){
			header("location: " . site_url() . "home.php");
			die();			
		}
	

	if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])){
		$safe_id = mysql_real_escape_string($_GET['id']);
		if(isset($_POST['Delete']) && current_user_has_role(1, 'FullAdmin')){
			$sql = "UPDATE `front_page_text` SET `DisplayEndDate` = '" . date("Y-m-d",time() - 86400) . "' WHERE `FrontPageTextID` =$safe_id LIMIT 1 ;";
			mysql_query($sql) or die(mysql_error());
			header("location: login.php");
		}
		$sql = "SELECT * FROM `front_page_text` WHERE `FrontPageTextID` = $safe_id LIMIT 1;";
		$result = mysql_query($sql) or die(mysql_error());
		$row = mysql_fetch_array($result);
		include_once 'include/header.php';
		?>
		<center><h2>Delete Text</h2></center>
		<center>Are you sure you want to delete this?</center>
		<table style="margin:auto;">
			<tr>
				<td><?php echo $row['Text']; ?></td>
			</tr>
			<tr>
				<td class="alignright" colspan="2">
					<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?action=delete&id=' . $_GET['id']; ?>">
						<input type="submit" name="Delete" value="Delete" />
					</form>
				</td>
			</tr>
		</table>
		<?php
	} else if(isset($_GET['action']) && $_GET['action'] == 'add'){
		if(isset($_POST['Text'])){
			$safe_text = mysql_real_escape_string($_POST['Text']);
			$start_date = $_POST['StartYear'] . '-' . $_POST['StartMonth'] . '-' . $_POST['StartDay'];
			$end_date = $_POST['EndYear'] . '-' . $_POST['EndMonth'] . '-' . $_POST['EndDay'];
			if($_POST['EndNever']){
				$end_date = '3000-01-01';
			}
			$sql = "INSERT INTO `front_page_text` (`AddedDate`, `AddedByUser`, `DisplayStartDate`, `DisplayEndDate`, `Text`)
			 VALUES ('" . date("Y-m-d") . "', '" . get_current_user_id() . "', '$start_date', '$end_date', '$safe_text');";
			 mysql_query($sql);
			 header("Location: login.php");
		}		
		include_once 'include/header.php';
		?>
		<h2><center>Add Content</center></h2>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?action=add">
			<table style="margin:auto;">
				<tr>
					<td>Starting Date:</td>
					<td>
						<?php
							get_date_drop_downs("Start",date("Y-m-d"));
						?>
					</td>
				</tr>
				<tr>
					<td>Ending Date:</td>
					<td>
						<?php
							get_date_drop_downs("End",date("Y-m-d"));
						?>
						<br>
						or<br>
						<input type="checkbox" name="EndNever"> Forever
					</td>
				</tr>
				<tr>
					<td colspan="2">
						Message:<br>
						<textarea name="Text" style="width:400px; height:150px;"></textarea>
					</td>
				</tr>
				<tr>
					<td class="alignright" colspan="2"><input type="submit" name="Save" value="Save" /></td>
				</tr>
			</table>
		</form>
		<?php
	} else if(isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])){
		$safe_id = mysql_real_escape_string($_GET['id']);
		if(isset($_POST['Text'])){
			$safe_text = mysql_real_escape_string($_POST['Text']);
			$safe_start_date = mysql_real_escape_string($_POST['StartYear'] . '-' . $_POST['StartMonth'] . '-' . $_POST['StartDay']);
			$safe_end_date = mysql_real_escape_string($_POST['EndYear'] . '-' . $_POST['EndMonth'] . '-' . $_POST['EndDay']);
			if($_POST['EndNever']){
				$safe_end_date = '3000-01-01';
			}
			$sql = "UPDATE `front_page_text` SET `AddedDate` = '" . date("Y-m-d") . "',
												 `AddedByUser` = '" . get_current_user_id() . "',
												 `DisplayStartDate` = '$safe_start_date',
												 `DisplayEndDate` = '$safe_end_date',
												 `Text` = '$safe_text' 
					WHERE `FrontPageTextID` =$safe_id LIMIT 1 ;";
			 mysql_query($sql);
			 header("Location: login.php");
		}
		$sql = "SELECT * FROM `front_page_text` WHERE `FrontPageTextID` =$safe_id LIMIT 1;";
		$result = mysql_query($sql) or die(mysql_error() . $sql);
		$row = mysql_fetch_array($result);
		include_once 'include/header.php';
		$selected_start_date = $row['DisplayStartDate'];
		$selected_end_date = $row['DisplayEndDate'];
		if($row['DisplayEndDate'] == '3000-01-01')
			$selected_end_date = get_date(date("Y-m-d"));
		?>
			<h2><center>Edit Content</center></h2>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?action=edit&id=<?php echo $_GET['id']; ?>">
				<table style="margin:auto;">
					<tr>
						<td>Starting Date:</td>
						<td>
							<?php
								get_date_drop_downs("Start",$selected_end_date);
							?>
						</td>
					</tr>
					<tr>
						<td>Ending Date:</td>
						<td>
							<?php
								get_date_drop_downs("End",$selected_end_date);
							?>
							<br>
							or<br>
							<input type="checkbox" name="EndNever" <?php if($row['DisplayEndDate'] == '3000-01-01') echo 'checked=""'; ?>> Forever
						</td>
					</tr>
					<tr>
						<td colspan="2">
							Message:<br>
							<textarea name="Text" style="width:400px; height:150px;"><?php echo $row['Text']; ?></textarea>
						</td>
					</tr>
					<tr>
						<td class="alignright" colspan="2"><input type="submit" name="Save" value="Save" /></td>
					</tr>
				</table>
			</form>
		<?php
	} else {
		header("location: " . site_url());
	}
	include_once 'include/footer.php';
?>
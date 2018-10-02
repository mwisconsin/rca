<?php
	include_once 'include/news_item.php';
	include_once 'include/user.php';
	include_once 'include/date_time.php';
	
	redirect_if_not_logged_in();

	$franchise = get_current_user_franchise();
	
	if(current_user_has_role(1, "FullAdmin") || current_user_has_role($franchise, "Franchisee")){
		header("Location: home.php");
		die();	
	}

	
	if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])){
		$safe_id = mysql_real_escape_string($_GET['id']);
		if(isset($_POST['Delete'])){
			remove_news_item($_GET['id']);
			header("location: login.php");
		}
		$sql = "SELECT * FROM `news_item` WHERE `NewsItemID` = $safe_id LIMIT 1;";
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
			$safe_franchise = mysql_real_escape_string($franchise);
			$start_date = $_POST['StartYear'] . '-' . $_POST['StartMonth'] . '-' . $_POST['StartDay'];
			$end_date = $_POST['EndYear'] . '-' . $_POST['EndMonth'] . '-' . $_POST['EndDay'];
			if($_POST['EndNever']){
				$end_date = '3000-01-01';
			}
			$sql = "INSERT INTO `news_item` (`AddedDate`, `AddedByUser`, `DisplayStartDate`, `DisplayEndDate`, `Text`, `FranchiseID`)
			 VALUES ('" . date("Y-m-d") . "', '" . get_current_user_id() . "', '$start_date', '$end_date', '$safe_text', $safe_franchise);";
			 mysql_query($sql);
			 header("Location: login.php");
		}		
		include_once 'include/header.php';
		$todays_date = get_date(date("Y-m-d"));
		?>
		<h2><center>Add Content</center></h2>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?action=add">
			<table style="margin:auto;">
				<tr>
					<td>Starting Date:</td>
					<td>
						<select name="StartMonth">
							<option value="1" <?php if($todays_date['Month'] == 1) echo 'SELECTED'; ?>>January</option>
							<option value="2" <?php if($todays_date['Month'] == 2) echo 'SELECTED'; ?>>February</option>
							<option value="3" <?php if($todays_date['Month'] == 3) echo 'SELECTED'; ?>>March</option>
							<option value="4" <?php if($todays_date['Month'] == 4) echo 'SELECTED'; ?>>April</option>
							<option value="5" <?php if($todays_date['Month'] == 5) echo 'SELECTED'; ?>>May</option>
							<option value="6" <?php if($todays_date['Month'] == 6) echo 'SELECTED'; ?>>June</option>
							<option value="7" <?php if($todays_date['Month'] == 7) echo 'SELECTED'; ?>>July</option>
							<option value="8" <?php if($todays_date['Month'] == 8) echo 'SELECTED'; ?>>August</option>
							<option value="9" <?php if($todays_date['Month'] == 9) echo 'SELECTED'; ?>>September</option>
							<option value="10" <?php if($todays_date['Month'] == 10) echo 'SELECTED'; ?>>October</option>
							<option value="11" <?php if($todays_date['Month'] == 11) echo 'SELECTED'; ?>>November</option>
							<option value="12" <?php if($todays_date['Month'] == 12) echo 'SELECTED'; ?>>December</option>
						</select> / 
						<select name="StartDay">
							<?php
								for($i = 1; $i <= 32; $i++)
								{
									echo '<option value="' . $i . '"';
									if($todays_date['Day'] == $i)
										echo 'SELECTED';
									echo '>' . $i . '</option>';
								}
							?>
						</select> / 
						<select name="StartYear">
							<?php
								for($i = (int)date("Y"); $i <= (int)date("Y")+ 5; $i++)
								{
									echo '<option value="' . $i . '"';
									if($todays_date['Year'] == $i)
										echo 'SELECTED';
									echo '>' . $i . '</option>';
								}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td>Ending Date:</td>
					<td>
						<select name="EndMonth">
							<option value="1" <?php if($todays_date['Month'] == 1) echo 'SELECTED'; ?>>January</option>
							<option value="2" <?php if($todays_date['Month'] == 2) echo 'SELECTED'; ?>>February</option>
							<option value="3" <?php if($todays_date['Month'] == 3) echo 'SELECTED'; ?>>March</option>
							<option value="4" <?php if($todays_date['Month'] == 4) echo 'SELECTED'; ?>>April</option>
							<option value="5" <?php if($todays_date['Month'] == 5) echo 'SELECTED'; ?>>May</option>
							<option value="6" <?php if($todays_date['Month'] == 6) echo 'SELECTED'; ?>>June</option>
							<option value="7" <?php if($todays_date['Month'] == 7) echo 'SELECTED'; ?>>July</option>
							<option value="8" <?php if($todays_date['Month'] == 8) echo 'SELECTED'; ?>>August</option>
							<option value="9" <?php if($todays_date['Month'] == 9) echo 'SELECTED'; ?>>September</option>
							<option value="10" <?php if($todays_date['Month'] == 10) echo 'SELECTED'; ?>>October</option>
							<option value="11" <?php if($todays_date['Month'] == 11) echo 'SELECTED'; ?>>November</option>
							<option value="12" <?php if($todays_date['Month'] == 12) echo 'SELECTED'; ?>>December</option>
						</select> / 
						<select name="EndDay">
							<?php
								for($i = 1; $i <= 32; $i++)
								{
									echo '<option value="' . $i . '"';
									if($todays_date['Day'] == $i)
										echo 'SELECTED';
									echo '>' . $i . '</option>';
								}
							?>
						</select> / 
						<select name="EndYear">
							<?php
								for($i = (int)date("Y"); $i <= (int)date("Y")+ 5; $i++)
								{
									echo '<option value="' . $i . '"';
									if($todays_date['Year'] == $i)
										echo 'SELECTED';
									echo '>' . $i . '</option>';
								}
							?>
						</select><br>
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
			$sql = "UPDATE `news_item` SET `AddedDate` = '" . date("Y-m-d") . "',
												 `AddedByUser` = '" . get_current_user_id() . "',
												 `DisplayStartDate` = '$safe_start_date',
												 `DisplayEndDate` = '$safe_end_date',
												 `Text` = '$safe_text'
					WHERE `NewsItemID` =$safe_id LIMIT 1 ;";
			 mysql_query($sql);
			 header("Location: login.php");
		}
		$sql = "SELECT * FROM `news_item` WHERE `NewsItemID` =$safe_id LIMIT 1;";
		$result = mysql_query($sql) or die(mysql_error() . $sql);
		$row = mysql_fetch_array($result);
		include_once 'include/header.php';
		$selected_start_date = get_date($row['DisplayStartDate']);
		$selected_end_date = get_date($row['DisplayEndDate']);
		if($row['DisplayEndDate'] == '3000-01-01')
			$selected_end_date = get_date(date("Y-m-d"));
		?>
			<h2><center>Edit Content</center></h2>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?action=edit&id=<?php echo $_GET['id']; ?>">
				<table style="margin:auto;">
					<tr>
						<td>Starting Date:</td>
						<td>
							<select name="StartMonth">
								<option value="1" <?php if($selected_start_date['Month'] == 1) echo 'SELECTED'; ?>>January</option>
								<option value="2" <?php if($selected_start_date['Month'] == 2) echo 'SELECTED'; ?>>February</option>
								<option value="3" <?php if($selected_start_date['Month'] == 3) echo 'SELECTED'; ?>>March</option>
								<option value="4" <?php if($selected_start_date['Month'] == 4) echo 'SELECTED'; ?>>April</option>
								<option value="5" <?php if($selected_start_date['Month'] == 5) echo 'SELECTED'; ?>>May</option>
								<option value="6" <?php if($selected_start_date['Month'] == 6) echo 'SELECTED'; ?>>June</option>
								<option value="7" <?php if($selected_start_date['Month'] == 7) echo 'SELECTED'; ?>>July</option>
								<option value="8" <?php if($selected_start_date['Month'] == 8) echo 'SELECTED'; ?>>August</option>
								<option value="9" <?php if($selected_start_date['Month'] == 9) echo 'SELECTED'; ?>>September</option>
								<option value="10" <?php if($selected_start_date['Month'] == 10) echo 'SELECTED'; ?>>October</option>
								<option value="11" <?php if($selected_start_date['Month'] == 11) echo 'SELECTED'; ?>>November</option>
								<option value="12" <?php if($selected_start_date['Month'] == 12) echo 'SELECTED'; ?>>December</option>
							</select> / 
							<select name="StartDay">
								<?php
									for($i = 1; $i <= 32; $i++)
									{
										echo '<option value="' . $i . '"';
									if($selected_start_date['Day'] == $i)
										echo 'SELECTED';
									echo '>' . $i . '</option>';
									}
								?>
							</select> / 
							<select name="StartYear">
								<?php
									for($i = (int)date("Y"); $i <= (int)date("Y")+ 5; $i++)
									{
										echo '<option value="' . $i . '"';
										if($selected_start_date['Year'] == $i)
											echo 'SELECTED';
										echo '>' . $i . '</option>';
									}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td>Ending Date:</td>
						<td>
							<select name="EndMonth">
								<option value="1" <?php if($selected_end_date['Month'] == 1) echo 'SELECTED'; ?>>January</option>
								<option value="2" <?php if($selected_end_date['Month'] == 2) echo 'SELECTED'; ?>>February</option>
								<option value="3" <?php if($selected_end_date['Month'] == 3) echo 'SELECTED'; ?>>March</option>
								<option value="4" <?php if($selected_end_date['Month'] == 4) echo 'SELECTED'; ?>>April</option>
								<option value="5" <?php if($selected_end_date['Month'] == 5) echo 'SELECTED'; ?>>May</option>
								<option value="6" <?php if($selected_end_date['Month'] == 6) echo 'SELECTED'; ?>>June</option>
								<option value="7" <?php if($selected_end_date['Month'] == 7) echo 'SELECTED'; ?>>July</option>
								<option value="8" <?php if($selected_end_date['Month'] == 8) echo 'SELECTED'; ?>>August</option>
								<option value="9" <?php if($selected_end_date['Month'] == 9) echo 'SELECTED'; ?>>September</option>
								<option value="10" <?php if($selected_end_date['Month'] == 10) echo 'SELECTED'; ?>>October</option>
								<option value="11" <?php if($selected_end_date['Month'] == 11) echo 'SELECTED'; ?>>November</option>
								<option value="12" <?php if($selected_end_date['Month'] == 12) echo 'SELECTED'; ?>>December</option>
							</select> / 
							<select name="EndDay">
								<?php
									for($i = 1; $i <= 32; $i++)
									{
										echo '<option value="' . $i . '"';
										if($selected_end_date['Day'] == $i)
											echo 'SELECTED';
										echo '>' . $i . '</option>';
									}
								?>
							</select> / 
							<select name="EndYear">
								<?php
									for($i = (int)date("Y"); $i <= (int)date("Y")+ 5; $i++)
									{
										echo '<option value="' . $i . '"';
										if($selected_end_date['Day'] == $i)
											echo 'SELECTED';
										echo '>' . $i . '</option>';
									}
								?>
							</select><br>
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

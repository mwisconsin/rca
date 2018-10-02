<?php
	require_once('include/user.php');
	require_once('include/link.php');
	
	$franchise = get_current_user_franchise();
	
	if(!current_user_has_role(1, "FullAdmin") && !current_user_has_role($franchise, "Franchisee")){
		die("<script type=\"text/javascript\">self.close();</script>");	
	}
	
	if($_POST['LinkID']){
		$result = set_link_note($_POST['LinkID'], $_POST['LinkNote']);
	}
	$link = get_link_note($_GET['id']);
	$pref = get_user_rider_preferences($link['RiderUserID']);
?>
<html>
	<header>
		<title>Link Notes</title>
		<style>
			body
			{ 
				margin: 20px;
				
			}
			HTML, BODY, FORM
			{
				height: 100%;
			}
			form
			{
			   overflow: hidden;

			}
			
		</style>
		
	</header>
	<body>
		<center><span style="font-size:1.7em;">Link Notes | Ride <?php echo $link['LinkID'];?></span></center>
		<hr>
		Rider Notes: <?php echo $pref['OtherNotes']; ?><br>
		<?php if($pref['HasCaretaker'] == 'Yes'){ 
			$name = get_displayable_person_name_string(get_name($pref['CaretakerID']));
			echo "Caretaker: $name, " . ($pref['CareTakerBackgroundCheck'] == 'Yes' ? "Background Checked" : "Background Not Checked");
		} ?>
		<form method="post" >
			<input type="hidden" name="LinkID" value="<?php echo $link['LinkID']; ?>" />
			<textarea name="LinkNote" style="width:100%; height:40%;"><?php echo $link['LinkNote']; ?></textarea>
			<input type="submit" name="SaveAndClose" value="Save and Close" style="width:100%;" />
		</form>
		<?php
		if(isset($_POST['SaveAndClose']))
		echo "<script>window.close();</script>";
		?>
		<br/>
		<br/>
	</body>
</html>
<?php if($result) { ?>
<script type="text/javascript">
	<?php 
		if($link['LinkNote'] != '') {
	?>
			window.opener.activateNote(<?php echo $_GET['id']; ?>);
	<?php
		} else {
	?>
			window.opener.deactivateNote(<?php echo $_GET['id']; ?>);
	<?php
		}
	?>
	window.close();
</script>
<?php } ?>

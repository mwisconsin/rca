<?php
	error_reporting(E_STRICT);
 	require_once('classes/user.php');
 	$current_user = new User(-1, -1, false);
	include_once 'include/user.php';
    include_once 'include/header.php';

?>
<div style="float:right; clear:both; width:320px; border:solid 1px #000; padding:2px; margin:0px 30px 0px 15px; text-align:center;">
    <span style="font:24px bold;">Login here</span>
    <br />
    <?php
        if(isset($_GET['login']) && $_GET['login'] == 'failed')
            echo '<div style="border:solid 1px; #ff0000; margin:5px; text-align:center; padding:5px;">Your username or password was wrong.</div>';
    ?>
    <br />
    <form method="post" action="<?php echo site_url() . 'login-action.php'; ?>">
        <table style="text-align:center; margin:auto;">
            <tr>
                <td style="font-weight:bold; text-align:right;">User ID</td>
                <td><input name="UserName" id="txt_user_name" type="text" value="<?php echo $_COOKIE['UserName']; ?>" style="width:200px;" /></td>
            </tr>
            <tr>
                <td></td>
                <td style="font-size:9px;">
                    Often your entire Email address<br/>
                    or Your 16 digit Riders Club ID
                </td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Password</td>
                <td><input name="Password" type="password" style="width:200px;" /></td>
            </tr>
        </table>
        <input name="Remember" type="checkbox"<?php if(isset($_COOKIE['UserName']) && $_COOKIE['UserName'] != NULL) echo ' checked="checked"'; ?> /> Remember me the next time I login<br /><br />
        
        <input name="Login" value="Enter" type="submit" /> <input type="button" value="I'm new. Help me get started." onclick="window.location = '<?php echo site_url() . "apply.php"; ?>';"><br /><br />
        <a href="<?php echo site_url(); ?>login_help.php?field=username">I forgot my User ID</a> • <a href="<?php echo site_url(); ?>login_help.php?field=password">I forgot my Password</a><br><br>
    </form>
</div>
<h1>Welcome to Riders Club of America</h1>
<p>
	If you have wondered how you can live without your car, it's easy! Riders Club of America takes the worry out of driving so you can enjoy getting where you need to go, when you need to be there.
</p>
<p>
	Wondering how to care for others when they live far away?  Riders Club of America can provide safe, effective transport for your loved ones even when your schedule does not allow you to drive for them. Come join our team, and enjoy peace of mind.
</p>
<div style="width:500px;">
<?php
	$sql = "SELECT * FROM `front_page_text` WHERE `DisplayStartDate` <= '" . date("Y-m-d") . "' AND `DisplayEndDate` >= '" . date("Y-m-d") . "';";
	$result = mysql_query($sql);
	if(is_logged_in())
		$franchise = get_current_user_franchise(FALSE);
	while($row = mysql_fetch_array($result)){
		echo '<p>';
		echo $row['Text'];
		if(is_logged_in() && current_user_has_role($franchise, 'FullAdmin')){
			echo ' - <a href="' . site_url() . 'frontpage.php?action=delete&id=' . $row['FrontPageTextID'] . '">Delete</a>';
			echo ' <a href="' . site_url() . 'frontpage.php?action=edit&id=' . $row['FrontPageTextID'] . '">Edit</a>';
		}
			
		echo '</p>';	
	}
	if(is_logged_in() && current_user_has_role(1, 'FullAdmin'))
		echo '<a href="' . site_url() . 'frontpage.php?action=add">Add New Paragraph</a>';
	echo '</div>';
    include_once 'include/footer.php';
	
?>

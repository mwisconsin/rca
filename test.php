<?php

require_once('classes/user.php');
//include_once 'include/user.php';

echo 'Hello';

echo "Another Hello!!!!";




exit();


$_SESSION['UserID'] = 657;
$_SESSION['AffectedUserID'] = 553;
$_SESSION['UserFranchiseID'] = 2;

if (! $user){
$user = new User(-1, 0, false);
}

if ($user->get_working_as_user_id() == 657){
} else {
echo "Invalid userId = " . $user->get_working_as_user_id() ."<br>";
}

unset($user);


if (! $user){
$user = new User(-1, 0, false);
}

if ($user->get_working_as_user_id() == 657){
} else {
echo "Invalid userId = " . $user->get_working_as_user_id() ."<br>";
}
if ($user->get_header_string() == "Bob Schuette, 319-377-1394, rdschuette@gmail.com, 970 12TH ST, MARION, IA 52302-3546"){
} else {
echo "Invalid $user->get_header_string() = '" . $user->get_header_string() . "'"; 
}
echo '<br>';
foreach ($user->_roles as $role){
	echo $role->_ClubID . ' - ' . $role->_Role;
	echo '<br>';
}
echo '<br>';
if ($user->has_club_role(2,'Driver')){
	echo "club Driver";
} else {
	echo "not club Driver";
}
echo '<br>';
if ($user->has_club_role(2,'FullAdmin')){
	echo "club FullAdmin";
} else {
	echo "not club FullAdmin";
}
echo '<br>';
if ($user->has_club_role(2, array('FullAdmin','souserone'))){
	echo "club FullAdmin or souserone";
} else {
	echo "not club FullAdmin or souserone";
}
echo '<br>';
if ($user->has_club_or_system_role(2,'FullAdmin')){
	echo "system FullAdmin";
} else {
	echo "not system FullAdmin";
}
echo '<br>';
if ($user->has_club_or_system_role(2, array('souserone','FullAdmin'))){
	echo "system FullAdmin or souserone";
} else {
	echo "not system FullAdmin or souserone";
}
echo '<br>';
echo '<br>';
echo '<br>';
$user = new User(-1,-1, false);
echo "working as = " . $user->get_working_as_user_id() . "<br>";
echo $user->get_header_string();
echo '<br>';
foreach ($user->_roles as $role){
	echo $role->_ClubID . ' - ' . $role->_Role;
	echo '<br>';
}
echo '<br>';
if ($user->working_as_has_club_role(2,'Driver')){
	echo "working as club Driver";
} else {
	echo "not working as club Driver";
}
echo '<br>';
if ($user->working_as_has_club_role(2,'FullAdmin')){
	echo "working as club FullAdmin";
} else {
	echo "not working as club FullAdmin";
}
echo '<br>';
if ($user->working_as_has_club_role(2, array('FullAdmin','souserone'))){
	echo "working as club FullAdmin or souserone";
} else {
	echo "not working as club FullAdmin or souserone";
}
echo '<br>';
if ($user->has_club_or_system_role(2,'FullAdmin')){
	echo "system FullAdmin";
} else {
	echo "not system FullAdmin";
}
echo '<br>';
if ($user->has_club_or_system_role(2, array('souserone','FullAdmin'))){
	echo "system FullAdmin or souserone";
} else {
	echo "not system FullAdmin or souserone";
}
echo '<br>';
?>

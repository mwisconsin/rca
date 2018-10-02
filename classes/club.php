<?php
require_once('include/database.php');

class Club{
	private $_UserID;
	private $_Title;
	private $_FirstName;
	private $_MiddleInitial;
	private $_LastName;
	private $_Suffix;
	private $_Address1;
	private $_Address2;
	private $_City;
	private $_State;
	private $_Zip5;
	private $_Zip4;
	private $_EmailAddress;
	private $_PhoneNumber;
	private $_club_id;
	private $_LargeFacilityID;
	private $_working_as;
	public $_roles;
	
    function __construct($user_id = 0, $working_as = 0, $should_redirect = true){
		
		if(isset($_SESSION['UserFranchiseID'])){
			$this->_club_id = $_SESSION['UserFranchiseID'];
		} else {
			if($should_redirect){
				$_SESSION['RedirectURL'] = $_SERVER['PHP_SELF'];
				header("Location: select_club.php");
			}
		}
		
		// use current session user ID if -1
		if($user_id == -1) 
			if(isset($_SESSION['UserID']))
				$user_id = $_SESSION['UserID'];
		$safe_user_id = mysql_real_escape_string ( $user_id );
		$sql = 
		"SELECT n.Title, n.FirstName, n.MiddleInitial, n.LastName, n.Suffix, a.Address1, a.Address2, a.City, a.State, a.Zip5, a.Zip4, a.DefaultFranchiseID
		 e.EmailAddress, p.PhoneNumber, p.PhoneType,
		 FROM users u
		 NATURAL LEFT JOIN person_name n 
		 NATURAL LEFT JOIN user_address ua 
		 NATURAL LEFT JOIN address a 
		 LEFT JOIN email e ON u.EmailID = e.EmailID 
		 NATURAL LEFT JOIN user_phone up 
		 NATURAL LEFT JOIN phone p 
		 WHERE UserID = $safe_user_id && AddressType = 'Physical' 
		 ORDER BY IsPrimary, PhoneType ";
		 
		 //echo $safe_user_id;
		 //echo $sql . '<br>';
		$result = mysql_query( $sql );
		if($result){
			while($row = mysql_fetch_array($result)){
				$this->_Title = $row['Title'];	
				$this->_FirstName = $row['FirstName'];	
				$this->_MiddleInitial = $row['MiddleInitial'];	
				$this->_LastName = $row['LastName'];	
				$this->_Suffix= $row['Suffix'];	
				$this->_Address1 = $row['Address1'];	
				$this->_Address2 = $row['Address2'];	
				$this->_City = $row['City'];	
				$this->_State = $row['State'];	
				$this->_Zip5 = $row['Zip5'];	
				$this->_Zip4 = $row['Zip4'];	
				$this->_EmailAddress = $row['EmailAddress'];	
				$this->_PhoneNumber = $row['PhoneNumber'];	
				if (!isset($this->_club_id)){
					if(is_null($row['DefaultFranchiseID'])){
						$this->_club_id = 2;
					} else{
						$this->_club_id =$row['DefaultFranchiseID'];
				}
					}
				}
			}
			$this->_UserID = $user_id;
			$this->_working_as = $this;
		} else {
			echo mysql_error();
			rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
							"Error creating user", $sql);
			return;
		}
		
// get roles		
		$this->_roles = array();
		$sql = 
		"SELECT *
		 FROM user_role
		 WHERE UserID = $safe_user_id";
		$result = mysql_query( $sql );
		if($result){
			while($row = mysql_fetch_array($result)){
				$this->_roles[] = new UserRole($row['FranchiseID'], $row['Role']);	
			}
		} else {
			echo mysql_error();
			rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
							"Error creating user roles", $sql);
		}
		
// get large facility if any
		$this->_LargeFacilityID = -1;
		if ($this->has_club_role($this->_club_id, 'LargeFacilityAdmin')) {
			$sql = "SELECT LargeFacilityID, UserID
					FROM large_facility_user
					WHERE UserID = $safe_user_id";

			$result = mysql_query($sql);

			if ($result) {
				$row = mysql_fetch_array($result, MYSQL_ASSOC);
				$this->_LargeFacilityID = $row['LargeFacilityID'];
			} else {
				rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
								"Error retrieving large facility id for user $user_id", $sql);
				rc_log_db_error(PEAR_LOG_ERR, 'CONTINUED', debug_backtrace(), '');         
			}
		}
		
// now if working as is -1 then see if they are working as someone else
		if ($working_as == -1 && $this->has_club_or_system_role($this->_club_id, array('FullAdmin','Franchisee')))
			$working_as = $_SESSION['AffectedUserID'];
			//&&
		    //($_SESSION['AffectedUserID'] && $_SESSION['UserID'] && $_SESSION['AUIDREQ'] &&
            //sha1('AUIDSECURITYHASH' . $_SESSION['AffectedUserID'] . $_SESSION['UserID']) == $_SESSION['AUIDREQ'])) 

		// get working as if specified
		if ($working_as > 0)
			$this->_working_as = new User($working_as, 0, false);

	}  //end of __construct
	
	function get_header_string(){
		$output_string = "";
		if ($this->_working_as->_Title != "") {$output_string .= $this->_working_as->_Title . " ";}
		$output_string .= $this->_working_as->_FirstName . " ";
		if ($this->_working_as->_MiddleInitial != "") {$this->_working_as->_MiddleInitial . ". ";}
		$output_string .= $this->_working_as->_LastName ;
		if ($this->_working_as->_Suffix != "") {$output_string .= " " . $this->_working_as->_Suffix;}
		$output_string .= ', ';
		$output_string .= $this->_working_as->_PhoneNumber . ", ";
		$output_string .= $this->_working_as->_EmailAddress . ", ";
		$output_string .= $this->_working_as->_Address1 ;
		if ($this->_working_as->_Address2 != "") {$output_string .= " " . $this->_working_as->_Address2;}
		$output_string .= ', '. $this->_working_as->_City . ", ";
		$output_string .= $this->_working_as->_State . " ";
		$output_string .= $this->_working_as->_Zip5 ;
		if ($this->_working_as->_Zip4 != "") {$output_string .= "-" .$this->_working_as->_Zip4 ;}
	return $output_string;
	}  //end of get_header_string

	public function get_working_as_user_id(){
		return $this->_working_as->_UserID;
	}
	public function has_club_role($club_id = 0, $role = ""){
		foreach($this->_roles as $user_role){
			if (is_array($role)){
				foreach($role as $role_entry){
					if ($user_role->is_equal($club_id, $role_entry)){
						return true;
					}
				}
			} else {
				if ($user_role->is_equal($club_id, $role)){
					return true;
				}
			}
		}
		return false;
	} // end of has_club_role

	public function has_club_or_system_role($club_id = 0, $role = ""){
		if ($club_id <= 0)
			$club_id = $this->_club_id;
		if ($this->has_club_role($club_id, $role)){
			return true;
		}
		return $this->has_club_role(1, $role);
	} // end of has_club_or_system_role

	public function working_as_has_club_role($club_id = 0, $role = ""){
		if ($club_id <= 0)
			$club_id = $this->_club_id;
		foreach($this->_working_as->_roles as $user_role){
			if (is_array($role)){
				foreach($role as $role_entry){
					if ($user_role->is_equal($club_id, $role_entry)){
						return true;
					}
				}
			} else {
				if ($user_role->is_equal($club_id, $role)){
					return true;
				}
			}
		}
		return false;
	} // end of has_club_role

	public function working_as_has_club_or_system_role($club_id = 0, $role = ""){
		if ($club_id <= 0)
			$club_id = $this->_club_id;
		if ($this->working_as_has_club_role($club_id, $role)){
			return true;
		}
		return $this->working_as_has_club_role(1, $role);
	} // end of has_club_or_system_role

	public function get_club_id_id(){
		return $this->__club_id;
	}

	public function get_large_facility_id(){
		return $this->_LargeFacilityID;
	}
}  // end of user class

class UserRole{
	public $_ClubID;
	public $_Role;
	
	public function __construct($club_id, $role){
		$this->_ClubID = (integer)$club_id;
		$this->_Role = $role;
	} // end of __ construct
	
	public function is_equal($club_id, $role){
		if ($this->_ClubID == $club_id && $this->_Role == $role){
			return true;
		} else {
			return false;
		} 
	} // end of is_equal
}  // end of UserRole class
?>
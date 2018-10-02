<?php
//if(! isset($_SESSION)){
//	session_start();
//}

require_once('include/database.php');

class Address{

	private $_address_id;
	private $_address1;
	private $_address2;
	private $_city;
	private $_state;
	private $_zip5;
	private $_zip4;
	private $_latitude;
	private $_logitude;
	private $_is_verified;
	private $_verify_source;
	
	function __construct($address_id, $address1, $address2, $city, $state, $zip5, $zip4 = "", $latitude = "",
			             $logitude = "", $is_verified = "", $verify_source = "" )
	{
		$this->_address_id = $address_id;
		$this->_address1 = $address1;
		$this->_address2 = $address2;
		$this->_city = $city;
		$this->_state = $state;
		$this->_zip5 = $zip5;
		$this->_zip4 = $zip4;
		$this->_latitude = $latitude;
		$this->_logitude = $logitude;
		$this->_is_verified = $is_verified;
		$this->_verify_source = $verify_source;
	}
}  // end of user class
	
<?php
	chdir('..');
	include_once 'include/user.php';
	include_once 'include/db_donation.php';
    include_once 'include/name.php';
	include_once 'include/address.php';
	$donation_id = mysql_real_escape_string($_REQUEST['id']);
	
	if(!if_current_user_has_role('FullAdmin') && !(isset($_REQUEST['hash']) && $_REQUEST['hash'] == get_donation_hash($donation_id))){
		die('You do not have access to this document. <a href="' . site_url() . '">Return Home</a>');
	}
	
	$sql = "SELECT * FROM donation WHERE DonationID = $donation_id LIMIT 1;";
	$result = mysql_fetch_array( mysql_query( $sql ) );
	$name = get_name( $result['DonorNameID'] );
	$address = get_address( $result['DonorAddressID'] );
	
	require('fpdf.php');

class PDF extends FPDF
{
	function Footer(){
	    //Go to 1.5 cm from bottom
	    $this->SetY(-15);
	    $this->SetFont('Times','B',10);
	    $this->Cell(0,0,'PLEASE KEEP THIS RECIPT FOR YOUR RECORDS',0,1,'C');
		$this->SetFont('Times','',10);
		$this->Cell(0,7,'Questions regarding this gift receipt should be directed to the Corporate Offices at 319-365-1511, or',0,1,'C');
		$this->Cell(0,1,'mail us at: Riders Club of America, 1700 B Ave NE  #213 - Cedar Rapids, IA - 52402-5421 ',0,1,'C');
		
	}
}
function get_date($date){
	return array('Month' => (int)substr($date,5,6),
				 'Day' => (int)substr($date,8,9),
				 'Year' => (int)substr($date,0,4));
}
$months = array('January','February','March','April','May','June','July','August','September','October','November','December');
$date = get_date($result['DonationTime']);

if($result['PaymentType'] == 'CREDIT')
	$acknowedgement = ' As you requested,this gift has been charged to your credit card.';
else if($result['PaymentType'] == 'MAILED' && $result['CheckNumber'] != NULL)
	$acknowedgement = " This letter acknowledges the receipt of your check #{$result['CheckNumber']}.";
else
	$acknowedgement = "This acknowleges the arrival of your donation.";

$pdf=new PDF();
$pdf->AddPage();
$pdf->Image('documents/images/large-logo2.jpg',(105-45),null,90);
$pdf->ln(5);
$pdf->SetFont('Times','',12);
$pdf->Write(5,"{$name['FirstName']} {$name['LastName']}");
$pdf->ln(5);
$pdf->Write(5,"{$address['Address1']}");
$pdf->ln(5);
$pdf->Write(5,"{$address['City']}, {$address['State']}, {$address['ZIP5']}");
$pdf->ln(10);
$pdf->Write(5,'Thank you for your recent gift in support of Riders Club of America.');
$pdf->ln(10);
$pdf->Write(5,'Your gift is greatly appreciated and does make a difference in strengthening the services we provide to those in need of transportation.');
$pdf->ln(10);
$pdf->Write(5,'Gifts from friends of Riders Club of America are increasingly essential for helping us fulfill our mission.  Much of our reputation has depended on, and will more and more depend on private gifts and your own valued participation.  Again, Thank you.');
$pdf->ln(10);
$pdf->Write(5,'Sincerely,');
$pdf->ln(10);
$pdf->Write(5,'Jim Balvanz');
$pdf->ln(5);
$pdf->Write(5,'Treasurer');
$pdf->ln(45);
$pdf->SetDrawColor(0,0,0);
$pdf->Line(10,160,200,160);
$pdf->Cell(0,1,'Receipt: ' . $result['DonorNameID'],0,1,'R');
$pdf->Write(5, date("F j, Y"));
$pdf->ln(10);
$pdf->Write(5,"{$name['FirstName']} {$name['LastName']}");

$pdf->ln(5);
$pdf->Write(5,"{$address['Address1']}");
$pdf->ln(5);
$pdf->Write(5,"{$address['City']}, {$address['State']}, {$address['ZIP5']}");
$pdf->ln(10);
$pdf->Write(5,'The officers and directors of the Riders Club of America greatly acknowledge your gift of $' . ($result['DonationCents'] / 100) . '.00 on ' . $months[$date['Month'] - 1] . ' ' . $date['Day'] . ', ' . $date['Year'] . '.' . $acknowedgement);
$pdf->SetFont('Times','B',12);
$pdf->Write(5," Riders Club of America did not provide any goods or services in return for your gift.");
$pdf->Line(10,225,200,225);
$pdf->Output("Thankyou_Letter_" . time() . ".pdf","I");
chdir('xhr/');
?>
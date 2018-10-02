<?php
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
		$this->Cell(0,1,'mail us at: Riders Club of America, 1700 B Ave NE  #213 &bull; Cedar Rapids, IA &bull; 52402-5421 ',0,1,'C');
		
	}
}

$pdf=new PDF();
$pdf->AddPage();
$pdf->Image('images/large-logo2.jpg',(105-45),null,90);
$pdf->ln(5);
$pdf->SetFont('Times','',12);
$pdf->Write(5,'Joel Bixby');
$pdf->ln(5);
$pdf->Write(5,'5111 Broadlawn Dr SE');
$pdf->ln(5);
$pdf->Write(5,'Cedar Rapids, IA 52403');
$pdf->ln(10);
$pdf->Write(5,'Thank you for indicating your desire to make a donation to Riders Club of America, in the amount of $100.00. By offering to support our efforts, you are changing the lives of others, and giving them back their sense of independence in transportation.');
$pdf->ln(10);
$pdf->Write(5,'Gifts from friends of Riders Club of America are increasingly essential for helping us fulfill our mission. Much of our reputation has depended on, and will more and more depend on private gifts and your own valued participation. Again, Thank you.');
$pdf->ln(10);
$pdf->Write(5,'Your gift is greatly appreciated and does make a difference in strengthening the services we provide to those in need of transportation.');
$pdf->ln(10);
$pdf->Write(5,'Please send your contribution to:');
$pdf->ln(15);
$pdf->Write(5,'Riders Club of America');
$pdf->ln(5);
$pdf->Write(5,'1700 B Ave NE  #213');
//$pdf->ln(5);
//$pdf->Write(5,'Suite 213');
$pdf->ln(5);
$pdf->Write(5,'Cedar Rapids, IA 52402-5421');
$pdf->ln(15);
$pdf->Write(5,'Sincerely,');
$pdf->ln(15);
$pdf->Write(5,'Martin Wissenberg');
$pdf->ln(5);
$pdf->Write(5,'Executive Director');
$pdf->ln(5);
$pdf->Write(5,'Riders Club of America');

$pdf->Output("Reminder_Letter_" . time() . ".pdf","I");

?>
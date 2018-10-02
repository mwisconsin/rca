<?php


require_once('../config/lang/eng.php');
require_once('../tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Riders Club');
$pdf->SetTitle('Invoice');
$pdf->SetSubject('Invoice');
$pdf->SetKeywords('Riders Club, PDF, Invoice');

// set default header data
//$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
//$pdf->setFooterData($tc=array(0,64,0), $lc=array(0,64,128));

// set header and footer fonts
//$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
//$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));


$pdf->setPrintHeader(false);

// set default monospaced font
//$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

//set margins

$pdf->SetMargins(2, 2, 2);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

//set some language-dependent strings
$pdf->setLanguageArray($l);

// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font
// dejavusans is a UTF-8 Unicode font, if you only need to
// print standard ASCII chars, you can use core fonts like
// helvetica or times to reduce file size.
//$pdf->SetFont('Arial', '', 14, '', true);
$pdf->SetFont('courier', '', 10);
// Add a page
// This method has several options, check the source code documentation for more information.
$pdf->AddPage();

// set text shadow effect
//$pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));

// Set some content to print
$html_header = <<<EOD

<table cellpadding="0" cellspacing="0" border="0">
<tr>
  <td valign="top" width="60">From:</td>
  <td valign="top" width="420">Customer 1<br />
                   123 Main St<br />
				   Ste 210<br />
				   Cedar Rapids, IA 52401</td>

  <td valign="top" align="right"><img src="../../images/logos/norse.jpg" /></td>
</tr>
</table>
<table cellpadding="0" cellspacing="0" border="0" style="margin-top:20px;">
<tr><td></td><td></td></td></tr>
<tr>
  <td width="250"></td>
  <td>Send Payment To:</td>
</tr>
</table>
<table cellpadding="0" cellspacing="0" border="0">
<tr>
  <td width="300"></td>
  <td>Norse Transport<br />
      2180 Stratford Dr.<br />
	  Marion, IA 52302</td>
</tr>
</table>
<table cellpadding="0" cellspacing="0" width="300">
<tr>
  <td>Current Due:</td>
  <td align="right">$ 80.00</td>
</tr>
<tr>
  <td>Due After 08/25/12:</td>
  <td align="right">$ 81.20</td>
</tr>
<tr>
  <td>Amount Enclosed:</td>
  <td style="border:1px solid black;">$</td>
</tr>
</table>
<p>Please enclose this form with your payment</p>
EOD;


$pdf->writeHTML($html_header, true, false, true, false, '');

$html_content = <<<EOD

<table cellpadding="0" cellspacing="0">
<tr>
  <td></td>
  <td><img src="../../images/logos/norse.jpg" /></td>
  <td align="top" valign="top">Date: 08/15/12</td>
</tr>
</table>
<p align="center">2180 Stratford Dr.<br />
Marion, IA 52302<br />
(319) 423-5023</p>
<h2 align="center">Invoice</h2>
<table cellpadding="0" cellspacing="0">
<tr>
  <td width="60" valign="top">To:</td>
  <td valign="top">Customer 1<br />
123 Main St.<br />
Ste 210<br />
Cedar Rapids, IA 52401</td>
</tr>
</table>

<table cellpadding="0" cellspacing="0">
<tr>
  <td>Beginning Balance</td>
  <td>July 14, 2012</td>
  <td align="right">$ 20.00</td>
</tr>
</table>
<p>Transportation provided</p>

<table cellpadding="0" cellspacing="0" border="1">
<tr>
  <th>Date</th>
  <th>At</th>
  <th>From</th>
  <th>To</th>
  <th>&nbsp;</th>
</tr>
<tr>
  <td align="right">07/15/12</td>
  <td align="right">8:00 AM</td>
  <td>123 Main St<br />Cedar Rapids, IA 52401</td>
  <td>Dr Miller<br />1209 4th Ave<br />Cedar Rapids, IA 52403</td>
  <td align="right">$ 20.00</td>
</tr>
<tr>
  <td align="right">07/15/12</td>
  <td align="right">10:00 AM</td>
  <td>Dr Miller<br />1209 4th Ave<br />Cedar Rapids, IA 52403</td>
  <td>123 Main St<br />Cedar Rapids, IA 52401</td>
  <td align="right">$ 20.00</td>
</tr>
<tr>
  <td align="right">07/16/12</td>
  <td align="right">12:00 PM</td>
  <td>123 Main St<br />Cedar Rapids, IA 52401</td>
  <td>Dr Miller<br />1209 4th Ave<br />Cedar Rapids, IA 52403</td>
  <td align="right">$ 20.00</td>
</tr>
<tr>
  <td align="right">07/16/12</td>
  <td align="right">2:00 PM</td>
  <td>Dr Miller<br />1209 4th Ave<br />Cedar Rapids, IA 52403</td>
  <td>123 Main St<br />Cedar Rapids, IA 52401</td>
  <td align="right">$ 20.00</td>
</tr>
</table>
<table cellpadding="0" cellspacing="0">
<tr>
  <td colspan="2" align="left" width="80%">Transport Total</td>
  <td align="right" width="20%" style="border:1px solid black;">$ 80.00</td>
</tr>
<tr>
  <td align="left" width="60%">Payment Received</td>
  <td align="center" width="20%">July 20, 2012</td>
  <td align="right" width="20%">($ 20.00)</td>
</tr>
<tr>
  <td colspan="2" align="left" width="80%">Current Total</td>
  <td align="right" width="20%">$ 80.00</td>
</tr>

</table>

<table cellpadding="0" cellspacing="0">
<tr>
  <td>Due Date: 08/25/12</td>
  <td>Late Penalty: 1.50% per month</td>
</tr>
</table>

EOD;

$pdf->writeHTML($html_content, true, false, true, false, '');



// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output('invoice.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+

<?php
require_once('class.phpmailer.php');

class Email {

  function send($to_address, $subject, $message) {
      
	$mail = new PHPMailer(true); // the true param means it will throw exceptions on errors, which we need to catch
			
	$mail->IsSMTP(); // telling the class to use SMTP
	
	try {
	
	  $mail->IsSMTP(); // enable SMTP
	$mail->SMTPDebug = 0;  // debugging: 1 = errors and messages, 2 = messages only
	$mail->SMTPAuth = true;  // authentication enabled
	$mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for GMail
	$mail->Host = 'smtp.gmail.com';
	$mail->Port = 465; 
	$mail->Username = 'developer@myridersclub.com';  
	$mail->Password = 'YellowMonkey01';     
	
	  
	  
	  $mail->AddReplyTo('noreply@myridersclub.com', 'No Reply');
	  
	  $mail->AddAddress($to_address, '');
	  
	  $mail->SetFrom('noreply@myridersclub.com', 'No Reply');
	  $mail->Subject = $subject;
	  $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!'; // optional - MsgHTML will create an alternate automatically
	  $mail->MsgHTML($message);
//	  $mail->AddStringAttachment($message, 'report.xls');      // attachment
	  $mail->Send();
	  echo "Message Sent OK</p>\n";
	} catch (phpmailerException $e) {
	  echo $e->errorMessage(); //Pretty error messages from PHPMailer
	} catch (Exception $e) {
	  echo $e->getMessage(); //Boring error messages from anything else!
	}
  
  }

  function sendText($to_address, $subject, $message) {
      
	$mail = new PHPMailer(true); // the true param means it will throw exceptions on errors, which we need to catch
			
	$mail->IsSMTP(); // telling the class to use SMTP
	
	try {
	
	  $mail->IsSMTP(); // enable SMTP
	$mail->SMTPDebug = 0;  // debugging: 1 = errors and messages, 2 = messages only
	$mail->SMTPAuth = true;  // authentication enabled
	$mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for GMail
	$mail->Host = 'smtp.gmail.com';
	$mail->Port = 465; 
	$mail->Username = 'developer@myridersclub.com';  
	$mail->Password = 'YellowMonkey01';     
	
	  
	  
	  $mail->AddReplyTo('noreply@myridersclub.com', 'No Reply');
	  
	  $mail->AddAddress($to_address, '');
	  
	  $mail->SetFrom('noreply@myridersclub.com', 'No Reply');
	  $mail->Subject = $subject;
	  $mail->Body = $message;
		$mail->IsHTML(false);
	  $mail->Send();
	  echo "Message Sent OK</p>\n";
	} catch (phpmailerException $e) {
	  echo $e->errorMessage(); //Pretty error messages from PHPMailer
	} catch (Exception $e) {
	  echo $e->getMessage(); //Boring error messages from anything else!
	}
  
  }
}
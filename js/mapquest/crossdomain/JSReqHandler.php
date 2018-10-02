<?php

require_once "functions.inc.php";
error_reporting(0);

// ----- variables
$sname = '';
$sport = '';
$spath = '';
$statuscode = 0;
$statusmssg = '';
$hasAuth = 'false';
$rescontentType = '';
$isXml = 1;


// Client Details Set here

$clientid = '85090';
$password = 'cK7dF9jJ';

// Getting the posted xml data from javascript

$method = $_SERVER['REQUEST_METHOD'];

if($method == "GET"){
   $PARAMS = $_GET;
   $roughHTTPPOST = mq_get_QueryData($PARAMS);
} else {
   $roughHTTPPOST = file_get_contents("php://input");
}
$sname = $_GET["sname"];
$sport = $_GET["sport"];
$spath = $_GET["spath"];


$domdoc = new DOMDocument;

if($clientid ==''){
   header("HTTP/1.1 500 No ClientId set",false);
   die("ClientId not set");
   exit;
}

//  Check for whether xml is passed to proxy page or not

set_error_handler('HandleXmlError');
   if(strlen($roughHTTPPOST) > 0){
       try{
        $domdoc->loadXML($roughHTTPPOST);
          }catch(DOMException $e) {
             $isXml = 0;
          }
restore_error_handler();
   } else {
      $isXml = 0;
   }

// Adding the client node part if the document is valid and has Authentication node

   if($isXml == 1)
      $xmlpost = mq_add_client_details_node($domdoc,$clientid,$password);
   else
      $xmlpost = $roughHTTPPOST;
    // xml passed has got some error
        if($xmlpost =='' and $isXml==1){
          header("HTTP/1.1 500",false);
          die($GLOBALS["statusmssg"]);
          exit;
    }

// Sending xml as post to the server

 // forming the server name
  $server = $sname.":".$sport;

 // forming the url
  if($hasAuth == "true") {
     $url = "/".$spath."/mqserver.dll?e=5";
  } else {
     if($spath != '')
        $url = "/".$spath;
     else
        $url = "";
  }

  if($method == 'GET'){
    $url .="?".$roughHTTPPOST;
  }
$xml = mqa_http_get ($server, $url, 60,$xmlpost,$method);


   // Check the HTTP Status code
   switch($GLOBALS["statuscode"]) {
   case 200:
      // Success
     if(strlen($xml) > 0 ){
       header( "Content-Type: $rescontentType",false);
      echo $xml;
    } else {
      header("HTTP/1.1 500",false);
      exit;
    }

      break;
   case 503:
      header("HTTP/1.1 503",false);
        die($GLOBALS["statusmssg"]);
      exit;
      break;
   case 403:
      header("HTTP/1.1 403",false);
        die($GLOBALS["statusmssg"]);
      exit;
      break;
     case 404:
        header("HTTP/1.1 404",false);
        die($GLOBALS["statusmssg"]);
        exit;
        break;
   case 400:
      // You may want to fall through here and read the specific XML error
      header("HTTP/1.1 400",false);
        die($GLOBALS["statusmssg"]);
      exit;
      break;
   default:
      header("HTTP/1.1 500",false);
        die($GLOBALS["statusmssg"]);
      exit;

    }

 // end

?>

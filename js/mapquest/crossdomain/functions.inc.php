<?php

function mq_get_QueryData($params){
   $queryString = "";
   foreach ($params as $key => $value) {
      if( ($key == "sname") or ($key == "spath") or ($key == "sport") ) {

      } else {
         $queryString .= "&";
         $queryString .= $key;
         $queryString .= "=";
         $queryString .= $value;
      }
   }
   return substr_replace($queryString,'',0,1);

 }

 function mq_add_client_details_node($domdoc,$clientidvalue,$passwordvalue){
   $AuthenticationNodeList = $domdoc->getElementsByTagName("Authentication");
   $AuthenticationNode = $AuthenticationNodeList->item(0);
    if($AuthenticationNode != null){
       $GLOBALS["hasAuth"] = 'true';
   $password = $domdoc->createElement("Password");
   $passwordText = $domdoc->createTextNode("$passwordvalue");
   $password->appendChild($passwordText);
   $clientid = $domdoc->createElement("ClientId");
   $clientidText = $domdoc->createTextNode("$clientidvalue");
   $clientid->appendChild($clientidText);
   $AuthenticationNode->appendChild($password);
   $AuthenticationNode->appendChild($clientid);
    } else {
       $GLOBALS["hasAuth"] = 'false';
    }
   $xmlpost = $domdoc->saveXML();
   return $xmlpost;
 }

 function mqa_http_get ($serverAndPort, $url, $timeOut,$xmlpost,$method) {
   preg_match ("#^(?:\w+://)?([\w.]+)(?::(\d+))?#", $serverAndPort, $sData);
    $server = $sData[1];
    $port   = (preg_match ("/^\d+$/", $sData[2]) ? $sData[2] : 80);

    $fp = fsockopen($server, $port, $errno, $errstr, $timeOut);

    if (!is_resource($fp)) {
      setStatusCode("404");
      setStatusMessage("Cannot establish connection.");
      // fsockopen failed
      return "";
    } else {

       if($method == 'POST') {
      fwrite($fp, "POST $url HTTP/1.0\r\n");
      fwrite($fp, "Host: $sData[0]\r\n");
      fwrite($fp, "Content-type: application/x-www-form-urlencoded\r\n");
      fwrite($fp, "Content-length: " . strlen($xmlpost) . "\r\n");
      fwrite($fp, "Accept: */*\r\n");
      fwrite($fp, "\r\n");
      fwrite($fp, "$xmlpost\r\n");
      fwrite($fp, "\r\n");
       } else {
          $out =  "GET $url HTTP/1.1\r\n";
          $out .= "Host: $sData[0]\r\n";
          $out .= "Connection: Close\r\n\r\n";

          fwrite($fp, $out);
       }
      $headers = "";
      while ($str = trim(fgets($fp, 4096)))
        $headers .= "$str\n";



      //--- for getting the status code using reqex split
      $status_code1 = array();
      preg_match('/\d\d\d/', $headers, $status_code1);

      //--- to get the data frm the response

      $data = "";
      while (!feof($fp))
        $data .= fgets($fp, 4096);
      }

      fclose($fp);

      $retData = preg_split("/(\r\n?|\n)\\1/", $data, 2);
      $content = $retData[0];

      // -- setting the statuscode

        setStatusCode($status_code1[0]);
      if($status_code1[0] < 200 || $status_code1[0] >299){
              setStatusMessage($data);
      }

    // -------parse the headers

    $newHeader = "";

    foreach (preg_split("/(?:\r?\n|\r)/", $headers) as $hdr) {
        $hdrLine = explode(": ", $hdr);
        if (sizeof($hdrLine) > 1) {
         $newHeader[$hdrLine[0]] = $hdrLine[1];
        }
    }
     $GLOBALS["rescontentType"] = $newHeader['Content-Type'];
    if (strlen($content) != $newHeader['Content-Length']) {
        return "";
    } else {
        return $content;
    }
}

function setStatusCode($statusCode){
   $GLOBALS["statuscode"] = $statusCode;
}
function setStatusMessage($statusmsg){
   $GLOBALS["statusmssg"] = $statusmsg;
}

function HandleXmlError($errno, $errstr, $errfile, $errline)
{

   if ($errno==E_WARNING && (substr_count($errstr,"DOMDocument::loadXML()")>0))
   {
       throw new DOMException("Xml entered is not well formed.");
   }
   else
       return false;
}
?>
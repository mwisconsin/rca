<?

$f = fopen('sms-output.txt','w+');
fwrite($f, var_export($_POST,true));
fclose($f);

echo <<<EOT
<?xml version="1.0" encoding="UTF-8" ?>
<Response>
    <Message>Thank you for your response! The Club Administrator will review your response and adjust the schedule appropriately.</Message>
</Response>
EOT;

?>

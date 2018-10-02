<?php

?>
   <html>
      <head>
         <title>PHPinfo</title>
      </head>
   
      <body>
         <br /><br />
         
         <?php

            echo "{$_SERVER['SCRIPT_FILENAME']}\n\n";
         
            phpinfo();
         ?>
         
      </body>
   </html>
   
   

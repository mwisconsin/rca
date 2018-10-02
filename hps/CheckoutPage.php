<?php
    //TODO
	  //Initialize variable strTransNum below by assiging it the transaction number that is to be processed. It must not to be empty.
	  //$strTransNum = "IN123456";
    session_start();
    $strTransNum = $_SESSION['TransactionNumber'];
    //END
?>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>Purchase Page</title>
<style type="text/css">
Body {	background-color:RGB(255,255,255);}
.BT_BtnOut {	Width:70px; font-size: 9pt; font-weight:bold;COLOR: RGB(0,0,0);font-family : Arial;}
.BT_BtnOvr {	Width:70px; font-size: 9pt; font-weight:bold;COLOR: RGB(0,0,0);font-family : Arial;}
.BT_Field {	FONT-SIZE: 8pt; font-family : Arial;COLOR: RGB(0,0,0);}
.BT_FieldDescription {	FONT-WEIGHT: bold; FONT-SIZE: 8pt; font-family : Arial; COLOR : RGB(0,0,0);}
</style></head>
<body>
    <div>
        <form method="post" id="PurchaseForm">
            <input type="hidden" id="TransactionNumber" value="<?php echo  $strTransNum; ?>" />
            <div id="PaymentMethodDiv" style="width:580px; height:200px; text-align:center; vertical-align:middle">
                <table style="width:100%; height:100%">
                    <tr>
                        <td style="vertical-align:middle; text-align:center">
                            <table width="260px">
                                <tr>
                                    <td style="text-align:right">
                                        <input id="rbCreditCard" type="radio" checked="checked" name="PaymentMethod"/><span class="BT_Field">Credit Card</span></td>
                                </tr>
                                <tr>
                                    <td style="text-align:right" colspan="2">
                                        <input id="btnNext" type="button" value="Next >>" onClick="Next();" class="BT_BtnOut" 
                                          onmouseover="this.className='BT_BtnOvr'" onMouseOut="this.className='BT_BtnOut';"/></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
            <div id="ProcessDiv" style="display:none;">
                <table>
                    <tr>
                        <td>
                            <!--You can adjust the iframe's height and width below in terms of pixels to your like-->
                            <div style="border:1px solid #000066;background-color:#eeeeff;padding:5px;">
                            All credit card information is stored in a secure server.  When you finish
your transaction you will be transferred back to our website.  No critical
data is passed between pages.
                            </div>
                            <iframe id="ProcessPage" style="overflow:hidden" height="800px" width="700px" frameborder="0"></iframe>
                        </td>
                    </tr>
                </table>
            </div>
        </form>
        <script type="text/javascript">
        function Next()
        {
            document.getElementById('PaymentMethodDiv').style.display='none';
            var processURL = "ProcessPageNew.php?No="+document.getElementById('TransactionNumber').value+"&ProcessType=";
            processURL = processURL + "CreditCard";
            document.getElementById('ProcessPage').src=processURL;
            document.getElementById('ProcessDiv').style.display='block';
        }
        
        function Back()
        {
            document.getElementById('ProcessDiv').style.display='none';
            document.getElementById('ProcessPage').src=null; 
            document.getElementById('PaymentMethodDiv').style.display='block';
        }
        function Change(index){}
        </script>
    </div>
</body>
</html>

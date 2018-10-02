				</div>
            <div id="footer" class="noprint">
            	<div id="footer_nav">
            		<a href="<?php echo site_url(); ?>documents.php">Documents</a> &bull; 
            		<a href="<?php echo site_url(); ?>faq.php">FAQ</a> &bull; 
                	<a href="<?php echo site_url(); ?>contactus.php">Contact Us</a> &bull; 
					<a target=_blank href="/documents/Policies20140516.pdf">Policies</a>
				</div>
				<img src="images/rcams.png" align="" height="40px">
            </div>
        </div>
    </body>
    <?php 
	if(is_logged_in()){
	$franchise = get_current_user_franchise(FALSE);
	if( current_user_has_role(1, 'FullAdmin') || current_user_has_role($franchise,'Franchisee')){ ?>
    <script src="js/sortable_table.js" type="text/javascript"></script>
    <?php } }?>
	<script src="js/address_options.js" type="text/javascript"></script>
    <script type="text/javascript" src="js/nav_bar.js"></script>
    <div class="fb-like noprint" data-href="https://www.facebook.com/pages/Riders-Club-of-America/134589719935220" data-send="true" data-width="450" data-show-faces="true" data-font="verdana"></div>
</html>

		</div><!-- /container -->

		<div id="push"></div>
	</div><!-- /wrap -->
	
	<footer id="footer" class="container">
		<hr />
		<?php if($this->ui->is_backend()) { ?><p class="pull-right"><a href="#">返回顶部</a></p><?php } ?>
		&copy; 2008-<?php echo date('Y');?> <a href="http://imunc.com/">IMUNC</a>. All rights reserved.
	</footer>
	
	<?php if(!empty($this->ui->js['footer'])) { ?><script language="javascript"><?php echo $this->ui->js['footer'];?></script><?php } ?>
	<?php if(!empty($this->ui->html['footer'])) echo $this->ui->html['footer'];?>
</body>
</html>
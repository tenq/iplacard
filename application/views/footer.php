<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Footer
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */
?>		</div><!-- /container -->

		<div id="push"></div>
	</div><!-- /wrap -->
	
	<footer id="footer" class="<?php echo $this->ui->show_sidebar ? 'container col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2' : 'container';?>">
		<hr />
		<p class="pull-right">
			<?php
			if($this->ui->is_backend() || $this->ui->is_frontend())
			{
				foreach(array('rss', 'renren', 'weibo', 'tencent-weibo', 'whatsapp', 'instagram', 'linkedin', 'google', 'youtube', 'wechat', 'qq', 'github', 'twitter', 'facebook') as $social)
				{
					$link = option("link_{$social}", false);
					if($link)
						echo anchor($link, icon($social, false), array('style' => 'color: inherit;', 'target' => '_blank')).' ';
				}
			} ?></p>
		<?php
		if(!empty(option('site_copyright', '')))
			echo option('site_copyright', '');
		else { ?>Powered by <a href="http://iplacard.com/">iPlacard</a> from <a href="http://imunc.com/">IMUNC</a><?php } ?>
	</footer>
	
	<?php if(!empty($this->ui->js['footer'])) { ?><script language="javascript"><?php echo is_dev() ? $this->ui->js['footer'] : preg_replace("/\s+/", ' ', $this->ui->js['footer']);?></script><?php } ?>
	<?php if(!empty($this->ui->html['footer'])) echo $this->ui->html['footer'];?>
</body>
</html>
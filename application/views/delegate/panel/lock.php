<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 锁定申请视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */

echo form_open("apply/status/lock", array(
	'class' => 'modal fade form-horizontal',
	'id' => 'lock',
	'tabindex' => '-1',
	'role' => 'dialog',
	'aria-labelledby' => 'lock_label',
	'aria-hidden' => 'true'
));?><div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<?php echo form_button(array(
					'content' => '&times;',
					'class' => 'close',
					'type' => 'button',
					'data-dismiss' => 'modal',
					'aria-hidden' => 'true'
				));?>
				<h4 class="modal-title" id="lock_label">确认锁定申请</h4>
			</div>
			<div class="modal-body flags-16">
				<p><?php
				if($seat_mode == 'select')
				{
					echo sprintf('您将确认申请完成并锁定席位 %1$s，%2$s为您的席位。席位锁定后您将无法更换您的席位，并且您的面试官也将无法再向您提供更多席位选择。',
						flag($seat['iso'], false).$seat['name'],
						$seat['committee']['name']
					);
				}
				else
				{
					echo sprintf('您将确认申请完成并锁定席位 %1$s，%2$s为您的席位。席位锁定后您将无法调整您的席位。',
						flag($seat['iso'], false).$seat['name'],
						$seat['committee']['name']
					);
				}
				?></p>

				<p><?php
				if(is_sudo())
					echo '席位锁定后无法重新解锁，您将以 SUDO 授权锁定代表申请，请输入您管理员帐户的密码并点击确认锁定按钮以继续。';
				else
					echo '席位锁定后无法重新解锁，请输入您的登录密码并点击确认锁定按钮以继续。';
				?></p>

				<div class="form-group <?php if(form_has_error('password')) echo 'has-error';?>">
					<?php echo form_label(is_sudo() ? '管理员密码' : '登录密码', 'password', array('class' => 'col-lg-3 control-label'));?>
					<div class="col-lg-5">
						<?php echo form_password(array(
							'name' => 'password',
							'id' => 'password',
							'class' => 'form-control',
							'required' => NULL
						));
						echo form_error('password');?>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<?php echo form_button(array(
					'content' => '取消',
					'type' => 'button',
					'class' => 'btn btn-link',
					'data-dismiss' => 'modal'
				));
				echo form_button(array(
					'name' => 'submit',
					'content' => '确认锁定',
					'type' => 'submit',
					'class' => 'btn btn-primary',
					'onclick' => 'loader(this);'
				)); ?>
			</div>
		</div>
	</div>
<?php echo form_close(); ?>
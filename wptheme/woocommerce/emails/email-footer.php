<?php
defined('ABSPATH') || exit;
?>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td id="template_footer" style="padding:0;background:#f5ebd6;border-top:1px solid #ddd1bb;">
								<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
									<tr>
										<td style="padding:18px 28px;color:#6b5b46;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.45;">
											<?php echo wp_kses_post(wpautop(wptexturize(apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'))))); ?>
										</td>
										<td align="right" style="padding:18px 28px;color:#6b5b46;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.45;white-space:nowrap;">
											<a href="<?php echo esc_url(home_url('/')); ?>" style="color:#2f6a2b;text-decoration:none;font-weight:800;"><?php echo esc_html(get_bloginfo('name', 'display')); ?></a>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</body>
</html>

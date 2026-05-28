<?php
defined('ABSPATH') || exit;

$site_name = get_bloginfo('name', 'display');
$logo_url = get_theme_file_uri('/assets/static/icons/italika-logo.svg');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo('charset'); ?>">
		<meta content="width=device-width, initial-scale=1.0" name="viewport">
		<title><?php echo esc_html($site_name); ?></title>
	</head>
	<body style="margin:0;padding:0;background:#f5ebd6;color:#2b2418;font-family:Arial,Helvetica,sans-serif;">
		<table id="body" role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#f5ebd6;margin:0;padding:28px 12px;">
			<tr>
				<td align="center">
					<table id="template_container" role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:680px;background:#ffffff;border:1px solid #ddd1bb;border-radius:8px;overflow:hidden;box-shadow:0 14px 30px rgba(46,33,22,.12);">
						<tr>
							<td id="template_header" style="background:#24311c;padding:0;">
								<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
									<tr>
										<td style="padding:22px 28px;">
											<a href="<?php echo esc_url(home_url('/')); ?>" style="display:inline-block;text-decoration:none;">
												<img src="<?php echo esc_url($logo_url); ?>" width="190" alt="<?php echo esc_attr($site_name); ?>" style="display:block;width:190px;max-width:100%;height:auto;border:0;">
											</a>
										</td>
										<td align="right" style="padding:22px 28px;color:#ffffff;font-size:13px;line-height:1.4;font-weight:700;">
											Заказ Italika
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td id="body_content" style="padding:0;">
								<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
									<tr>
										<td id="body_content_inner" style="padding:28px;color:#2b2418;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;">
											<h1 style="margin:0 0 18px;color:#2b2418;font-family:Arial,Helvetica,sans-serif;font-size:28px;line-height:1.15;font-weight:900;">
												<?php echo esc_html($email_heading); ?>
											</h1>

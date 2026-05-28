<?php
defined('ABSPATH') || exit;

do_action('woocommerce_before_lost_password_form');
?>

<section class="lk-reg">
	<div class="container">
		<div class="lk-reg__shell">
			<div class="lk-reg__panel">
				<form class="lk-reg__form woocommerce-ResetPassword lost_reset_password" method="post">
					<div class="lk-reg__form-head">
						<h2 class="lk-reg__form-title">Восстановить пароль</h2>
						<p class="lk-reg__form-text">Введите email или логин. Мы отправим ссылку для смены пароля.</p>
					</div>

					<label class="lk-reg__field" for="user_login">
						<span class="lk-reg__label">Email или логин</span>
						<input class="lk-reg__input woocommerce-Input woocommerce-Input--text input-text" type="text" name="user_login" id="user_login" autocomplete="username" placeholder="mail@example.ru" required>
					</label>

					<?php do_action('woocommerce_lostpassword_form'); ?>
					<?php if (function_exists('italika_spam_render_fields')) : ?>
						<?php italika_spam_render_fields('lost_password'); ?>
					<?php endif; ?>

					<input type="hidden" name="wc_reset_password" value="true">
					<?php wp_nonce_field('lost_password', 'woocommerce-lost-password-nonce'); ?>

					<button class="lk-reg__submit woocommerce-Button button" type="submit" value="<?php esc_attr_e('Reset password', 'woocommerce'); ?>">Отправить ссылку</button>
					<a class="lk-reg__link lk-reg__link--back" href="<?php echo esc_url(italika_wc_get_account_url()); ?>">Вернуться ко входу</a>
				</form>
			</div>
		</div>
	</div>
</section>

<?php do_action('woocommerce_after_lost_password_form'); ?>

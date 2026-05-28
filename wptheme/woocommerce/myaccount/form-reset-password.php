<?php
defined('ABSPATH') || exit;

do_action('woocommerce_before_reset_password_form');
?>

<section class="lk-reg">
	<div class="container">
		<div class="lk-reg__shell">
			<div class="lk-reg__panel">
				<form method="post" class="lk-reg__form woocommerce-ResetPassword lost_reset_password">
					<div class="lk-reg__form-head">
						<h2 class="lk-reg__form-title">Задать новый пароль</h2>
						<p class="lk-reg__form-text">Введите новый пароль и повторите его.</p>
					</div>

					<label class="lk-reg__field" for="password_1">
						<span class="lk-reg__label">Новый пароль</span>
						<span class="lk-reg__password">
							<input class="lk-reg__input woocommerce-Input woocommerce-Input--text input-text" type="password" name="password_1" id="password_1" autocomplete="new-password" required>
							<button class="lk-reg__password-toggle" type="button" data-password-toggle aria-label="Показать пароль"></button>
						</span>
					</label>

					<label class="lk-reg__field" for="password_2">
						<span class="lk-reg__label">Повторите новый пароль</span>
						<span class="lk-reg__password">
							<input class="lk-reg__input woocommerce-Input woocommerce-Input--text input-text" type="password" name="password_2" id="password_2" autocomplete="new-password" required>
							<button class="lk-reg__password-toggle" type="button" data-password-toggle aria-label="Показать пароль"></button>
						</span>
					</label>

					<input type="hidden" name="reset_key" value="<?php echo esc_attr($args['key']); ?>">
					<input type="hidden" name="reset_login" value="<?php echo esc_attr($args['login']); ?>">

					<?php do_action('woocommerce_resetpassword_form'); ?>
					<?php if (function_exists('italika_spam_render_fields')) : ?>
						<?php italika_spam_render_fields('reset_password'); ?>
					<?php endif; ?>
					<?php wp_nonce_field('reset_password', 'woocommerce-reset-password-nonce'); ?>

					<button type="submit" class="lk-reg__submit woocommerce-Button button" name="save_password" value="<?php esc_attr_e('Save', 'woocommerce'); ?>">Сохранить пароль</button>
				</form>
			</div>
		</div>
	</div>
</section>

<script>
;(function () {
	var root = document.currentScript.previousElementSibling

	if (!root) {
		return
	}

	root.addEventListener('click', function (event) {
		var passwordButton = event.target.closest('[data-password-toggle]')
		var passwordInput

		if (!passwordButton) {
			return
		}

		passwordInput = passwordButton.parentNode.querySelector('input')

		if (!passwordInput) {
			return
		}

		passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password'
		passwordButton.classList.toggle('is-visible', passwordInput.type === 'text')
		passwordButton.setAttribute('aria-label', passwordInput.type === 'text' ? 'Скрыть пароль' : 'Показать пароль')
	})
})()
</script>

<?php do_action('woocommerce_after_reset_password_form'); ?>

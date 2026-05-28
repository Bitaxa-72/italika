<?php
defined('ABSPATH') || exit;

do_action('woocommerce_before_customer_login_form');

$registration_enabled = 'yes' === get_option('woocommerce_enable_myaccount_registration');
?>

<section class="lk-reg" data-lk-reg>
	<div class="container">
		<div class="lk-reg__shell">
			<div class="lk-reg__panel">
				<?php if ($registration_enabled) : ?>
					<div class="lk-reg__tabs" role="tablist" aria-label="Выберите действие">
						<button class="lk-reg__tab is-active" type="button" role="tab" aria-selected="true" aria-controls="lk-login" data-lk-tab="login">Вход</button>
						<button class="lk-reg__tab" type="button" role="tab" aria-selected="false" aria-controls="lk-register" data-lk-tab="register">Регистрация</button>
					</div>
				<?php endif; ?>

				<form class="lk-reg__form is-active woocommerce-form woocommerce-form-login login" id="lk-login" method="post" data-lk-panel="login">
					<?php do_action('woocommerce_login_form_start'); ?>

					<div class="lk-reg__form-head">
						<h2 class="lk-reg__form-title">Войти в кабинет</h2>
						<p class="lk-reg__form-text">Введите email, телефон или логин и пароль.</p>
					</div>

					<label class="lk-reg__field" for="username">
						<span class="lk-reg__label">Email, телефон или логин</span>
						<input class="lk-reg__input woocommerce-Input woocommerce-Input--text input-text" type="text" name="username" id="username" autocomplete="username" placeholder="mail@example.ru" value="<?php echo !empty($_POST['username']) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>" required>
					</label>

					<label class="lk-reg__field" for="password">
						<span class="lk-reg__label">Пароль</span>
						<span class="lk-reg__password">
							<input class="lk-reg__input woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" placeholder="Введите пароль" required>
							<button class="lk-reg__password-toggle" type="button" data-password-toggle aria-label="Показать пароль"></button>
						</span>
					</label>

					<?php do_action('woocommerce_login_form'); ?>
					<?php if (function_exists('italika_spam_render_fields')) : ?>
						<?php italika_spam_render_fields('login'); ?>
					<?php endif; ?>

					<div class="lk-reg__row">
						<label class="lk-reg__check woocommerce-form-login__rememberme">
							<input class="lk-reg__check-input woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" checked>
							<span class="lk-reg__check-box" aria-hidden="true"></span>
							<span>Запомнить меня</span>
						</label>
						<a class="lk-reg__link" href="<?php echo esc_url(wc_lostpassword_url()); ?>">Забыли пароль?</a>
					</div>

					<?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
					<input type="hidden" name="redirect" value="<?php echo esc_url(italika_wc_get_account_url()); ?>">
					<button class="lk-reg__submit woocommerce-button button woocommerce-form-login__submit" type="submit" name="login" value="<?php esc_attr_e('Log in', 'woocommerce'); ?>">Войти</button>

					<?php do_action('woocommerce_login_form_end'); ?>
				</form>

				<?php if ($registration_enabled) : ?>
					<form class="lk-reg__form woocommerce-form woocommerce-form-register register" id="lk-register" method="post" data-lk-panel="register" hidden>
						<?php do_action('woocommerce_register_form_start'); ?>

						<div class="lk-reg__form-head">
							<h2 class="lk-reg__form-title">Создать аккаунт</h2>
							<p class="lk-reg__form-text">Заполните данные для быстрых заказов.</p>
						</div>

						<div class="lk-reg__grid">
							<label class="lk-reg__field" for="reg_billing_first_name">
								<span class="lk-reg__label">Имя</span>
								<input class="lk-reg__input" type="text" name="billing_first_name" id="reg_billing_first_name" autocomplete="given-name" placeholder="Ваше имя" value="<?php echo !empty($_POST['billing_first_name']) ? esc_attr(wp_unslash($_POST['billing_first_name'])) : ''; ?>" required>
							</label>

							<label class="lk-reg__field" for="reg_billing_phone">
								<span class="lk-reg__label">Телефон</span>
								<input class="lk-reg__input" type="tel" name="billing_phone" id="reg_billing_phone" autocomplete="tel" placeholder="+7 900 000-00-00" value="<?php echo !empty($_POST['billing_phone']) ? esc_attr(wp_unslash($_POST['billing_phone'])) : ''; ?>" required>
							</label>
						</div>

						<div class="lk-reg__field">
							<span class="lk-reg__label">Тип покупателя</span>
							<label class="lk-reg__check lk-reg__check--wide">
								<input class="lk-reg__check-input js-register-customer-type" type="radio" name="italika_customer_type" value="individual" <?php checked(!empty($_POST['italika_customer_type']) ? sanitize_key(wp_unslash($_POST['italika_customer_type'])) : 'individual', 'individual'); ?>>
								<span class="lk-reg__check-box" aria-hidden="true"></span>
								<span>Физ. лицо</span>
							</label>
							<label class="lk-reg__check lk-reg__check--wide">
								<input class="lk-reg__check-input js-register-customer-type" type="radio" name="italika_customer_type" value="legal_entity" <?php checked(!empty($_POST['italika_customer_type']) ? sanitize_key(wp_unslash($_POST['italika_customer_type'])) : '', 'legal_entity'); ?>>
								<span class="lk-reg__check-box" aria-hidden="true"></span>
								<span>Юр. лицо</span>
							</label>
						</div>

						<div class="lk-reg__grid js-register-company-fields" <?php echo (!empty($_POST['italika_customer_type']) && sanitize_key(wp_unslash($_POST['italika_customer_type'])) === 'legal_entity') ? '' : 'hidden'; ?>>
							<label class="lk-reg__field" for="reg_italika_company_name">
								<span class="lk-reg__label">Наименование компании</span>
								<input class="lk-reg__input js-register-company-required" type="text" name="italika_company_name" id="reg_italika_company_name" placeholder="ООО Ромашка" value="<?php echo !empty($_POST['italika_company_name']) ? esc_attr(wp_unslash($_POST['italika_company_name'])) : ''; ?>">
							</label>

							<label class="lk-reg__field" for="reg_italika_company_inn">
								<span class="lk-reg__label">ИНН</span>
								<input class="lk-reg__input js-register-company-required" type="text" name="italika_company_inn" id="reg_italika_company_inn" placeholder="ИНН компании" value="<?php echo !empty($_POST['italika_company_inn']) ? esc_attr(wp_unslash($_POST['italika_company_inn'])) : ''; ?>">
							</label>
						</div>

						<label class="lk-reg__field" for="reg_email">
							<span class="lk-reg__label">Email</span>
							<input class="lk-reg__input woocommerce-Input woocommerce-Input--text input-text" type="email" name="email" id="reg_email" autocomplete="email" placeholder="mail@example.ru" value="<?php echo !empty($_POST['email']) ? esc_attr(wp_unslash($_POST['email'])) : ''; ?>" required>
						</label>

						<label class="lk-reg__field" for="reg_italika_bonus_card_number">
							<span class="lk-reg__label">Номер бонусной карты</span>
							<input class="lk-reg__input" type="text" name="italika_bonus_card_number" id="reg_italika_bonus_card_number" placeholder="Укажите номер бонусной карты" value="<?php echo !empty($_POST['italika_bonus_card_number']) ? esc_attr(wp_unslash($_POST['italika_bonus_card_number'])) : ''; ?>">
						</label>

						<?php if ('no' === get_option('woocommerce_registration_generate_password')) : ?>
							<label class="lk-reg__field" for="reg_password">
								<span class="lk-reg__label">Пароль</span>
								<span class="lk-reg__password">
									<input class="lk-reg__input woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="reg_password" autocomplete="new-password" placeholder="Минимум 6 символов" required>
									<button class="lk-reg__password-toggle" type="button" data-password-toggle aria-label="Показать пароль"></button>
								</span>
							</label>
						<?php else : ?>
							<p class="lk-reg__form-text">Пароль будет отправлен на вашу почту.</p>
						<?php endif; ?>

						<?php do_action('woocommerce_register_form'); ?>
						<?php if (function_exists('italika_spam_render_fields')) : ?>
							<?php italika_spam_render_fields('register'); ?>
						<?php endif; ?>

						<label class="lk-reg__check lk-reg__check--wide">
							<input class="lk-reg__check-input" name="agreement" type="checkbox" required>
							<span class="lk-reg__check-box" aria-hidden="true"></span>
							<span>Согласен на обработку персональных данных</span>
						</label>

						<?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>
						<button class="lk-reg__submit woocommerce-Button woocommerce-button button woocommerce-form-register__submit" type="submit" name="register" value="<?php esc_attr_e('Register', 'woocommerce'); ?>">Зарегистрироваться</button>

						<?php do_action('woocommerce_register_form_end'); ?>
					</form>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>

<script>
;(function () {
	var root = document.querySelector('[data-lk-reg]')

	if (!root) {
		return
	}

	var tabs = root.querySelectorAll('[data-lk-tab]')
	var panels = root.querySelectorAll('[data-lk-panel]')
	var registerPanel = root.querySelector('#lk-register')

	function setMode(mode) {
		var i = 0

		for (i = 0; i < tabs.length; i++) {
			tabs[i].classList.toggle('is-active', tabs[i].getAttribute('data-lk-tab') === mode)
			tabs[i].setAttribute('aria-selected', tabs[i].getAttribute('data-lk-tab') === mode ? 'true' : 'false')
		}

		for (i = 0; i < panels.length; i++) {
			panels[i].hidden = panels[i].getAttribute('data-lk-panel') !== mode
			panels[i].classList.toggle('is-active', panels[i].getAttribute('data-lk-panel') === mode)
		}
	}

	function syncRegisterCustomerType() {
		var checked = registerPanel ? registerPanel.querySelector('.js-register-customer-type:checked') : null
		var companyFields = registerPanel ? registerPanel.querySelector('.js-register-company-fields') : null
		var companyInputs = companyFields ? companyFields.querySelectorAll('.js-register-company-required') : []
		var isLegalEntity = checked && checked.value === 'legal_entity'
		var i = 0

		if (!companyFields) {
			return
		}

		companyFields.hidden = !isLegalEntity

		for (i = 0; i < companyInputs.length; i++) {
			companyInputs[i].required = isLegalEntity
		}
	}

	root.addEventListener('click', function (event) {
		var tab = event.target.closest('[data-lk-tab]')
		var passwordButton = event.target.closest('[data-password-toggle]')
		var passwordInput

		if (tab) {
			setMode(tab.getAttribute('data-lk-tab'))
			return
		}

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

	root.addEventListener('change', function (event) {
		if (event.target.matches('.js-register-customer-type')) {
			syncRegisterCustomerType()
		}
	})

	syncRegisterCustomerType()
})()
</script>

<?php do_action('woocommerce_after_customer_login_form'); ?>

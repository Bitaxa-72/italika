<?php
defined('ABSPATH') || exit;

$cart_data = function_exists('italika_wc_cart_data') ? italika_wc_cart_data() : ['items' => [], 'count' => 0];
?>

<section class="cart-page" data-cart-endpoint="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" data-coupon-endpoint="" data-checkout-url="<?php echo esc_url(wc_get_checkout_url()); ?>">
	<div class="container">
		<div class="cart-page__head">
			<div class="cart-page__intro">
				<span class="cart-page__eyebrow">Корзина</span>
				<h1 class="cart-page__title">Ваш заказ</h1>
				<p class="cart-page__summary js-cart-summary">Проверьте товары и количество перед оформлением.</p>
			</div>
			<a class="cart-page__back" href="<?php echo esc_url(italika_wc_get_shop_url()); ?>">Продолжить покупки</a>
		</div>

		<div class="cart-page__layout js-cart-layout">
			<div class="cart-page__main">
				<div class="cart-page__toolbar">
					<label class="cart-page__select-all">
						<input class="js-cart-select-all" type="checkbox" checked>
						<span>Выбрать все товары</span>
					</label>
					<button class="cart-page__link-button js-cart-clear-selected" type="button">Удалить выбранные</button>
				</div>
				<div class="cart-page__list js-cart-list" aria-label="Товары в корзине"></div>
			</div>

			<aside class="cart-page__side" aria-label="Итоги заказа">
				<div class="cart-page__summary-card">
					<h2 class="cart-page__side-title">Итого</h2>
					<dl class="cart-page__totals">
						<div class="cart-page__total-row">
							<dt>Товары</dt>
							<dd class="js-cart-subtotal">0 ₽</dd>
						</div>
						<div class="cart-page__total-row">
							<dt>Доставка</dt>
							<dd class="js-cart-shipping">На оформлении</dd>
						</div>
						<div class="cart-page__total-row cart-page__total-row--grand">
							<dt>К оплате</dt>
							<dd class="js-cart-total">0 ₽</dd>
						</div>
					</dl>

					<a class="cart-page__checkout js-cart-checkout" href="<?php echo esc_url(wc_get_checkout_url()); ?>">Оформить заказ</a>
					<p class="cart-page__hint">Способ получения выбирается на следующем шаге.</p>
				</div>
			</aside>
		</div>

		<div class="cart-page__empty js-cart-empty" hidden>
			<span class="cart-page__empty-icon" aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="none">
					<path d="M3.5 5H6l1.6 8.1c.1.7.7 1.2 1.4 1.2h7.7c.7 0 1.3-.5 1.4-1.2L19.5 8H7.1" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
					<circle cx="10" cy="18.2" r="1.2" fill="currentColor" stroke="none"></circle>
					<circle cx="17" cy="18.2" r="1.2" fill="currentColor" stroke="none"></circle>
				</svg>
			</span>
			<h2 class="cart-page__empty-title">В корзине пока пусто</h2>
			<p class="cart-page__empty-text">Добавляйте товары из каталога, акций или избранного, чтобы быстро перейти к оформлению.</p>
			<a class="cart-page__empty-link" href="<?php echo esc_url(italika_wc_get_shop_url()); ?>">Перейти к товарам</a>
		</div>
	</div>

	<script>
		;(function () {
			var section = document.querySelector('.cart-page')
			if (!section) return

			var list = section.querySelector('.js-cart-list')
			var layout = section.querySelector('.js-cart-layout')
			var empty = section.querySelector('.js-cart-empty')
			var summary = section.querySelector('.js-cart-summary')
			var selectAll = section.querySelector('.js-cart-select-all')
			var clearSelectedButton = section.querySelector('.js-cart-clear-selected')
			var subtotalNode = section.querySelector('.js-cart-subtotal')
			var shippingNode = section.querySelector('.js-cart-shipping')
			var totalNode = section.querySelector('.js-cart-total')
			var checkoutLink = section.querySelector('.js-cart-checkout')
			var checkoutUrl = section.getAttribute('data-checkout-url') || 'checkout.html'
			var products = <?php echo wp_json_encode($cart_data['items'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?> || []
			var ajaxUrl = (window.italikaWooData && window.italikaWooData.ajaxUrl) || section.getAttribute('data-cart-endpoint')
			var cartNonce = (window.italikaWooData && window.italikaWooData.cartNonce) || '<?php echo esc_js(wp_create_nonce('italika_wc_cart_nonce')); ?>'

			function escapeHtml(value) {
				return String(value)
					.replace(/&/g, '&amp;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;')
					.replace(/"/g, '&quot;')
					.replace(/'/g, '&#039;')
			}

			function formatPrice(value) {
				return Number(value).toLocaleString('ru-RU', {
					minimumFractionDigits: 2,
					maximumFractionDigits: 2
				}) + ' ₽'
			}

			function getProductsWord(count) {
				var lastDigit = count % 10
				var lastTwoDigits = count % 100
				if (lastTwoDigits >= 11 && lastTwoDigits <= 14) return 'товаров'
				if (lastDigit === 1) return 'товар'
				if (lastDigit >= 2 && lastDigit <= 4) return 'товара'
				return 'товаров'
			}

			function getSelectedProducts() {
				return products.filter(function (product) {
					return product.isSelected
				})
			}

			function getTotals() {
				var selectedProducts = getSelectedProducts()
				var subtotal = selectedProducts.reduce(function (sum, product) {
					return sum + product.price * product.quantity
				}, 0)

				return {
					subtotal: subtotal,
					shipping: 0,
					total: subtotal,
					count: selectedProducts.reduce(function (sum, product) {
						return sum + product.quantity
					}, 0)
				}
			}

			function syncCartAction(action, payload) {
				section.dispatchEvent(new CustomEvent('cart:change', {
					detail: {
						action: action,
						payload: payload,
						items: products.slice()
					}
				}))
			}

			function requestCartUpdate(data) {
				var formData = new FormData()

				formData.append('action', 'italika_wc_cart_update')
				formData.append('nonce', cartNonce)

				Object.keys(data).forEach(function (key) {
					var value = data[key]

					if (Array.isArray(value)) {
						value.forEach(function (item) {
							formData.append(key + '[]', item)
						})
						return
					}

					formData.append(key, value)
				})

				return fetch(ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: formData
				})
			}

			function createItemMarkup(product, index) {
				var warning = product.isAvailable ? '' : ' cart-page__item--warning'
				var checked = product.isSelected ? ' checked' : ''
				var stockClass = product.isAvailable ? ' cart-page__stock--available' : ' cart-page__stock--waiting'

				return (
					'' +
					'<article class="cart-page__item' + warning + '" data-cart-index="' + index + '" data-product-id="' + escapeHtml(product.id) + '" data-cart-key="' + escapeHtml(product.key) + '">' +
					'<label class="cart-page__item-check"><input class="js-cart-item-check" type="checkbox"' + checked + ' aria-label="Выбрать товар: ' + escapeHtml(product.title) + '"></label>' +
					'<a class="cart-page__image-box" href="' + escapeHtml(product.href) + '"><img class="cart-page__image" src="' + escapeHtml(product.image) + '" alt="' + escapeHtml(product.title) + '" loading="lazy" decoding="async"></a>' +
					'<div class="cart-page__item-body">' +
					'<div class="cart-page__item-top"><div><a class="cart-page__item-title" href="' + escapeHtml(product.href) + '">' + escapeHtml(product.title) + '</a><span class="cart-page__meta">Артикул: ' + escapeHtml(product.sku) + '</span></div><button class="cart-page__remove js-cart-remove" type="button" aria-label="Удалить товар: ' + escapeHtml(product.title) + '">Удалить</button></div>' +
					'<span class="cart-page__stock' + stockClass + '">' + escapeHtml(product.stockText) + '</span>' +
					'<div class="cart-page__item-bottom"><div class="cart-page__quantity" aria-label="Количество товара"><button class="cart-page__quantity-button js-cart-decrease" type="button" aria-label="Уменьшить количество">-</button><input class="cart-page__quantity-input js-cart-quantity" type="number" min="1" max="' + escapeHtml(product.maxQuantity) + '" value="' + escapeHtml(product.quantity) + '" inputmode="numeric"><button class="cart-page__quantity-button js-cart-increase" type="button" aria-label="Увеличить количество">+</button></div>' +
					'<div class="cart-page__price"><strong>' + formatPrice(product.price * product.quantity) + '</strong><span>' + formatPrice(product.price) + ' / шт</span></div></div>' +
					'</div></article>'
				)
			}

			function renderItems() {
				var fragment = document.createDocumentFragment()
				var wrapper = document.createElement('div')
				var i = 0

				list.innerHTML = ''
				for (i = 0; i < products.length; i++) {
					wrapper.innerHTML = createItemMarkup(products[i], i)
					fragment.appendChild(wrapper.firstChild)
				}
				list.appendChild(fragment)
			}

			function updateState() {
				var totals = getTotals()
				var isEmpty = products.length === 0
				var selectedCount = getSelectedProducts().length
				var allSelected = products.length > 0 && selectedCount === products.length

				subtotalNode.textContent = formatPrice(totals.subtotal)
				shippingNode.textContent = 'На оформлении'
				totalNode.textContent = formatPrice(totals.total)
				summary.textContent = isEmpty
					? 'Корзина пустая. Добавьте товары, чтобы перейти к оформлению.'
					: 'В корзине ' + totals.count + ' ' + getProductsWord(totals.count) + '. Можно все проверить и оформить заказ.'
				layout.hidden = isEmpty
				empty.hidden = !isEmpty
				clearSelectedButton.disabled = selectedCount === 0
				selectAll.checked = allSelected
				selectAll.indeterminate = !allSelected && selectedCount > 0
				checkoutLink.classList.toggle('is-disabled', totals.count === 0)
				checkoutLink.setAttribute('href', totals.count ? checkoutUrl : '#')
			}

			function renderCart() {
				renderItems()
				updateState()
			}

			function updateProductQuantity(index, nextQuantity) {
				var product = products[index]
				var previousQuantity
				var quantity
				if (!product) return
				previousQuantity = product.quantity
				quantity = Math.max(1, Math.min(Number(nextQuantity) || 1, product.maxQuantity))
				product.quantity = quantity
				product.isSelected = true
				renderCart()
				syncCartAction('quantity', {
					productId: product.id,
					quantity: quantity
				})
				requestCartUpdate({
					cart_action_type: 'quantity',
					cart_key: product.key,
					quantity: quantity
				}).catch(function () {
					product.quantity = previousQuantity
					renderCart()
				})
			}

			list.addEventListener('click', function (event) {
				var item = event.target.closest('.cart-page__item')
				var index = item ? Number(item.getAttribute('data-cart-index')) : -1
				var removed
				if (!item || !products[index]) return

				if (event.target.closest('.js-cart-remove')) {
					removed = products[index]
					syncCartAction('remove', {
						productId: removed.id
					})
					products.splice(index, 1)
					renderCart()
					requestCartUpdate({
						cart_action_type: 'remove',
						cart_key: removed.key
					}).catch(function () {
						products.splice(index, 0, removed)
						renderCart()
					})
					return
				}

				if (event.target.closest('.js-cart-decrease')) {
					updateProductQuantity(index, products[index].quantity - 1)
					return
				}

				if (event.target.closest('.js-cart-increase')) {
					updateProductQuantity(index, products[index].quantity + 1)
				}
			})

			list.addEventListener('change', function (event) {
				var item = event.target.closest('.cart-page__item')
				var index = item ? Number(item.getAttribute('data-cart-index')) : -1
				if (!item || !products[index]) return

				if (event.target.classList.contains('js-cart-item-check')) {
					products[index].isSelected = event.target.checked
					updateState()
					syncCartAction('select', {
						productId: products[index].id,
						selected: products[index].isSelected
					})
					return
				}

				if (event.target.classList.contains('js-cart-quantity')) {
					updateProductQuantity(index, event.target.value)
				}
			})

			selectAll.addEventListener('change', function () {
				products = products.map(function (product) {
					product.isSelected = selectAll.checked
					return product
				})
				renderCart()
				syncCartAction('selectAll', {
					selected: selectAll.checked
				})
			})

			clearSelectedButton.addEventListener('click', function () {
				var removedProducts = products.filter(function (product) {
					return product.isSelected
				})
				var removedKeys = removedProducts.map(function (product) {
					return product.key
				})

				products = products.filter(function (product) {
					return !product.isSelected
				})
				renderCart()
				syncCartAction('clearSelected', {})

				if (!removedKeys.length) return

				requestCartUpdate({
					cart_action_type: 'remove_selected',
					cart_keys: removedKeys
				}).catch(function () {
					products = products.concat(removedProducts)
					renderCart()
				})
			})

			renderCart()
		})()
	</script>
</section>

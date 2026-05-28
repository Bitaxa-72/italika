(function ($) {
  function getCartLink() {
    return document.querySelector('.js-header-cart, .header__actions a[href*="cart"], .header__actions a[href*="korzina"], .header__actions a[href*="basket"]')
  }

  function setCartCount(count) {
    var countNode = document.querySelector('.js-cart-count')

    if (!countNode) {
      return
    }

    countNode.textContent = String(Math.max(0, Number(count) || 0))
    countNode.hidden = Number(countNode.textContent) <= 0
    countNode.classList.add('is-pulsing')
    window.setTimeout(function () {
      countNode.classList.remove('is-pulsing')
    }, 420)
  }

  function getImageFromButton(button) {
    var card = button ? button.closest('.product-page, .ecomcard, .sale-products__card, li.product') : null

    return card ? card.querySelector('img') : null
  }

  function flyToCart(button) {
    var image = getImageFromButton(button)
    var cart = getCartLink()
    var imageRect
    var cartRect
    var ghost

    if (!image || !cart) {
      return
    }

    imageRect = image.getBoundingClientRect()
    cartRect = cart.getBoundingClientRect()

    if (!imageRect.width || !imageRect.height) {
      return
    }

    ghost = image.cloneNode(false)
    ghost.className = 'italika-cart-flyer'
    ghost.style.left = imageRect.left + 'px'
    ghost.style.top = imageRect.top + 'px'
    ghost.style.width = imageRect.width + 'px'
    ghost.style.height = imageRect.height + 'px'
    document.body.appendChild(ghost)

    requestAnimationFrame(function () {
      ghost.style.transform =
        'translate(' +
        (cartRect.left + cartRect.width / 2 - imageRect.left - imageRect.width / 2) +
        'px,' +
        (cartRect.top + cartRect.height / 2 - imageRect.top - imageRect.height / 2) +
        'px) scale(.18)'
      ghost.style.opacity = '0'
    })

    window.setTimeout(function () {
      if (ghost && ghost.parentNode) {
        ghost.parentNode.removeChild(ghost)
      }

      cart.classList.add('is-cart-target')
      window.setTimeout(function () {
        cart.classList.remove('is-cart-target')
      }, 420)
    }, 680)
  }

  function initAddToCartAnimation() {
    $(document.body).on('adding_to_cart', function (event, button) {
      if (button && button.length) {
        flyToCart(button.get(0))
      }
    })

    $(document.body).on('added_to_cart', function (event, fragments, cartHash, button) {
      var countNode

      if (fragments && fragments['.js-cart-count']) {
        countNode = document.createElement('span')
        countNode.innerHTML = fragments['.js-cart-count']
        countNode = countNode.querySelector('.js-cart-count')

        if (countNode) {
          setCartCount(countNode.textContent)
        }
      }

      if (button && button.length) {
        button.addClass('is-added')
        window.setTimeout(function () {
          button.removeClass('is-added')
        }, 1100)
      }
    })
  }

  function initCheckoutShell() {
    var section = document.querySelector('.checkout-page')
    var addressBlock = section ? section.querySelector('.js-checkout-address') : null
    var deliveryRadios = section ? section.querySelectorAll('.js-checkout-delivery') : []
    var customerTypeRadios = section ? section.querySelectorAll('.js-checkout-customer-type') : []
    var companyFieldsBlock = section ? section.querySelector('.js-checkout-company-fields') : null
    var paymentNote = section ? section.querySelector('.js-checkout-payment-note') : null
    var addressLabel = section ? section.querySelector('.js-checkout-address-label') : null
    var addressInput = section ? section.querySelector('.js-checkout-address-input') : null

    if (!section) {
      return
    }

    function getPaymentNote(value) {
      if (value === 'italika_card_pickup') {
        return 'Оплата картой при получении после подтверждения менеджером.'
      }
      if (value === 'italika_card_pickup') {
        return 'Оплата картой пройдет на месте после подтверждения менеджером.'
      }

      if (value === 'italika_payment_link') {
        return 'Ссылку для оплаты отправит менеджер после проверки состава заказа.'
      }

      return 'Оплата наличными пройдет после подтверждения заказа менеджером.'
    }

    function syncDelivery() {
      var checked = section.querySelector('.js-checkout-delivery:checked')
      var methodId = checked ? checked.getAttribute('data-method-id') : ''
      var isDelivery = methodId === 'italika_delivery'
      var addressFields = addressBlock ? addressBlock.querySelectorAll('input, textarea, select') : []

      if (addressBlock) {
        addressBlock.hidden = !isDelivery
      }

      Array.prototype.forEach.call(addressFields, function (field) {
        field.disabled = !isDelivery
        field.required = isDelivery
      })

      if (addressLabel) {
        addressLabel.textContent = 'Адрес доставки'
      }

      if (addressInput) {
        addressInput.placeholder = 'Улица, дом, квартира или офис'
      }
    }

    function syncPayment() {
      var checked = section.querySelector('.js-checkout-payment:checked')

      if (paymentNote && checked) {
        paymentNote.textContent = getPaymentNote(checked.value)
      }
    }

    function syncCustomerType() {
      var checked = section.querySelector('.js-checkout-customer-type:checked')
      var isLegalEntity = checked && checked.value === 'legal_entity'
      var companyFields = companyFieldsBlock ? companyFieldsBlock.querySelectorAll('input, textarea, select') : []

      if (companyFieldsBlock) {
        companyFieldsBlock.hidden = !isLegalEntity
      }

      Array.prototype.forEach.call(companyFields, function (field) {
        field.required = isLegalEntity
      })
    }

    Array.prototype.forEach.call(deliveryRadios, function (radio) {
      radio.addEventListener('change', function () {
        syncDelivery()
        $(document.body).trigger('update_checkout')
      })
    })

    Array.prototype.forEach.call(customerTypeRadios, function (radio) {
      radio.addEventListener('change', syncCustomerType)
    })

    section.addEventListener('change', function (event) {
      if (event.target.classList.contains('js-checkout-payment')) {
        syncPayment()
      }
    })

    syncDelivery()
    syncPayment()
    syncCustomerType()
  }

  function initProductPage() {
    var sections = document.querySelectorAll('.product-page')

    Array.prototype.forEach.call(sections, function (section) {
      var image = section.querySelector('.js-product-image')
      var thumbs = section.querySelectorAll('.js-product-thumb')
      var quantityInput = section.querySelector('.js-product-quantity')
      var minusButton = section.querySelector('.js-product-minus')
      var plusButton = section.querySelector('.js-product-plus')
      var cartButton = section.querySelector('.js-product-cart')

      function syncCartQuantity() {
        if (!cartButton || !quantityInput) {
          return
        }

        cartButton.setAttribute('data-quantity', quantityInput.value)
      }

      function setQuantity(value) {
        var min = quantityInput ? Number(quantityInput.min) || 1 : 1
        var max = quantityInput ? Number(quantityInput.max) || 99 : 99
        var nextValue = Math.max(min, Math.min(Number(value) || min, max))

        if (!quantityInput) {
          return
        }

        quantityInput.value = nextValue
        syncCartQuantity()
      }

      Array.prototype.forEach.call(thumbs, function (thumb) {
        thumb.addEventListener('click', function () {
          var nextImage = thumb.getAttribute('data-image')

          if (!image || !nextImage) {
            return
          }

          image.setAttribute('src', nextImage)

          Array.prototype.forEach.call(thumbs, function (item) {
            item.classList.toggle('is-active', item === thumb)
          })
        })
      })

      if (minusButton && quantityInput) {
        minusButton.addEventListener('click', function () {
          setQuantity(Number(quantityInput.value) - 1)
        })
      }

      if (plusButton && quantityInput) {
        plusButton.addEventListener('click', function () {
          setQuantity(Number(quantityInput.value) + 1)
        })
      }

      if (quantityInput) {
        quantityInput.addEventListener('change', function () {
          setQuantity(quantityInput.value)
        })

        quantityInput.addEventListener('input', syncCartQuantity)
      }

      if (cartButton && quantityInput) {
        cartButton.addEventListener('click', function () {
          setQuantity(quantityInput.value)
        })
      }

      syncCartQuantity()
    })
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initAddToCartAnimation()
      initCheckoutShell()
      initProductPage()
    })
  } else {
    initAddToCartAnimation()
    initCheckoutShell()
    initProductPage()
  }
})(jQuery)

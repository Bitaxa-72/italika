(function () {
  function findClosestButton(node) {
    while (node && node !== document) {
      if (
        node.classList &&
        node.classList.contains('js-italika-favorite')
      ) {
        return node
      }
      node = node.parentNode
    }
    return null
  }

  function triggerLogin(selector) {
    var target = null
    var finalSelector = selector || ''

    if (finalSelector) {
      target = document.querySelector(finalSelector)
    }

    if (target) {
      target.click()
      return
    }

    if (typeof window.CustomEvent === 'function') {
      document.dispatchEvent(
        new CustomEvent('italika:favorites:auth-required', {
          bubbles: true
        })
      )
      return
    }

    var event = document.createEvent('CustomEvent')
    event.initCustomEvent('italika:favorites:auth-required', true, false, {})
    document.dispatchEvent(event)
  }

  function setButtonState(button, active, label) {
    if (!button) {
      return
    }

    button.classList.toggle('is-active', !!active)
    button.setAttribute('aria-pressed', active ? 'true' : 'false')

    if (label) {
      button.setAttribute('aria-label', label)
    }
  }

  function syncProductButtons(productId, active, label) {
    var buttons = document.querySelectorAll('.js-italika-favorite[data-product-id="' + productId + '"]')
    var i = 0

    for (i = 0; i < buttons.length; i++) {
      setButtonState(buttons[i], active, label)
      buttons[i].removeAttribute('data-loading')
    }
  }

  function syncFavoritesCount(count) {
    var counters = document.querySelectorAll('.js-favorites-count')
    var value = parseInt(count || '0', 10)
    var i = 0

    if (isNaN(value) || value < 0) {
      value = 0
    }

    for (i = 0; i < counters.length; i++) {
      counters[i].textContent = String(value)
    }
  }

  function dispatchFavoritesUpdated(button, data) {
    var event = null
    var detail = {
      productId: parseInt(data.product_id || '0', 10),
      active: !!data.active,
      count: parseInt(data.count || '0', 10)
    }

    if (typeof window.CustomEvent === 'function') {
      event = new CustomEvent('italika:favorites:updated', {
        bubbles: true,
        detail: detail
      })
    } else {
      event = document.createEvent('CustomEvent')
      event.initCustomEvent('italika:favorites:updated', true, false, detail)
    }

    button.dispatchEvent(event)
  }

  function requestToggle(productId, done, fail) {
    var xhr = new XMLHttpRequest()
    var data = new FormData()

    data.append('action', 'italika_favorites_toggle')
    data.append('nonce', italikaFavoritesData.nonce)
    data.append('product_id', productId)

    xhr.open('POST', italikaFavoritesData.ajaxUrl, true)

    xhr.onreadystatechange = function () {
      var response = null

      if (xhr.readyState !== 4) {
        return
      }

      if (xhr.status < 200 || xhr.status >= 300) {
        fail()
        return
      }

      try {
        response = JSON.parse(xhr.responseText)
      } catch (e) {
        fail()
        return
      }

      if (!response || typeof response !== 'object') {
        fail()
        return
      }

      done(response)
    }

    xhr.send(data)
  }

  document.addEventListener('click', function (event) {
    var button = findClosestButton(event.target)
    var productId = 0
    var authRequired = '0'

    if (!button) {
      return
    }

    event.preventDefault()

    if (button.getAttribute('data-loading') === '1') {
      return
    }

    productId = parseInt(button.getAttribute('data-product-id') || '0', 10)
    authRequired = button.getAttribute('data-auth-required') || '0'

    if (!productId) {
      return
    }

    if (authRequired === '1') {
      triggerLogin(italikaFavoritesData.loginTriggerSelector || '')
      return
    }

    button.setAttribute('data-loading', '1')

    requestToggle(
      productId,
      function (response) {
        if (!response.success || !response.data) {
          syncProductButtons(productId, button.classList.contains('is-active'), button.getAttribute('aria-label') || '')
          return
        }

        if (response.data.requires_auth) {
          syncProductButtons(productId, button.classList.contains('is-active'), button.getAttribute('aria-label') || '')
          triggerLogin(response.data.login_trigger_selector || italikaFavoritesData.loginTriggerSelector || '')
          return
        }

        syncProductButtons(productId, !!response.data.active, response.data.label || '')
        syncFavoritesCount(response.data.count || 0)
        dispatchFavoritesUpdated(button, response.data)
      },
      function () {
        syncProductButtons(productId, button.classList.contains('is-active'), button.getAttribute('aria-label') || '')
      }
    )
  })
})()

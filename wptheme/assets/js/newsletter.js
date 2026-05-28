(function () {
  function getData() {
    return window.italikaNewsletterData || {}
  }

  function setMessage(node, text, type) {
    if (!node) {
      return
    }

    node.hidden = false
    node.textContent = text
    node.classList.toggle('is-error', type === 'error')
    node.classList.toggle('is-success', type !== 'error')
  }

  function initForm(form) {
    var data = getData()
    var email = form.querySelector('input[name="email"]')
    var button = form.querySelector('.subscribe-modal__submit')
    var message = form.querySelector('.js-italika-newsletter-message')

    form.addEventListener('submit', function (event) {
      var xhr = new XMLHttpRequest()
      var formData = new FormData(form)
      var buttonText = button ? button.textContent : ''

      event.preventDefault()

      if (!email || !email.value.trim()) {
        setMessage(message, 'Укажите email.', 'error')
        return
      }

      if (button) {
        button.disabled = true
        button.textContent = 'Отправляем'
      }

      formData.set('action', 'italika_newsletter_subscribe')
      formData.set('nonce', data.nonce || '')
      formData.set('email', email.value.trim())

      xhr.open('POST', data.ajaxUrl || form.action, true)

      xhr.onreadystatechange = function () {
        var response = null
        var text = 'Не удалось оформить подписку. Попробуйте еще раз.'
        var type = 'error'

        if (xhr.readyState !== 4) {
          return
        }

        if (button) {
          button.disabled = false
          button.textContent = buttonText
        }

        try {
          response = JSON.parse(xhr.responseText)
        } catch (e) {
          setMessage(message, text, type)
          return
        }

        if (response && response.data && response.data.message) {
          text = response.data.message
        }

        if (response && response.success) {
          type = 'success'
          form.reset()
        }

        setMessage(message, text, type)
      }

      xhr.send(formData)
    })
  }

  function init() {
    var forms = document.querySelectorAll('.js-italika-newsletter-form')
    var i = 0

    for (i = 0; i < forms.length; i++) {
      initForm(forms[i])
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init)
  } else {
    init()
  }
})()

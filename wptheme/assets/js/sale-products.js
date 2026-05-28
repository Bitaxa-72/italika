(function () {
  function appendHtml(target, html) {
    var wrapper = document.createElement('div')
    var fragment = document.createDocumentFragment()

    wrapper.innerHTML = html

    while (wrapper.firstChild) {
      fragment.appendChild(wrapper.firstChild)
    }

    target.appendChild(fragment)
  }

  function createSkeletonMarkup() {
    return '' +
      '<div class="sale-products__card sale-products__card--skeleton">' +
      '<span class="sale-products__skeleton-badges">' +
      '<span class="sale-products__skeleton sale-products__skeleton-badge"></span>' +
      '</span>' +
      '<span class="sale-products__image-box">' +
      '<span class="sale-products__skeleton sale-products__skeleton-image"></span>' +
      '</span>' +
      '<span class="sale-products__content">' +
      '<span>' +
      '<span class="sale-products__skeleton sale-products__skeleton-title"></span>' +
      '<span class="sale-products__skeleton sale-products__skeleton-title"></span>' +
      '<span class="sale-products__skeleton sale-products__skeleton-stock"></span>' +
      '</span>' +
      '<span class="sale-products__price-block">' +
      '<span class="sale-products__skeleton sale-products__skeleton-price"></span>' +
      '<span class="sale-products__skeleton sale-products__skeleton-old"></span>' +
      '<span class="sale-products__skeleton sale-products__skeleton-benefit"></span>' +
      '</span>' +
      '<span class="sale-products__skeleton sale-products__skeleton-cart"></span>' +
      '</span>' +
      '</div>'
  }

  function appendSkeletons(grid, count) {
    var html = ''
    var i = 0

    for (i = 0; i < count; i++) {
      html += createSkeletonMarkup()
    }

    appendHtml(grid, html)
  }

  function removeSkeletons(grid) {
    var skeletons = grid.querySelectorAll('.sale-products__card--skeleton')
    var i = 0

    for (i = 0; i < skeletons.length; i++) {
      skeletons[i].parentNode.removeChild(skeletons[i])
    }
  }

  function updateButtonState(section, button, text, hasMore, isLoading) {
    if (!button || !text) {
      return
    }

    if (isLoading) {
      button.disabled = true
      button.classList.add('is-loading')
      button.classList.remove('is-finished')
      text.textContent = 'Загрузка'
      return
    }

    button.classList.remove('is-loading')

    if (!hasMore) {
      button.disabled = true
      button.classList.add('is-finished')
      text.textContent = 'Больше нет товаров'
      return
    }

    button.disabled = false
    button.classList.remove('is-finished')
    text.textContent = 'Загрузить еще'
  }

  function initSection(section) {
    var grid = section.querySelector('.js-sale-products-grid')
    var button = section.querySelector('.js-sale-products-more')
    var text = button ? button.querySelector('.sale-products__more-text') : null
    var chunkSize = parseInt(section.getAttribute('data-chunk-size') || '12', 10)
    var renderedCount = parseInt(section.getAttribute('data-rendered-count') || '0', 10)
    var totalCount = parseInt(section.getAttribute('data-total-count') || '0', 10)
    var isLoading = false

    if (!grid || !button || !text) {
      return
    }

    updateButtonState(section, button, text, renderedCount < totalCount, false)

    button.addEventListener('click', function () {
      var xhr = new XMLHttpRequest()
      var formData = new FormData()
      var nextCount = Math.min(chunkSize, Math.max(0, totalCount - renderedCount))

      if (isLoading || renderedCount >= totalCount) {
        return
      }

      isLoading = true
      updateButtonState(section, button, text, true, true)
      appendSkeletons(grid, nextCount)

      formData.append('action', 'italika_sale_products_load_more')
      formData.append('nonce', italikaSaleProductsData.nonce)
      formData.append('offset', renderedCount)
      formData.append('limit', chunkSize)

      xhr.open('POST', italikaSaleProductsData.ajaxUrl, true)

      xhr.onreadystatechange = function () {
        var response = null

        if (xhr.readyState !== 4) {
          return
        }

        removeSkeletons(grid)
        isLoading = false

        if (xhr.status < 200 || xhr.status >= 300) {
          updateButtonState(section, button, text, renderedCount < totalCount, false)
          return
        }

        try {
          response = JSON.parse(xhr.responseText)
        } catch (e) {
          updateButtonState(section, button, text, renderedCount < totalCount, false)
          return
        }

        if (!response || !response.success || !response.data) {
          updateButtonState(section, button, text, renderedCount < totalCount, false)
          return
        }

        if (response.data.html) {
          appendHtml(grid, response.data.html)
        }

        renderedCount = parseInt(response.data.nextOffset || renderedCount, 10)
        section.setAttribute('data-rendered-count', String(renderedCount))
        updateButtonState(section, button, text, !!response.data.hasMore, false)
      }

      xhr.send(formData)
    })
  }

  function init() {
    var sections = document.querySelectorAll('.js-sale-products-section')
    var i = 0

    for (i = 0; i < sections.length; i++) {
      initSection(sections[i])
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init)
  } else {
    init()
  }
})()
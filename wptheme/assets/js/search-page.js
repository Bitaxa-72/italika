(function () {
  function getData() {
    return window.italikaSearchData || {}
  }

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

  function updateButton(button, text, hasMore, isLoading) {
    if (!button || !text) {
      return
    }

    button.hidden = !hasMore && !isLoading
    button.disabled = !hasMore || isLoading
    button.classList.toggle('is-loading', isLoading)
    text.textContent = isLoading ? 'Загрузка' : 'Загрузить еще'
  }

  function updateSummary(summary, query, totalCount) {
    var suffix = query ? ' по запросу "' + query + '"' : ''

    if (summary) {
      summary.textContent = totalCount
        ? 'Найдено ' + totalCount + ' товаров' + suffix
        : 'Нет товаров' + suffix
    }
  }

  function initResults(section) {
    var data = getData()
    var grid = section.querySelector('.js-search-results-grid')
    var empty = section.querySelector('.js-search-results-empty')
    var summary = section.querySelector('.js-search-results-summary')
    var button = section.querySelector('.js-search-results-more')
    var text = button ? button.querySelector('.sale-products__more-text') : null
    var query = section.getAttribute('data-query') || ''
    var chunkSize = parseInt(section.getAttribute('data-chunk-size') || '12', 10)
    var renderedCount = parseInt(section.getAttribute('data-rendered-count') || '0', 10)
    var totalCount = parseInt(section.getAttribute('data-total-count') || '0', 10)
    var isLoading = false

    if (!grid || !button || !text) {
      return
    }

    updateButton(button, text, renderedCount < totalCount, false)

    button.addEventListener('click', function () {
      var xhr = new XMLHttpRequest()
      var formData = new FormData()
      var nextCount = Math.min(chunkSize, Math.max(0, totalCount - renderedCount))

      if (isLoading || renderedCount >= totalCount) {
        return
      }

      isLoading = true
      updateButton(button, text, true, true)
      appendSkeletons(grid, nextCount)

      formData.append('action', 'italika_search_load_more')
      formData.append('nonce', data.nonce || '')
      formData.append('query', query)
      formData.append('offset', renderedCount)
      formData.append('limit', chunkSize)

      xhr.open('POST', data.ajaxUrl || '', true)

      xhr.onreadystatechange = function () {
        var response = null

        if (xhr.readyState !== 4) {
          return
        }

        removeSkeletons(grid)
        isLoading = false

        if (xhr.status < 200 || xhr.status >= 300) {
          updateButton(button, text, renderedCount < totalCount, false)
          return
        }

        try {
          response = JSON.parse(xhr.responseText)
        } catch (e) {
          updateButton(button, text, renderedCount < totalCount, false)
          return
        }

        if (!response || !response.success || !response.data) {
          updateButton(button, text, renderedCount < totalCount, false)
          return
        }

        if (response.data.html) {
          appendHtml(grid, response.data.html)
        }

        renderedCount = parseInt(response.data.nextOffset || renderedCount, 10)
        totalCount = parseInt(response.data.totalCount || totalCount, 10)
        section.setAttribute('data-rendered-count', String(renderedCount))
        section.setAttribute('data-total-count', String(totalCount))

        if (empty) {
          empty.hidden = renderedCount > 0
        }

        updateSummary(summary, query, totalCount)
        updateButton(button, text, !!response.data.hasMore, false)
      }

      xhr.send(formData)
    })
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;')
  }

  function getResultsUrl(query) {
    var data = getData()
    var url = new URL(data.searchUrl || window.location.origin + '/', window.location.origin)

    url.searchParams.set('post_type', 'product')

    if (query) {
      url.searchParams.set('s', query)
    } else {
      url.searchParams.delete('s')
    }

    return url.toString()
  }

  function renderSuggestItems(list, items) {
    var html = ''
    var i = 0

    for (i = 0; i < items.length; i++) {
      html += '' +
        '<a class="search-suggest__item" href="' + escapeHtml(items[i].url) + '">' +
        '<span class="search-suggest__icon" aria-hidden="true">' +
        '<svg viewBox="0 0 24 24" fill="none">' +
        '<path d="M6 7.5h12l-1 11H7L6 7.5Z" stroke-width="1.8" stroke-linejoin="round"></path>' +
        '<path d="M9 7.5a3 3 0 0 1 6 0" stroke-width="1.8" stroke-linecap="round"></path>' +
        '</svg>' +
        '</span>' +
        '<span class="search-suggest__content">' +
        '<strong>' + escapeHtml(items[i].title) + '</strong>' +
        '</span>' +
        '</a>'
    }

    list.innerHTML = html
  }

  function initSuggestions(form) {
    var data = getData()
    var input = form.querySelector('.js-italika-search-input')
    var list = form.querySelector('.js-italika-search-suggest-list')
    var allLink = form.querySelector('.js-italika-search-all')
    var timer = null
    var requestId = 0

    if (!input || !list) {
      return
    }

    list.innerHTML = ''

    form.addEventListener('submit', function (event) {
      if (!input.value.trim()) {
        event.preventDefault()
      }
    })

    input.addEventListener('input', function () {
      var query = input.value.trim()
      var currentRequestId
      var xhr
      var formData

      if (allLink) {
        allLink.href = getResultsUrl(query)
      }

      window.clearTimeout(timer)

      if (query.length < 3) {
        list.innerHTML = ''
        return
      }

      timer = window.setTimeout(function () {
        currentRequestId = ++requestId
        xhr = new XMLHttpRequest()
        formData = new FormData()

        formData.append('action', 'italika_search_suggest')
        formData.append('nonce', data.nonce || '')
        formData.append('query', query)

        xhr.open('POST', data.ajaxUrl || '', true)

        xhr.onreadystatechange = function () {
          var response = null

          if (xhr.readyState !== 4 || currentRequestId !== requestId) {
            return
          }

          if (xhr.status < 200 || xhr.status >= 300) {
            list.innerHTML = ''
            return
          }

          try {
            response = JSON.parse(xhr.responseText)
          } catch (e) {
            list.innerHTML = ''
            return
          }

          if (!response || !response.success || !response.data) {
            list.innerHTML = ''
            return
          }

          if (allLink && response.data.resultsUrl) {
            allLink.href = response.data.resultsUrl
          }

          renderSuggestItems(list, response.data.items || [])
        }

        xhr.send(formData)
      }, 220)
    })
  }

  function init() {
    var sections = document.querySelectorAll('.js-search-results-page')
    var forms = document.querySelectorAll('.js-italika-search-form')
    var i = 0

    for (i = 0; i < sections.length; i++) {
      initResults(sections[i])
    }

    for (i = 0; i < forms.length; i++) {
      initSuggestions(forms[i])
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init)
  } else {
    init()
  }
})()

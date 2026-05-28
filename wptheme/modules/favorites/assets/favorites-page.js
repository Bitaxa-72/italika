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

  function setLoading(button, isLoading, hasMore) {
    var text = button ? button.querySelector('.sale-products__more-text') : null

    if (!button) {
      return
    }

    button.classList.toggle('is-loading', !!isLoading)
    button.disabled = !!isLoading || !hasMore
    button.hidden = !hasMore && !isLoading

    if (text) {
      text.textContent = isLoading ? 'Загрузка' : 'Загрузить еще'
    }
  }

  function requestPage(categoryId, offset, limit, done, fail) {
    var xhr = new XMLHttpRequest()
    var data = new FormData()

    data.append('action', 'italika_favorites_page_load')
    data.append('nonce', italikaFavoritesPageData.nonce)
    data.append('category_id', categoryId)
    data.append('offset', offset)
    data.append('limit', limit)

    xhr.open('POST', italikaFavoritesPageData.ajaxUrl, true)

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

      if (!response || !response.success || !response.data) {
        fail()
        return
      }

      done(response.data)
    }

    xhr.send(data)
  }

  function initPage(page) {
    var grid = page.querySelector('.js-favorites-page-grid')
    var filters = page.querySelector('.js-favorites-page-filters')
    var moreButton = page.querySelector('.js-favorites-page-more')
    var summary = page.querySelector('.js-favorites-page-summary')
    var empty = page.querySelector('.js-favorites-page-empty')
    var limit = parseInt(page.getAttribute('data-limit') || '12', 10)
    var renderedCount = parseInt(page.getAttribute('data-rendered-count') || '0', 10)
    var activeCategoryId = parseInt(page.getAttribute('data-active-category-id') || '0', 10)
    var isLoading = false

    if (!grid || !filters || !moreButton) {
      return
    }

    function applyResponse(data, reset) {
      renderedCount = parseInt(data.nextOffset || '0', 10)
      page.setAttribute('data-rendered-count', String(renderedCount))
      page.setAttribute('data-total-count', String(data.filteredCount || 0))

      if (reset) {
        grid.innerHTML = ''
      }

      if (data.html) {
        appendHtml(grid, data.html)
      }

      if (typeof data.filtersHtml === 'string') {
        filters.innerHTML = data.filtersHtml
      }

      if (summary && data.summary) {
        summary.textContent = data.summary
      }

      if (empty) {
        empty.hidden = !data.isEmpty
      }

      grid.hidden = !!data.isEmpty
      filters.hidden = !!data.isEmpty
      setLoading(moreButton, false, !!data.hasMore)
    }

    function load(reset) {
      var offset = reset ? 0 : renderedCount
      var skeletonCount = reset ? limit : Math.max(1, limit)

      if (isLoading) {
        return
      }

      isLoading = true
      setLoading(moreButton, true, true)

      if (reset) {
        grid.innerHTML = ''
      }

      appendSkeletons(grid, skeletonCount)

      requestPage(
        activeCategoryId,
        offset,
        limit,
        function (data) {
          isLoading = false
          removeSkeletons(grid)
          applyResponse(data, reset)
        },
        function () {
          isLoading = false
          removeSkeletons(grid)
          setLoading(moreButton, false, renderedCount < parseInt(page.getAttribute('data-total-count') || '0', 10))
        }
      )
    }

    filters.addEventListener('click', function (event) {
      var button = event.target.closest('.favorites-page__filter')

      if (!button) {
        return
      }

      activeCategoryId = parseInt(button.getAttribute('data-category-id') || '0', 10)
      page.setAttribute('data-active-category-id', String(activeCategoryId))
      load(true)
    })

    moreButton.addEventListener('click', function () {
      load(false)
    })

    document.addEventListener('italika:favorites:updated', function (event) {
      if (!event.detail || event.detail.active) {
        return
      }

      if (!page.contains(event.target)) {
        load(true)
        return
      }

      load(true)
    })
  }

  function init() {
    var pages = document.querySelectorAll('.js-favorites-page')
    var i = 0

    for (i = 0; i < pages.length; i++) {
      initPage(pages[i])
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init)
  } else {
    init()
  }
})()

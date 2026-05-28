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

  function updateCount(countNode, renderedCount, totalCount) {
    if (!countNode) {
      return
    }

    countNode.textContent = 'Показано ' + renderedCount + ' из ' + totalCount
  }

  function updateButton(button, text, hasMore, isLoading) {
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
    text.textContent = 'Загрузить еще 21 товар'
  }

  function isMobileCategoryMenu() {
    return window.matchMedia
      ? window.matchMedia('(max-width: 980px)').matches
      : window.innerWidth <= 980
  }

  function getDirectChildByClass(node, className) {
    var children = node ? node.children : []
    var i = 0

    for (i = 0; i < children.length; i++) {
      if (children[i].classList.contains(className)) {
        return children[i]
      }
    }

    return null
  }

  function getDirectSubcats(item) {
    return getDirectChildByClass(item, 'catalog-page__subcats')
  }

  function getDirectToggle(item) {
    return getDirectChildByClass(item, 'catalog-page__cat-toggle')
  }

  function setCategoryItemOpen(item, isOpen) {
    var toggle = getDirectToggle(item)

    item.classList.toggle('is-open', isOpen)

    if (toggle) {
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false')
    }
  }

  function closeSiblingCategories(item) {
    var parent = item.parentNode
    var children = parent ? parent.children : []
    var i = 0

    for (i = 0; i < children.length; i++) {
      if (
        children[i] !== item &&
        children[i].classList &&
        (children[i].classList.contains('catalog-page__cat-item') ||
          children[i].classList.contains('catalog-page__subcat-item'))
      ) {
        setCategoryItemOpen(children[i], false)
      }
    }
  }

  function initCategoryMenu(section) {
    var categoryList = section.querySelector('.catalog-page__cat-list')
    var drawer = section.querySelector('.catalog-page__aside')
    var openButtons = section.querySelectorAll('.js-catalog-menu-open')
    var closeButtons = section.querySelectorAll('.js-catalog-menu-close')
    var items
    var i = 0
    var item
    var subcats
    var toggle

    function setDrawerOpen(isOpen) {
      var j = 0

      section.classList.toggle('is-category-drawer-open', isOpen)
      document.body.classList.toggle('is-catalog-drawer-open', isOpen)

      if (drawer) {
        drawer.setAttribute('aria-hidden', isOpen ? 'false' : 'true')
      }

      for (j = 0; j < openButtons.length; j++) {
        openButtons[j].setAttribute('aria-expanded', isOpen ? 'true' : 'false')
      }
    }

    if (!categoryList) {
      return
    }

    if (drawer) {
      drawer.setAttribute('aria-hidden', isMobileCategoryMenu() ? 'true' : 'false')
    }

    for (i = 0; i < openButtons.length; i++) {
      openButtons[i].addEventListener('click', function (event) {
        event.preventDefault()
        setDrawerOpen(true)
      })
    }

    for (i = 0; i < closeButtons.length; i++) {
      closeButtons[i].addEventListener('click', function (event) {
        event.preventDefault()
        setDrawerOpen(false)
      })
    }

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && section.classList.contains('is-category-drawer-open')) {
        setDrawerOpen(false)
      }
    })

    window.addEventListener('resize', function () {
      if (!isMobileCategoryMenu()) {
        setDrawerOpen(false)

        if (drawer) {
          drawer.setAttribute('aria-hidden', 'false')
        }
      } else if (!section.classList.contains('is-category-drawer-open') && drawer) {
        drawer.setAttribute('aria-hidden', 'true')
      }
    })

    items = categoryList.querySelectorAll('.catalog-page__cat-item, .catalog-page__subcat-item')

    for (i = 0; i < items.length; i++) {
      item = items[i]
      subcats = getDirectSubcats(item)

      if (!subcats) {
        continue
      }

      item.classList.add('catalog-page__cat-item--has-children')

      if (getDirectToggle(item)) {
        continue
      }

      toggle = document.createElement('button')
      toggle.className = 'catalog-page__cat-toggle'
      toggle.type = 'button'
      toggle.setAttribute('aria-expanded', item.classList.contains('is-open') ? 'true' : 'false')
      toggle.setAttribute('aria-label', 'Показать подкатегории')
      item.insertBefore(toggle, subcats)
    }

    categoryList.addEventListener('click', function (event) {
      var toggleButton = event.target.closest('.catalog-page__cat-toggle')
      var link = event.target.closest('.catalog-page__cat-link, .catalog-page__subcat-link')
      var currentItem
      var hasSubcats
      var shouldOpen

      if (toggleButton) {
        currentItem = toggleButton.parentNode
        hasSubcats = getDirectSubcats(currentItem)

        if (!hasSubcats) {
          return
        }

        event.preventDefault()
        event.stopPropagation()
        shouldOpen = !currentItem.classList.contains('is-open')
        closeSiblingCategories(currentItem)
        setCategoryItemOpen(currentItem, shouldOpen)
        return
      }

      if (!link || !isMobileCategoryMenu()) {
        return
      }

      currentItem = link.closest('.catalog-page__cat-item, .catalog-page__subcat-item')
      hasSubcats = currentItem && getDirectSubcats(currentItem)

      if (!hasSubcats || currentItem.classList.contains('is-open')) {
        return
      }

      event.preventDefault()
      closeSiblingCategories(currentItem)
      setCategoryItemOpen(currentItem, true)
    })
  }

  function initCatalog(section) {
    var grid = section.querySelector('.js-catalog-grid')
    var button = section.querySelector('.js-catalog-more')
    var text = button ? button.querySelector('.sale-products__more-text') : null
    var countNode = section.querySelector('.js-catalog-count')
    var category = section.getAttribute('data-category') || ''
    var chunkSize = parseInt(section.getAttribute('data-chunk-size') || '21', 10)
    var renderedCount = parseInt(section.getAttribute('data-rendered-count') || '0', 10)
    var totalCount = parseInt(section.getAttribute('data-total-count') || '0', 10)
    var isLoading = false

    initCategoryMenu(section)

    if (!grid || !button || !text) {
      updateCount(countNode, renderedCount, totalCount)
      return
    }

    updateButton(button, text, renderedCount < totalCount, false)
    updateCount(countNode, renderedCount, totalCount)

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

      formData.append('action', 'italika_catalog_load_more')
      formData.append('nonce', italikaCatalogData.nonce)
      formData.append('category', category)
      formData.append('offset', renderedCount)
      formData.append('limit', chunkSize)

      xhr.open('POST', italikaCatalogData.ajaxUrl, true)

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
        updateCount(countNode, renderedCount, totalCount)
        updateButton(button, text, !!response.data.hasMore, false)
      }

      xhr.send(formData)
    })
  }

  function init() {
    var sections = document.querySelectorAll('.js-catalog-page')
    var i = 0

    for (i = 0; i < sections.length; i++) {
      initCatalog(sections[i])
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init)
  } else {
    init()
  }
})()

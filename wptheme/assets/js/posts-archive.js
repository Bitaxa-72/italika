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

  function createSkeletonMarkup(type) {
    if (type === 'recipes') {
      return '' +
        '<div class="recipes-page__card recipes-page__card--skeleton">' +
        '<span class="recipes-page__media">' +
        '<span class="recipes-page__skeleton recipes-page__skeleton-image"></span>' +
        '</span>' +
        '<span class="recipes-page__body">' +
        '<span class="recipes-page__skeleton recipes-page__skeleton-meta"></span>' +
        '<span class="recipes-page__skeleton recipes-page__skeleton-title"></span>' +
        '<span class="recipes-page__skeleton recipes-page__skeleton-title recipes-page__skeleton-title--short"></span>' +
        '</span>' +
        '</div>'
    }

    return '' +
      '<div class="news-page__card news-page__card--skeleton">' +
      '<span class="news-page__media">' +
      '<span class="news-page__skeleton news-page__skeleton-image"></span>' +
      '</span>' +
      '<span class="news-page__body">' +
      '<span class="news-page__skeleton news-page__skeleton-meta"></span>' +
      '<span class="news-page__skeleton news-page__skeleton-title"></span>' +
      '<span class="news-page__skeleton news-page__skeleton-title news-page__skeleton-title--short"></span>' +
      '<span class="news-page__skeleton news-page__skeleton-text"></span>' +
      '</span>' +
      '</div>'
  }

  function appendSkeletons(grid, type, count) {
    var html = ''
    var i = 0

    for (i = 0; i < count; i++) {
      html += createSkeletonMarkup(type)
    }

    appendHtml(grid, html)
  }

  function removeSkeletons(section) {
    var skeletons = section.querySelectorAll('.news-page__card--skeleton, .recipes-page__card--skeleton')
    var i = 0

    for (i = 0; i < skeletons.length; i++) {
      skeletons[i].parentNode.removeChild(skeletons[i])
    }
  }

  function updateCategories(categoriesNode, currentCategory) {
    var buttons = categoriesNode.querySelectorAll('[data-news-archive-category], [data-recipes-archive-category]')
    var value = ''
    var i = 0

    for (i = 0; i < buttons.length; i++) {
      value = buttons[i].getAttribute('data-news-archive-category')

      if (value === null) {
        value = buttons[i].getAttribute('data-recipes-archive-category') || ''
      }

      buttons[i].classList.toggle('is-active', value === currentCategory)
      buttons[i].setAttribute('aria-pressed', value === currentCategory ? 'true' : 'false')
    }
  }

  function updateButton(section, button, actionNode, textNode, renderedCount, totalCount, isLoading) {
    var labels = window.italikaPostsArchiveData && window.italikaPostsArchiveData.labels
      ? window.italikaPostsArchiveData.labels
      : {}
    var hasMore = renderedCount < totalCount

    if (actionNode) {
      actionNode.hidden = !hasMore
    }

    if (!button || !textNode) {
      return
    }

    button.disabled = !hasMore || isLoading
    button.classList.toggle('is-loading', isLoading)
    textNode.textContent = isLoading ? (labels.loading || 'Загрузка') : (labels.more || 'Загрузить еще')
    section.setAttribute('data-rendered-count', String(renderedCount))
    section.setAttribute('data-total-count', String(totalCount))
  }

  function requestPosts(type, category, offset, limit, done) {
    var xhr = new XMLHttpRequest()
    var formData = new FormData()
    var data = window.italikaPostsArchiveData || {}

    formData.append('action', 'italika_posts_archive_load')
    formData.append('nonce', data.nonce || '')
    formData.append('archive_type', type)
    formData.append('category', category)
    formData.append('offset', offset)
    formData.append('limit', limit)

    xhr.open('POST', data.ajaxUrl || '', true)

    xhr.onreadystatechange = function () {
      var response = null

      if (xhr.readyState !== 4) {
        return
      }

      if (xhr.status >= 200 && xhr.status < 300) {
        try {
          response = JSON.parse(xhr.responseText)
        } catch (e) {
          response = null
        }
      }

      done(response && response.success ? response.data : null)
    }

    xhr.send(formData)
  }

  function splitFirstNewsCard(html) {
    var wrapper = document.createElement('div')
    var first = null
    var rest = document.createDocumentFragment()

    wrapper.innerHTML = html
    first = wrapper.firstElementChild

    if (first) {
      first.classList.add('news-page__card--featured')
    }

    while (wrapper.children.length > 1) {
      rest.appendChild(wrapper.children[1])
    }

    return {
      first: first,
      rest: rest
    }
  }

  function initArchive(section) {
    var type = section.getAttribute('data-posts-archive-type') || 'news'
    var grid = section.querySelector('.js-posts-archive-grid')
    var featured = section.querySelector('.js-posts-archive-featured')
    var categoriesNode = section.querySelector('.js-posts-archive-categories')
    var empty = section.querySelector('.js-posts-archive-empty')
    var button = section.querySelector('.js-posts-archive-more')
    var actionNode = button ? button.parentNode : null
    var textNode = button ? button.querySelector('.news-page__more-text, .recipes-page__more-text') : null
    var limit = parseInt(section.getAttribute('data-chunk-size') || '12', 10)
    var renderedCount = parseInt(section.getAttribute('data-rendered-count') || '0', 10)
    var totalCount = parseInt(section.getAttribute('data-total-count') || '0', 10)
    var currentCategory = section.getAttribute('data-posts-archive-category') || ''
    var isLoading = false

    if (!grid || !categoriesNode) {
      return
    }

    updateCategories(categoriesNode, currentCategory)
    updateButton(section, button, actionNode, textNode, renderedCount, totalCount, false)

    function load(reset) {
      var offset = reset ? 0 : renderedCount
      var skeletonCount = reset ? limit : Math.min(limit, Math.max(0, totalCount - renderedCount))

      if (isLoading || (!reset && renderedCount >= totalCount)) {
        return
      }

      isLoading = true
      updateButton(section, button, actionNode, textNode, renderedCount, totalCount, true)

      if (reset) {
        if (featured) {
          featured.innerHTML = ''
        }

        grid.innerHTML = ''
        renderedCount = 0
        totalCount = 0

        if (actionNode) {
          actionNode.hidden = true
        }
      }

      appendSkeletons(grid, type, skeletonCount || limit)

      requestPosts(type, currentCategory, offset, limit, function (data) {
        var newsParts = null

        removeSkeletons(section)
        isLoading = false

        if (!data) {
          if (empty) {
            empty.hidden = false
          }

          updateButton(section, button, actionNode, textNode, renderedCount, totalCount, false)
          return
        }

        totalCount = parseInt(data.totalCount || 0, 10)
        renderedCount = parseInt(data.nextOffset || 0, 10)

        if (type === 'news' && reset && featured) {
          newsParts = splitFirstNewsCard(data.html || '')

          if (newsParts.first) {
            featured.appendChild(newsParts.first)
          }

          grid.appendChild(newsParts.rest)
        } else if (data.html) {
          appendHtml(grid, data.html)
        }

        if (empty) {
          empty.hidden = totalCount !== 0
        }

        updateButton(section, button, actionNode, textNode, renderedCount, totalCount, false)
      })
    }

    categoriesNode.addEventListener('click', function (event) {
      var buttonNode = event.target.closest('[data-news-archive-category], [data-recipes-archive-category]')
      var value = ''

      if (!buttonNode) {
        return
      }

      value = buttonNode.getAttribute('data-news-archive-category')

      if (value === null) {
        value = buttonNode.getAttribute('data-recipes-archive-category') || ''
      }

      currentCategory = value
      section.setAttribute('data-posts-archive-category', currentCategory)
      updateCategories(categoriesNode, currentCategory)
      load(true)
    })

    if (button) {
      button.addEventListener('click', function () {
        load(false)
      })
    }
  }

  function init() {
    var sections = document.querySelectorAll('.js-posts-archive')
    var i = 0

    for (i = 0; i < sections.length; i++) {
      initArchive(sections[i])
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init)
  } else {
    init()
  }
})()

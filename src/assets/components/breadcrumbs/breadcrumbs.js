var labels = {
  'about.html': 'О компании',
  'card.html': 'Корзина',
  'checkout.html': 'Оформление заказа',
  'contacts.html': 'Контакты',
  'favorites.html': 'Избранное',
  'lkmain.html': 'Личный кабинет',
  'lkreg.html': 'Вход и регистрация',
  'search.html': 'Поиск'
}

function initBreadcrumbs() {
  var root = document.querySelector('[data-breadcrumbs]')

  if (!root) {
    return
  }

  var current = root.querySelector(
    '[data-breadcrumbs-current]'
  )
  var page = window.location.pathname
    .split('/')
    .pop()
    .toLowerCase()
  var label = labels[page]

  if (!label) {
    root.hidden = true
    return
  }

  current.textContent = label
}

initBreadcrumbs()

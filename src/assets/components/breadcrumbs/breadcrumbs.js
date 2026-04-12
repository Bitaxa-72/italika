var labels = {
  'news.html': 'Новости',
  'news-single.html': 'Новость',
  'offer.html': 'Публичная оферта',
  'privacy.html': 'Политика конфиденциальности',
  'recipe-single.html': 'Рецепт',
  'recipes.html': 'Рецепты',
  'about.html': 'О компании',
  'card.html': 'Корзина',
  'checkout.html': 'Оформление заказа',
  'contacts.html': 'Контакты',
  'delivery.html': 'Доставка и оплата',
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

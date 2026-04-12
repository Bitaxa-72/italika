const header = document.querySelector('.header')
const navToggle = header?.querySelector('.nav-toggle')
const nav = header?.querySelector('.nav')
const searchField = header?.querySelector(
  '.searchbar__field'
)
const compactNavQuery = window.matchMedia(
  '(width <= 980px)'
)
const mobilePlaceholderQuery = window.matchMedia(
  '(width <= 520px)'
)

const updateHeaderMetrics = () => {
  if (!header) {
    return
  }

  document.documentElement.style.setProperty(
    '--header-height',
    `${Math.ceil(header.getBoundingClientRect().height)}px`
  )
}

const setNavState = (isOpen) => {
  if (!header || !navToggle || !nav) {
    return
  }

  const isClosedCompactNav =
    compactNavQuery.matches && !isOpen

  header.classList.toggle('is-nav-open', isOpen)
  navToggle.setAttribute('aria-expanded', String(isOpen))
  navToggle.setAttribute(
    'aria-label',
    isOpen ? 'Закрыть меню' : 'Открыть меню'
  )
  nav.toggleAttribute('inert', isClosedCompactNav)
  nav.setAttribute(
    'aria-hidden',
    String(isClosedCompactNav)
  )
  window.requestAnimationFrame(updateHeaderMetrics)
}

navToggle?.addEventListener('click', () => {
  setNavState(!header.classList.contains('is-nav-open'))
})

document.addEventListener('click', (event) => {
  if (
    compactNavQuery.matches &&
    header?.classList.contains('is-nav-open') &&
    !event.target.closest('.header')
  ) {
    setNavState(false)
  }
})

nav?.addEventListener('click', (event) => {
  if (
    event.target.closest('a') &&
    compactNavQuery.matches
  ) {
    setNavState(false)
  }
})

document.addEventListener('keydown', (event) => {
  if (
    event.key === 'Escape' &&
    header?.classList.contains('is-nav-open')
  ) {
    setNavState(false)
    navToggle?.focus()
  }
})

compactNavQuery.addEventListener('change', (event) => {
  if (!event.matches) {
    setNavState(false)
    return
  }

  setNavState(
    header?.classList.contains('is-nav-open') ?? false
  )
})

if (header && navToggle && nav) {
  setNavState(header.classList.contains('is-nav-open'))
}

if (header) {
  updateHeaderMetrics()
  window.addEventListener('resize', updateHeaderMetrics, {
    passive: true
  })
  window.addEventListener('load', updateHeaderMetrics, {
    passive: true
  })
}

const updateSearchPlaceholder = () => {
  if (!searchField) {
    return
  }

  if (mobilePlaceholderQuery.matches) {
    searchField.placeholder =
      searchField.dataset.placeholderMobile ||
      searchField.dataset.placeholderFull ||
      ''
    return
  }

  if (compactNavQuery.matches) {
    searchField.placeholder =
      searchField.dataset.placeholderTablet ||
      searchField.dataset.placeholderFull ||
      ''
    return
  }

  searchField.placeholder =
    searchField.dataset.placeholderFull || ''
}

compactNavQuery.addEventListener(
  'change',
  updateSearchPlaceholder
)
mobilePlaceholderQuery.addEventListener(
  'change',
  updateSearchPlaceholder
)
updateSearchPlaceholder()

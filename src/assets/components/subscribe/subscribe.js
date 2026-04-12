const subscribeModal = document.querySelector(
  '[data-subscribe-modal]'
)
const subscribeOpenButtons = document.querySelectorAll(
  '[data-subscribe-open]'
)
const subscribeCloseButtons =
  subscribeModal?.querySelectorAll('[data-subscribe-close]')
const subscribeEmailInput = subscribeModal?.querySelector(
  '.subscribe-modal__input'
)
let subscribeLastActiveElement = null

const setSubscribeModalState = (isOpen) => {
  if (!subscribeModal) {
    return
  }

  subscribeModal.classList.toggle('is-open', isOpen)
  subscribeModal.setAttribute(
    'aria-hidden',
    String(!isOpen)
  )
  document.body.style.overflow = isOpen ? 'hidden' : ''

  if (isOpen) {
    subscribeEmailInput?.focus()
    return
  }

  subscribeLastActiveElement?.focus()
}

subscribeOpenButtons.forEach((button) => {
  button.addEventListener('click', () => {
    subscribeLastActiveElement = button
    setSubscribeModalState(true)
  })
})

subscribeCloseButtons?.forEach((button) => {
  button.addEventListener('click', () => {
    setSubscribeModalState(false)
  })
})

document.addEventListener('keydown', (event) => {
  if (
    event.key === 'Escape' &&
    subscribeModal?.classList.contains('is-open')
  ) {
    setSubscribeModalState(false)
  }
})

import Swiper from 'swiper'
import { Navigation } from 'swiper/modules'

document.addEventListener('DOMContentLoaded', function () {
  var slider = document.querySelector('.js-recipe-cats-slider')

  if (!slider) {
    return
  }

  var section = slider.closest('.recipe-cats')
  var root = section || document

  new Swiper(slider, {
    modules: [Navigation],
    speed: 650,
    spaceBetween: 18,
    slidesPerView: 1.12,
    grabCursor: true,
    watchOverflow: true,
    navigation: {
      prevEl: root.querySelector('.js-recipe-cats-prev'),
      nextEl: root.querySelector('.js-recipe-cats-next')
    },
    breakpoints: {
      768: {
        slidesPerView: 2.2
      },
      981: {
        slidesPerView: 3
      },
      1200: {
        slidesPerView: 4
      }
    }
  })
})

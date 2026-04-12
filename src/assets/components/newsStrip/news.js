import Swiper from 'swiper'
import { Navigation } from 'swiper/modules'

document.addEventListener('DOMContentLoaded', function () {
  var slider = document.querySelector(
    '.js-news-strip-slider'
  )

  if (!slider) {
    return
  }

  var section = slider.closest('.news-strip')
  var root = section || document

  new Swiper(slider, {
    modules: [Navigation],
    speed: 650,
    spaceBetween: 18,
    slidesPerView: 1.15,
    grabCursor: true,
    watchOverflow: true,
    navigation: {
      prevEl: root.querySelector('.js-news-strip-prev'),
      nextEl: root.querySelector('.js-news-strip-next')
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
      },
      1360: {
        slidesPerView: 5
      }
    }
  })
})

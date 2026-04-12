import Swiper from 'swiper'
import {
  Autoplay,
  Navigation,
  Pagination
} from 'swiper/modules'

document.addEventListener('DOMContentLoaded', function () {
  var heroSlider = document.querySelector('.js-hero-slider')

  if (!heroSlider) {
    return
  }

  new Swiper(heroSlider, {
    modules: [Autoplay, Navigation, Pagination],
    loop: true,
    speed: 700,
    spaceBetween: 0,
    autoplay: {
      delay: 5000,
      disableOnInteraction: false,
      pauseOnMouseEnter: true
    },
    pagination: {
      el: heroSlider.querySelector('.hero__pagination'),
      clickable: true
    },
    navigation: {
      prevEl: heroSlider.querySelector(
        '.hero__arrow--prev'
      ),
      nextEl: heroSlider.querySelector('.hero__arrow--next')
    }
  })
})

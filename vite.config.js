import { defineConfig } from 'vite'
import injectHTML from 'vite-plugin-html-inject'

export default defineConfig({
  base:
    process.env.NODE_ENV === 'production'
      ? '/italika/'
      : '/',
  server: {
    port: 3030
  },
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        main: '/index.html',
        about: '/about.html',
        card: '/card.html',
        catalog: '/catalog.html',
        checkout: '/checkout.html',
        contacts: '/contacts.html',
        delivery: '/delivery.html',
        favorites: '/favorites.html',
        lkMain: '/lkMain.html',
        lkReg: '/lkReg.html',
        news: '/news.html',
        newsSingle: '/news-single.html',
        offer: '/offer.html',
        privacy: '/privacy.html',
        product: '/product.html',
        recipes: '/recipes.html',
        recipeSingle: '/recipe-single.html',
        search: '/search.html',
        template: '/template.html'
      }
    }
  },
  plugins: [injectHTML()]
})

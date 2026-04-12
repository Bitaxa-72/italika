import { defineConfig } from 'vite'
import injectHTML from 'vite-plugin-html-inject'

export default defineConfig({
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
        checkout: '/checkout.html',
        contacts: '/contacts.html',
        favorites: '/favorites.html',
        lkMain: '/lkMain.html',
        lkReg: '/lkReg.html',
        search: '/search.html'
      }
    }
  },
  plugins: [injectHTML()]
})

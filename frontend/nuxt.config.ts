import tailwindcss from '@tailwindcss/vite'

export default defineNuxtConfig({
  compatibilityDate: '2026-10-09',
  ssr: false,
  devtools: { enabled: false },
  css: ['~/assets/css/main.css'],
  components: [{ path: '~/components', pathPrefix: false }],
  vite: {
    plugins: [tailwindcss()],
    server: {
      watch: {
        ignored: ['**/test-results/**', '**/playwright-report/**'],
      },
    },
  },
  // Bundle Nuxt's renderer so its build-time modules resolve in Windows production builds.
  nitro: { externals: { inline: ['nuxt'] } },
  runtimeConfig: {
    laravelBaseUrl: 'http://127.0.0.1:8000',
    // true hanya bila Nuxt berada di belakang reverse proxy (mis. nginx) yang MENIMPA X-Forwarded-For
    // dengan IP klien ($remote_addr). Bawaan false: IP diambil dari koneksi langsung.
    trustForwardedFor: false,
  },
  app: {
    head: {
      htmlAttrs: { lang: 'id' },
      title: 'Gojags Asrama',
      script: [{ src: '/js/theme.js', tagPosition: 'head' }],
      link: [
        { rel: 'icon', href: '/images/gojags.png' },
      ],
    },
  },
})

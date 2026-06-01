export default defineNuxtConfig({
  devtools: { enabled: true },

  modules: [
    '@nuxtjs/tailwindcss',
    '@pinia/nuxt',
    '@nuxtjs/google-fonts',
    '@nuxt/icon',
    '@nuxt/image',
  ],

  runtimeConfig: {
    public: {
      apiUrl: process.env.NUXT_PUBLIC_API_URL || 'http://localhost:8000/api',
      appUrl: process.env.NUXT_PUBLIC_APP_URL || 'http://localhost:3000',
      stripeKey: process.env.NUXT_STRIPE_KEY || '',
    },
  },

  tailwindcss: {
    config: {
      theme: {
        extend: {
          colors: {
            primary: {
              50: '#fdf8f0',
              100: '#f9eddb',
              200: '#f2d7b0',
              300: '#e9bb7c',
              400: '#df9a46',
              500: '#d68222',
              600: '#c46a18',
              700: '#a35116',
              800: '#844119',
              900: '#6c3618',
            },
            canvas: {
              50: '#fafaf7',
              100: '#f5f4ed',
              200: '#e8e5d8',
              300: '#d6d0bb',
              400: '#c1b799',
              500: '#b0a480',
              600: '#9a8d6a',
              700: '#817457',
              800: '#6a5f4a',
              900: '#574f3f',
            },
          },
          fontFamily: {
            serif: ['Playfair Display', 'Georgia', 'serif'],
            sans: ['Inter', 'system-ui', 'sans-serif'],
          },
        },
      },
    },
  },

  googleFonts: {
    families: {
      'Playfair Display': [400, 500, 600, 700],
      'Inter': [300, 400, 500, 600, 700],
    },
  },

  compatibilityDate: '2025-01-01',
})

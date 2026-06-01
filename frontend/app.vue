<template>
  <NuxtLayout>
    <NuxtPage />
  </NuxtLayout>
</template>

<script setup lang="ts">
useHead({
  titleTemplate: (title) => title ? `${title} — Atelier` : 'Atelier — Professional Platform for Painters',
  htmlAttrs: { lang: 'en' },
  meta: [
    { name: 'description', content: 'Atelier is a professional platform for painters to showcase artwork, sell paintings, and share tutorials with collectors worldwide.' },
    { property: 'og:title', content: 'Atelier — Professional Platform for Painters' },
    { property: 'og:description', content: 'Discover original paintings, learn from master painters, and build your art collection.' },
    { property: 'og:type', content: 'website' },
    { property: 'og:site_name', content: 'Atelier' },
    { name: 'twitter:card', content: 'summary_large_image' },
    { name: 'theme-color', content: '#d68222' },
  ],
  link: [
    { rel: 'icon', type: 'image/x-icon', href: '/favicon.ico' },
  ],
})
</script>

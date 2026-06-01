# Skill 04: Landing Page

## Overview
Build a professional, conversion-focused landing page for Atelier with hero section, features, featured painters, and Grisaille tool teaser.

---

## Step 1: Layout Component

### `components/layout/Navbar.vue`

```vue
<template>
  <nav class="fixed top-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-md border-b border-canvas-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        <!-- Logo -->
        <NuxtLink to="/" class="flex items-center gap-2">
          <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
            <span class="text-white font-serif font-bold text-lg">A</span>
          </div>
          <span class="text-xl font-serif font-bold text-canvas-900">Atelier</span>
        </NuxtLink>

        <!-- Desktop Nav -->
        <div class="hidden md:flex items-center gap-8">
          <NuxtLink to="/grisaille" class="text-canvas-600 hover:text-primary-600 transition font-medium">
            Grisaille Tool
          </NuxtLink>
          <NuxtLink to="/explore" class="text-canvas-600 hover:text-primary-600 transition font-medium">
            Explore
          </NuxtLink>
          <NuxtLink to="/shop" class="text-canvas-600 hover:text-primary-600 transition font-medium">
            Shop
          </NuxtLink>
        </div>

        <!-- Auth Buttons -->
        <div class="flex items-center gap-4">
          <template v-if="isAuthenticated">
            <NuxtLink
              :to="isPainter ? '/dashboard' : '/explore'"
              class="px-4 py-2 text-canvas-700 hover:text-primary-600 transition font-medium"
            >
              Dashboard
            </NuxtLink>
            <button
              @click="logout"
              class="px-4 py-2 text-canvas-600 hover:text-canvas-800 transition"
            >
              Logout
            </button>
          </template>
          <template v-else>
            <NuxtLink
              to="/login"
              class="px-4 py-2 text-canvas-700 hover:text-primary-600 transition font-medium"
            >
              Sign In
            </NuxtLink>
            <NuxtLink
              to="/register"
              class="px-5 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition"
            >
              Get Started
            </NuxtLink>
          </template>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
const { isAuthenticated, isPainter, logout } = useAuth()
</script>
```

### `components/layout/Footer.vue`

```vue
<template>
  <footer class="bg-canvas-900 text-canvas-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
        <!-- Brand -->
        <div class="md:col-span-1">
          <div class="flex items-center gap-2 mb-4">
            <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
              <span class="text-white font-serif font-bold text-lg">A</span>
            </div>
            <span class="text-xl font-serif font-bold text-white">Atelier</span>
          </div>
          <p class="text-canvas-400 text-sm leading-relaxed">
            The professional platform for painters to showcase, sell, and share their craft with the world.
          </p>
        </div>

        <!-- Platform -->
        <div>
          <h4 class="text-white font-semibold mb-4">Platform</h4>
          <ul class="space-y-2 text-sm">
            <li><NuxtLink to="/explore" class="hover:text-primary-400 transition">Explore Painters</NuxtLink></li>
            <li><NuxtLink to="/shop" class="hover:text-primary-400 transition">Video Shop</NuxtLink></li>
            <li><NuxtLink to="/grisaille" class="hover:text-primary-400 transition">Grisaille Tool</NuxtLink></li>
            <li><NuxtLink to="/register" class="hover:text-primary-400 transition">Start Selling</NuxtLink></li>
          </ul>
        </div>

        <!-- For Painters -->
        <div>
          <h4 class="text-white font-semibold mb-4">For Painters</h4>
          <ul class="space-y-2 text-sm">
            <li><NuxtLink to="/register" class="hover:text-primary-400 transition">Create Account</NuxtLink></li>
            <li><NuxtLink to="/dashboard" class="hover:text-primary-400 transition">Dashboard</NuxtLink></li>
            <li><NuxtLink to="/pricing" class="hover:text-primary-400 transition">Pricing</NuxtLink></li>
            <li><NuxtLink to="/help" class="hover:text-primary-400 transition">Help Center</NuxtLink></li>
          </ul>
        </div>

        <!-- Legal -->
        <div>
          <h4 class="text-white font-semibold mb-4">Legal</h4>
          <ul class="space-y-2 text-sm">
            <li><NuxtLink to="/privacy" class="hover:text-primary-400 transition">Privacy Policy</NuxtLink></li>
            <li><NuxtLink to="/terms" class="hover:text-primary-400 transition">Terms of Service</NuxtLink></li>
            <li><NuxtLink to="/cookies" class="hover:text-primary-400 transition">Cookie Policy</NuxtLink></li>
          </ul>
        </div>
      </div>

      <div class="border-t border-canvas-800 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
        <p class="text-canvas-500 text-sm">
          &copy; {{ new Date().getFullYear() }} Atelier. All rights reserved.
        </p>
        <div class="flex items-center gap-6">
          <a href="#" class="text-canvas-500 hover:text-primary-400 transition">
            <Icon name="mdi:instagram" class="w-5 h-5" />
          </a>
          <a href="#" class="text-canvas-500 hover:text-primary-400 transition">
            <Icon name="mdi:twitter" class="w-5 h-5" />
          </a>
          <a href="#" class="text-canvas-500 hover:text-primary-400 transition">
            <Icon name="mdi:youtube" class="w-5 h-5" />
          </a>
        </div>
      </div>
    </div>
  </footer>
</template>
```

---

## Step 2: Landing Page

### `pages/index.vue`

```vue
<template>
  <div>
    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center bg-gradient-to-br from-canvas-50 via-white to-primary-50 pt-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
          <!-- Left: Copy -->
          <div>
            <h1 class="text-5xl lg:text-6xl font-serif font-bold text-canvas-900 leading-tight">
              Your Art.<br />
              <span class="text-primary-600">Your Stage.</span>
            </h1>
            <p class="mt-6 text-xl text-canvas-600 leading-relaxed max-w-lg">
              The professional platform built exclusively for painters. Showcase your gallery,
              sell your work, share your technique, and grow your art business.
            </p>
            <div class="mt-10 flex flex-wrap gap-4">
              <NuxtLink
                to="/register"
                class="px-8 py-4 bg-primary-600 text-white rounded-xl font-semibold text-lg hover:bg-primary-700 transition shadow-lg shadow-primary-600/25"
              >
                Start as a Painter
              </NuxtLink>
              <NuxtLink
                to="/explore"
                class="px-8 py-4 bg-white text-canvas-700 rounded-xl font-semibold text-lg border-2 border-canvas-200 hover:border-primary-300 transition"
              >
                Explore Artists
              </NuxtLink>
            </div>
            <p class="mt-4 text-sm text-canvas-500">
              Free to join. No credit card required.
            </p>
          </div>

          <!-- Right: Hero Image Grid -->
          <div class="relative">
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-4">
                <div class="rounded-2xl overflow-hidden shadow-xl aspect-[3/4] bg-canvas-200">
                  <img
                    src="/images/hero-1.jpg"
                    alt="Oil painting"
                    class="w-full h-full object-cover"
                    loading="lazy"
                  />
                </div>
                <div class="rounded-2xl overflow-hidden shadow-xl aspect-square bg-canvas-200">
                  <img
                    src="/images/hero-2.jpg"
                    alt="Watercolor painting"
                    class="w-full h-full object-cover"
                    loading="lazy"
                  />
                </div>
              </div>
              <div class="space-y-4 mt-8">
                <div class="rounded-2xl overflow-hidden shadow-xl aspect-square bg-canvas-200">
                  <img
                    src="/images/hero-3.jpg"
                    alt="Acrylic painting"
                    class="w-full h-full object-cover"
                    loading="lazy"
                  />
                </div>
                <div class="rounded-2xl overflow-hidden shadow-xl aspect-[3/4] bg-canvas-200">
                  <img
                    src="/images/hero-4.jpg"
                    alt="Portrait painting"
                    class="w-full h-full object-cover"
                    loading="lazy"
                  />
                </div>
              </div>
            </div>
            <!-- Floating stats -->
            <div class="absolute -bottom-6 -left-6 bg-white rounded-2xl shadow-2xl p-6 flex items-center gap-4">
              <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center">
                <Icon name="mdi:palette" class="w-6 h-6 text-primary-600" />
              </div>
              <div>
                <p class="text-2xl font-bold text-canvas-900">2,400+</p>
                <p class="text-sm text-canvas-500">Painters Worldwide</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="py-24 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
          <h2 class="text-4xl font-serif font-bold text-canvas-900">
            Everything Painters Need
          </h2>
          <p class="mt-4 text-xl text-canvas-600">
            A complete platform designed around the art of painting
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <!-- Feature 1 -->
          <div class="p-8 rounded-2xl bg-canvas-50 hover:bg-primary-50 transition group">
            <div class="w-14 h-14 bg-primary-100 rounded-xl flex items-center justify-center mb-6 group-hover:bg-primary-200 transition">
              <Icon name="mdi:image-multiple" class="w-7 h-7 text-primary-600" />
            </div>
            <h3 class="text-xl font-semibold text-canvas-900 mb-3">Your Gallery</h3>
            <p class="text-canvas-600 leading-relaxed">
              Create a stunning online gallery for your paintings. Upload high-res images, add details, set prices, and share your portfolio with collectors worldwide.
            </p>
          </div>

          <!-- Feature 2 -->
          <div class="p-8 rounded-2xl bg-canvas-50 hover:bg-primary-50 transition group">
            <div class="w-14 h-14 bg-primary-100 rounded-xl flex items-center justify-center mb-6 group-hover:bg-primary-200 transition">
              <Icon name="mdi:video" class="w-7 h-7 text-primary-600" />
            </div>
            <h3 class="text-xl font-semibold text-canvas-900 mb-3">Video Shop</h3>
            <p class="text-canvas-600 leading-relaxed">
              Sell technique videos, tutorials, and process demonstrations. Share your knowledge and earn passive income from your expertise.
            </p>
          </div>

          <!-- Feature 3 -->
          <div class="p-8 rounded-2xl bg-canvas-50 hover:bg-primary-50 transition group">
            <div class="w-14 h-14 bg-primary-100 rounded-xl flex items-center justify-center mb-6 group-hover:bg-primary-200 transition">
              <Icon name="mdi:currency-usd" class="w-7 h-7 text-primary-600" />
            </div>
            <h3 class="text-xl font-semibold text-canvas-900 mb-3">Sell Your Work</h3>
            <p class="text-canvas-600 leading-relaxed">
              Accept payments directly through Stripe. Set your prices, manage orders, and track earnings all in one place.
            </p>
          </div>

          <!-- Feature 4 -->
          <div class="p-8 rounded-2xl bg-canvas-50 hover:bg-primary-50 transition group">
            <div class="w-14 h-14 bg-primary-100 rounded-xl flex items-center justify-center mb-6 group-hover:bg-primary-200 transition">
              <Icon name="mdi:chart-bar" class="w-7 h-7 text-primary-600" />
            </div>
            <h3 class="text-xl font-semibold text-canvas-900 mb-3">Grisaille Tool</h3>
            <p class="text-canvas-600 leading-relaxed">
              Upload any photo and get instant value analysis with 0-9 grayscale mapping, palette recommendations, and glazing suggestions.
            </p>
          </div>

          <!-- Feature 5 -->
          <div class="p-8 rounded-2xl bg-canvas-50 hover:bg-primary-50 transition group">
            <div class="w-14 h-14 bg-primary-100 rounded-xl flex items-center justify-center mb-6 group-hover:bg-primary-200 transition">
              <Icon name="mdi:hammer-wrench" class="w-7 h-7 text-primary-600" />
            </div>
            <h3 class="text-xl font-semibold text-canvas-900 mb-3">Project Tracking</h3>
            <p class="text-canvas-600 leading-relaxed">
              Share your works-in-progress. Document planned, in-progress, and completed projects to engage your audience.
            </p>
          </div>

          <!-- Feature 6 -->
          <div class="p-8 rounded-2xl bg-canvas-50 hover:bg-primary-50 transition group">
            <div class="w-14 h-14 bg-primary-100 rounded-xl flex items-center justify-center mb-6 group-hover:bg-primary-200 transition">
              <Icon name="mdi:web" class="w-7 h-7 text-primary-600" />
            </div>
            <h3 class="text-xl font-semibold text-canvas-900 mb-3">Your Own Site</h3>
            <p class="text-canvas-600 leading-relaxed">
              Get a dedicated URL at atelier.com/your-name. A professional home for your art that you control.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Featured Painters Section -->
    <section class="py-24 bg-canvas-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between mb-12">
          <div>
            <h2 class="text-4xl font-serif font-bold text-canvas-900">Featured Artists</h2>
            <p class="mt-2 text-xl text-canvas-600">Discover exceptional painters on Atelier</p>
          </div>
          <NuxtLink
            to="/explore"
            class="hidden md:flex items-center gap-2 text-primary-600 font-medium hover:text-primary-700 transition"
          >
            View All
            <Icon name="mdi:arrow-right" class="w-5 h-5" />
          </NuxtLink>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div
            v-for="painter in featuredPainters"
            :key="painter.id"
            class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition group"
          >
            <NuxtLink :to="`/painter/${painter.slug}`">
              <div class="aspect-[4/5] bg-canvas-200 overflow-hidden">
                <img
                  :src="painter.featured_image || '/images/default-avatar.jpg'"
                  :alt="painter.name"
                  class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                  loading="lazy"
                />
              </div>
              <div class="p-6">
                <h3 class="font-semibold text-canvas-900 text-lg">{{ painter.name }}</h3>
                <p class="text-canvas-500 text-sm mt-1">{{ painter.specialties?.join(', ') || 'Oil Painting' }}</p>
                <p class="text-canvas-400 text-xs mt-2">{{ painter.location || 'Worldwide' }}</p>
              </div>
            </NuxtLink>
          </div>
        </div>
      </div>
    </section>

    <!-- Grisaille Tool CTA -->
    <section class="py-24 bg-gradient-to-br from-canvas-900 to-canvas-800 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
          <div>
            <h2 class="text-4xl font-serif font-bold leading-tight">
              Master Values with the<br />
              <span class="text-primary-400">Grisaille Tool</span>
            </h2>
            <p class="mt-6 text-xl text-canvas-300 leading-relaxed">
              Upload any reference photo and instantly get a 10-value grayscale analysis.
              Receive personalized palette recommendations and glazing suggestions for
              your paintings.
            </p>
            <ul class="mt-8 space-y-4">
              <li class="flex items-center gap-3">
                <Icon name="mdi:check-circle" class="w-6 h-6 text-primary-400 flex-shrink-0" />
                <span class="text-canvas-300">Instant 0-9 value mapping with histogram</span>
              </li>
              <li class="flex items-center gap-3">
                <Icon name="mdi:check-circle" class="w-6 h-6 text-primary-400 flex-shrink-0" />
                <span class="text-canvas-300">Color palette recommendations for each value range</span>
              </li>
              <li class="flex items-center gap-3">
                <Icon name="mdi:check-circle" class="w-6 h-6 text-primary-400 flex-shrink-0" />
                <span class="text-canvas-300">Glazing technique suggestions for smooth transitions</span>
              </li>
              <li class="flex items-center gap-3">
                <Icon name="mdi:check-circle" class="w-6 h-6 text-primary-400 flex-shrink-0" />
                <span class="text-canvas-300">Download printable PDF for your studio</span>
              </li>
            </ul>
            <NuxtLink
              to="/grisaille"
              class="inline-flex items-center gap-2 mt-10 px-8 py-4 bg-primary-600 text-white rounded-xl font-semibold text-lg hover:bg-primary-700 transition"
            >
              Try the Grisaille Tool
              <Icon name="mdi:arrow-right" class="w-5 h-5" />
            </NuxtLink>
          </div>

          <!-- Tool Preview -->
          <div class="relative">
            <div class="bg-white rounded-2xl shadow-2xl p-6">
              <div class="aspect-square bg-canvas-100 rounded-xl overflow-hidden mb-4">
                <img
                  src="/images/grisaille-preview.jpg"
                  alt="Grisaille tool preview"
                  class="w-full h-full object-cover"
                  loading="lazy"
                />
              </div>
              <div class="flex gap-1 h-8">
                <div v-for="i in 10" :key="i" class="flex-1 rounded" :style="{ background: `rgb(${(i-1)*28}, ${(i-1)*28}, ${(i-1)*28})` }" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials -->
    <section class="py-24 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
          <h2 class="text-4xl font-serif font-bold text-canvas-900">Loved by Painters</h2>
          <p class="mt-4 text-xl text-canvas-600">
            Hear from artists who have transformed their practice with Atelier
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div
            v-for="(testimonial, index) in testimonials"
            :key="index"
            class="p-8 rounded-2xl bg-canvas-50 border border-canvas-100"
          >
            <div class="flex items-center gap-1 mb-4">
              <Icon v-for="i in 5" :key="i" name="mdi:star" class="w-5 h-5 text-primary-500" />
            </div>
            <p class="text-canvas-700 leading-relaxed mb-6">
              "{{ testimonial.quote }}"
            </p>
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-full bg-canvas-200 overflow-hidden">
                <img
                  :src="testimonial.avatar"
                  :alt="testimonial.name"
                  class="w-full h-full object-cover"
                  loading="lazy"
                />
              </div>
              <div>
                <p class="font-semibold text-canvas-900">{{ testimonial.name }}</p>
                <p class="text-sm text-canvas-500">{{ testimonial.title }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Final CTA -->
    <section class="py-24 bg-primary-600">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl lg:text-5xl font-serif font-bold text-white leading-tight">
          Ready to Share Your Art with the World?
        </h2>
        <p class="mt-6 text-xl text-primary-100 max-w-2xl mx-auto">
          Join thousands of painters who are building their careers on Atelier.
          Create your free account today.
        </p>
        <div class="mt-10 flex flex-wrap justify-center gap-4">
          <NuxtLink
            to="/register"
            class="px-8 py-4 bg-white text-primary-700 rounded-xl font-semibold text-lg hover:bg-primary-50 transition shadow-lg"
          >
            Create Free Account
          </NuxtLink>
          <NuxtLink
            to="/explore"
            class="px-8 py-4 bg-primary-700 text-white rounded-xl font-semibold text-lg hover:bg-primary-800 transition border border-primary-500"
          >
            Browse Artists
          </NuxtLink>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'default' })

// Fetch featured painters
const config = useRuntimeConfig()
const { data: paintersData } = await useFetch<{ painters: any[] }>(
  `${config.public.apiUrl}/painters?featured=true&limit=4`
)

const featuredPainters = computed(() => paintersData.value?.painters || [])

const testimonials = [
  {
    quote: 'Atelier transformed how I sell my paintings. The gallery is beautiful and the payment system is seamless.',
    name: 'Sarah Chen',
    title: 'Oil Painter, California',
    avatar: '/images/testimonial-1.jpg',
  },
  {
    quote: 'The Grisaille tool alone is worth it. I use it daily to plan my value structures before painting.',
    name: 'Marcus Rivera',
    title: 'Portrait Artist, New York',
    avatar: '/images/testimonial-2.jpg',
  },
  {
    quote: 'I sell technique videos alongside my paintings. It\'s become a significant income stream for me.',
    name: 'Elena Volkov',
    title: 'Watercolorist, London',
    avatar: '/images/testimonial-3.jpg',
  },
]
</script>
```

---

## Step 3: Default Layout

### `layouts/default.vue`

```vue
<template>
  <div class="min-h-screen flex flex-col">
    <LayoutNavbar />
    <main class="flex-1">
      <slot />
    </main>
    <LayoutFooter />
  </div>
</template>
```

---

## Step 4: App Entry

### `app.vue`

```vue
<template>
  <NuxtLayout>
    <NuxtPage />
  </NuxtLayout>
</template>
```

---

## Step 5: Global CSS

### `assets/css/main.css`

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

@layer base {
  body {
    @apply font-sans text-canvas-800 antialiased;
  }

  h1, h2, h3, h4, h5, h6 {
    @apply font-serif;
  }
}

@layer components {
  .btn-primary {
    @apply px-6 py-3 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition shadow-sm;
  }

  .btn-secondary {
    @apply px-6 py-3 bg-white text-canvas-700 rounded-lg font-medium border border-canvas-300 hover:border-primary-300 transition;
  }

  .card {
    @apply bg-white rounded-2xl shadow-sm border border-canvas-100 overflow-hidden;
  }

  .input {
    @apply w-full px-4 py-3 border border-canvas-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition;
  }
}
```

---

## Verification Checklist

- [ ] Navbar renders with auth state
- [ ] Hero section is responsive
- [ ] Features grid displays correctly
- [ ] Featured painters load from API
- [ ] Grisaille CTA section renders
- [ ] Testimonials display
- [ ] Footer links work
- [ ] Mobile responsive on all sections

---

## Next Steps

Proceed to [Skill 05: Grisaille Tool](./05-grisaille-tool.md) for the unique value analysis feature.

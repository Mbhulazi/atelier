<template>
  <div>
    <!-- Welcome Banner -->
    <div v-if="isAuthenticated" class="bg-primary-50 border-b border-primary-100 pt-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
              <span class="text-sm font-serif font-bold text-primary-600">{{ user?.name?.charAt(0) }}</span>
            </div>
            <div>
              <p class="font-medium text-canvas-900">Welcome back, {{ user?.name }}!</p>
              <p class="text-sm text-canvas-500">Ready to create something beautiful?</p>
            </div>
          </div>
          <NuxtLink
            to="/dashboard"
            class="px-5 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-xl transition"
          >
            Go to Dashboard
          </NuxtLink>
        </div>
      </div>
    </div>

    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center bg-gradient-to-br from-canvas-50 via-white to-primary-50 pt-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
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
                v-if="!isAuthenticated"
                to="/register"
                class="px-8 py-4 bg-primary-600 text-white rounded-xl font-semibold text-lg hover:bg-primary-700 transition shadow-lg shadow-primary-600/25"
              >
                Start as a Painter
              </NuxtLink>
              <NuxtLink
                v-else
                :to="isPainter ? '/dashboard/paintings/create' : '/gallery'"
                class="px-8 py-4 bg-primary-600 text-white rounded-xl font-semibold text-lg hover:bg-primary-700 transition shadow-lg shadow-primary-600/25"
              >
                {{ isPainter ? 'Upload a Painting' : 'Browse Gallery' }}
              </NuxtLink>
              <NuxtLink
                to="/gallery"
                class="px-8 py-4 bg-white text-canvas-700 rounded-xl font-semibold text-lg border-2 border-canvas-200 hover:border-primary-300 transition"
              >
                Explore Artists
              </NuxtLink>
            </div>
            <p v-if="!isAuthenticated" class="mt-4 text-sm text-canvas-500">
              Free to join. No credit card required.
            </p>
          </div>

          <div class="relative">
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-4">
                <div class="rounded-2xl overflow-hidden shadow-xl aspect-[3/4] bg-canvas-200 flex items-center justify-center">
                  <Icon name="mdi:image" class="w-16 h-16 text-canvas-400" />
                </div>
                <div class="rounded-2xl overflow-hidden shadow-xl aspect-square bg-canvas-200 flex items-center justify-center">
                  <Icon name="mdi:palette" class="w-12 h-12 text-canvas-400" />
                </div>
              </div>
              <div class="space-y-4 mt-8">
                <div class="rounded-2xl overflow-hidden shadow-xl aspect-square bg-canvas-200 flex items-center justify-center">
                  <Icon name="mdi:brush" class="w-12 h-12 text-canvas-400" />
                </div>
                <div class="rounded-2xl overflow-hidden shadow-xl aspect-[3/4] bg-canvas-200 flex items-center justify-center">
                  <Icon name="mdi:image-multiple" class="w-16 h-16 text-canvas-400" />
                </div>
              </div>
            </div>
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
          <div class="p-8 rounded-2xl bg-canvas-50 hover:bg-primary-50 transition group">
            <div class="w-14 h-14 bg-primary-100 rounded-xl flex items-center justify-center mb-6 group-hover:bg-primary-200 transition">
              <Icon name="mdi:image-multiple" class="w-7 h-7 text-primary-600" />
            </div>
            <h3 class="text-xl font-semibold text-canvas-900 mb-3">Your Gallery</h3>
            <p class="text-canvas-600 leading-relaxed">
              Create a stunning online gallery for your paintings. Upload high-res images, add details, set prices, and share your portfolio with collectors worldwide.
            </p>
          </div>

          <div class="p-8 rounded-2xl bg-canvas-50 hover:bg-primary-50 transition group">
            <div class="w-14 h-14 bg-primary-100 rounded-xl flex items-center justify-center mb-6 group-hover:bg-primary-200 transition">
              <Icon name="mdi:video" class="w-7 h-7 text-primary-600" />
            </div>
            <h3 class="text-xl font-semibold text-canvas-900 mb-3">Video Shop</h3>
            <p class="text-canvas-600 leading-relaxed">
              Sell technique videos, tutorials, and process demonstrations. Share your knowledge and earn passive income from your expertise.
            </p>
          </div>

          <div class="p-8 rounded-2xl bg-canvas-50 hover:bg-primary-50 transition group">
            <div class="w-14 h-14 bg-primary-100 rounded-xl flex items-center justify-center mb-6 group-hover:bg-primary-200 transition">
              <Icon name="mdi:currency-usd" class="w-7 h-7 text-primary-600" />
            </div>
            <h3 class="text-xl font-semibold text-canvas-900 mb-3">Sell Your Work</h3>
            <p class="text-canvas-600 leading-relaxed">
              Accept payments directly through Stripe. Set your prices, manage orders, and track earnings all in one place.
            </p>
          </div>

          <div class="p-8 rounded-2xl bg-canvas-50 hover:bg-primary-50 transition group">
            <div class="w-14 h-14 bg-primary-100 rounded-xl flex items-center justify-center mb-6 group-hover:bg-primary-200 transition">
              <Icon name="mdi:chart-bar" class="w-7 h-7 text-primary-600" />
            </div>
            <h3 class="text-xl font-semibold text-canvas-900 mb-3">Grisaille Tool</h3>
            <p class="text-canvas-600 leading-relaxed">
              Upload any photo and get instant value analysis with 0-9 grayscale mapping, palette recommendations, and glazing suggestions.
            </p>
          </div>

          <div class="p-8 rounded-2xl bg-canvas-50 hover:bg-primary-50 transition group">
            <div class="w-14 h-14 bg-primary-100 rounded-xl flex items-center justify-center mb-6 group-hover:bg-primary-200 transition">
              <Icon name="mdi:hammer-wrench" class="w-7 h-7 text-primary-600" />
            </div>
            <h3 class="text-xl font-semibold text-canvas-900 mb-3">Project Tracking</h3>
            <p class="text-canvas-600 leading-relaxed">
              Share your works-in-progress. Document planned, in-progress, and completed projects to engage your audience.
            </p>
          </div>

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
              Receive personalized palette recommendations and glazing suggestions for your paintings.
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

          <div class="relative">
            <div class="bg-white rounded-2xl shadow-2xl p-6">
              <div class="aspect-square bg-canvas-100 rounded-xl overflow-hidden mb-4 flex items-center justify-center">
                <Icon name="mdi:image" class="w-24 h-24 text-canvas-300" />
              </div>
              <div class="flex gap-1 h-8">
                <div v-for="i in 10" :key="i" class="flex-1 rounded" :style="{ background: `rgb(${(i-1)*28}, ${(i-1)*28}, ${(i-1)*28})` }" />
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
          {{ isAuthenticated ? 'Ready to Get Started?' : 'Ready to Share Your Art with the World?' }}
        </h2>
        <p class="mt-6 text-xl text-primary-100 max-w-2xl mx-auto">
          {{ isAuthenticated
            ? 'Head to your dashboard to start creating and selling.'
            : 'Join thousands of painters who are building their careers on Atelier. Create your free account today.'
          }}
        </p>
        <div class="mt-10 flex flex-wrap justify-center gap-4">
          <NuxtLink
            v-if="isAuthenticated"
            to="/dashboard"
            class="px-8 py-4 bg-white text-primary-700 rounded-xl font-semibold text-lg hover:bg-primary-50 transition shadow-lg"
          >
            Go to Dashboard
          </NuxtLink>
          <NuxtLink
            v-else
            to="/register"
            class="px-8 py-4 bg-white text-primary-700 rounded-xl font-semibold text-lg hover:bg-primary-50 transition shadow-lg"
          >
            Create Free Account
          </NuxtLink>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'default' })

const { user, isAuthenticated, isPainter } = useAuth()
</script>

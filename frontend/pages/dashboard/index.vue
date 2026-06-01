<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <h1 class="text-3xl font-serif font-bold text-canvas-900 mb-8">Dashboard</h1>
      <p class="text-canvas-600">Welcome back, {{ user?.name }}</p>

      <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <NuxtLink
          to="/dashboard/paintings"
          class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition group"
        >
          <Icon name="mdi:image" class="w-8 h-8 text-primary-600 mb-3" />
          <h3 class="font-semibold text-canvas-900 group-hover:text-primary-600 transition">Manage Paintings</h3>
          <p class="text-sm text-canvas-500 mt-1">Upload and organize your artwork</p>
        </NuxtLink>

        <NuxtLink
          to="/dashboard/videos"
          class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition group"
        >
          <Icon name="mdi:video-outline" class="w-8 h-8 text-primary-600 mb-3" />
          <h3 class="font-semibold text-canvas-900 group-hover:text-primary-600 transition">Manage Videos</h3>
          <p class="text-sm text-canvas-500 mt-1">Upload tutorials and techniques</p>
        </NuxtLink>

        <NuxtLink
          to="/dashboard/earnings"
          class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition group"
        >
          <Icon name="mdi:cash-multiple" class="w-8 h-8 text-primary-600 mb-3" />
          <h3 class="font-semibold text-canvas-900 group-hover:text-primary-600 transition">Earnings</h3>
          <p class="text-sm text-canvas-500 mt-1">Track sales and payouts</p>
        </NuxtLink>

        <NuxtLink
          to="/dashboard/settings"
          class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition group"
        >
          <Icon name="mdi:cog" class="w-8 h-8 text-primary-600 mb-3" />
          <h3 class="font-semibold text-canvas-900 group-hover:text-primary-600 transition">Settings</h3>
          <p class="text-sm text-canvas-500 mt-1">Manage your account and payments</p>
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const { user } = useAuth()
</script>

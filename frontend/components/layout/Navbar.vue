<template>
  <nav class="fixed top-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-md border-b border-canvas-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        <NuxtLink to="/" class="flex items-center gap-2">
          <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
            <span class="text-white font-serif font-bold text-lg">A</span>
          </div>
          <span class="text-xl font-serif font-bold text-canvas-900">Atelier</span>
        </NuxtLink>

        <div class="hidden md:flex items-center gap-8">
          <NuxtLink to="/gallery" class="text-canvas-600 hover:text-primary-600 transition font-medium">
            Gallery
          </NuxtLink>
          <NuxtLink to="/painters" class="text-canvas-600 hover:text-primary-600 transition font-medium">
            Painters
          </NuxtLink>
          <NuxtLink to="/shop" class="text-canvas-600 hover:text-primary-600 transition font-medium">
            Shop
          </NuxtLink>
          <NuxtLink to="/grisaille" class="text-canvas-600 hover:text-primary-600 transition font-medium">
            Grisaille Tool
          </NuxtLink>
        </div>

        <div class="flex items-center gap-4">
          <template v-if="isAuthenticated">
            <div class="hidden sm:flex items-center gap-2">
              <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                <span class="text-xs font-serif font-bold text-primary-600">{{ user?.name?.charAt(0) }}</span>
              </div>
              <span class="text-sm font-medium text-canvas-700">{{ user?.name }}</span>
            </div>
            <NuxtLink
              to="/dashboard"
              class="px-4 py-2 text-canvas-700 hover:text-primary-600 transition font-medium"
            >
              Dashboard
            </NuxtLink>
            <button
              @click="handleLogout"
              class="px-4 py-2 text-canvas-600 hover:text-canvas-800 transition text-sm"
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
              class="px-5 py-2 bg-primary-500 text-white rounded-xl font-medium hover:bg-primary-600 transition"
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
const { user, isAuthenticated, isPainter, logout } = useAuth()

const handleLogout = async () => {
  await logout()
}
</script>

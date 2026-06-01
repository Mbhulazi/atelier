<template>
  <footer class="bg-canvas-900 text-canvas-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
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

        <div>
          <h4 class="text-white font-semibold mb-4">Platform</h4>
          <ul class="space-y-2 text-sm">
            <li><NuxtLink to="/explore" class="hover:text-primary-400 transition">Explore Painters</NuxtLink></li>
            <li><NuxtLink to="/shop" class="hover:text-primary-400 transition">Video Shop</NuxtLink></li>
            <li><NuxtLink to="/grisaille" class="hover:text-primary-400 transition">Grisaille Tool</NuxtLink></li>
            <li><NuxtLink to="/register" class="hover:text-primary-400 transition">Start Selling</NuxtLink></li>
          </ul>
        </div>

        <div>
          <h4 class="text-white font-semibold mb-4">For Painters</h4>
          <ul class="space-y-2 text-sm">
            <li><NuxtLink to="/register" class="hover:text-primary-400 transition">Create Account</NuxtLink></li>
            <li><NuxtLink to="/dashboard" class="hover:text-primary-400 transition">Dashboard</NuxtLink></li>
            <li><NuxtLink to="/pricing" class="hover:text-primary-400 transition">Pricing</NuxtLink></li>
          </ul>
        </div>

        <div>
          <h4 class="text-white font-semibold mb-4">Legal</h4>
          <ul class="space-y-2 text-sm">
            <li><NuxtLink to="/privacy" class="hover:text-primary-400 transition">Privacy Policy</NuxtLink></li>
            <li><NuxtLink to="/terms" class="hover:text-primary-400 transition">Terms of Service</NuxtLink></li>
          </ul>
        </div>
      </div>

      <div class="border-t border-canvas-800 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
        <p class="text-canvas-500 text-sm">
          &copy; {{ new Date().getFullYear() }} Atelier. All rights reserved.
        </p>
      </div>
    </div>
  </footer>
</template>

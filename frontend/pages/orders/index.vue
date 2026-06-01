<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <h1 class="text-3xl font-serif font-bold text-canvas-900 mb-8">My Orders</h1>

      <div v-if="pending" class="space-y-4">
        <div v-for="i in 5" :key="i" class="bg-white rounded-2xl p-6 shadow-sm animate-pulse">
          <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-canvas-200 rounded-lg" />
            <div class="flex-1 space-y-2">
              <div class="h-4 bg-canvas-200 rounded w-1/3" />
              <div class="h-3 bg-canvas-200 rounded w-1/4" />
            </div>
            <div class="h-6 bg-canvas-200 rounded w-20" />
          </div>
        </div>
      </div>

      <div v-else-if="orders?.data?.length" class="space-y-4">
        <NuxtLink
          v-for="order in orders.data"
          :key="order.id"
          :to="`/orders/${order.order_number}`"
          class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition block"
        >
          <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-lg overflow-hidden bg-canvas-100 flex-shrink-0">
              <img
                v-if="order.painting?.images?.[0]"
                :src="`${apiUrl}/storage/${order.painting.images[0].image_url}`"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center">
                <Icon name="mdi:play-circle" class="w-8 h-8 text-canvas-300" />
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-serif font-semibold text-canvas-900 truncate">
                {{ order.painting?.title || order.video?.title }}
              </p>
              <p class="text-sm text-canvas-500">
                {{ order.type === 'painting' ? 'Painting' : 'Video' }} from {{ order.painter?.name }}
              </p>
              <p class="text-xs text-canvas-400 mt-1">{{ formatDate(order.created_at) }} — {{ order.order_number }}</p>
            </div>
            <div class="text-right flex-shrink-0">
              <p class="font-semibold text-canvas-900">${{ order.total }}</p>
              <span :class="[
                'text-xs px-2 py-0.5 rounded-full mt-1 inline-block',
                statusClass(order.status)
              ]">
                {{ order.status }}
              </span>
            </div>
          </div>
        </NuxtLink>
      </div>

      <div v-else class="text-center py-20 bg-white rounded-2xl">
        <Icon name="mdi:receipt-text-outline" class="w-16 h-16 text-canvas-300 mx-auto mb-4" />
        <p class="text-canvas-500 text-lg mb-4">No orders yet</p>
        <NuxtLink to="/gallery" class="text-primary-600 hover:text-primary-700 font-medium">
          Browse gallery
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const { apiUrl } = useApi()

const { data: orders, pending } = await useFetch(`${apiUrl}/orders`, {
  headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
})

const formatDate = (date: string) => new Date(date).toLocaleDateString('en-US', {
  month: 'short', day: 'numeric', year: 'numeric'
})

const statusClass = (status: string) => {
  const map: Record<string, string> = {
    completed: 'bg-green-50 text-green-700',
    pending: 'bg-yellow-50 text-yellow-700',
    processing: 'bg-blue-50 text-blue-700',
    failed: 'bg-red-50 text-red-700',
    refunded: 'bg-canvas-100 text-canvas-600',
    cancelled: 'bg-canvas-100 text-canvas-600',
  }
  return map[status] || 'bg-canvas-100 text-canvas-600'
}

useHead({ title: 'My Orders — Atelier' })
</script>

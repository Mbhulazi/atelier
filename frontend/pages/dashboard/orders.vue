<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <h1 class="text-3xl font-serif font-bold text-canvas-900 mb-8">Sales Orders</h1>

      <div v-if="pending" class="space-y-4">
        <div v-for="i in 5" :key="i" class="bg-white rounded-2xl p-6 shadow-sm animate-pulse">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-canvas-200 rounded-full" />
            <div class="flex-1 space-y-2">
              <div class="h-4 bg-canvas-200 rounded w-1/3" />
              <div class="h-3 bg-canvas-200 rounded w-1/4" />
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="orders?.data?.length" class="space-y-4">
        <div
          v-for="order in orders.data"
          :key="order.id"
          class="bg-white rounded-2xl p-6 shadow-sm"
        >
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
              <span class="text-sm font-serif font-bold text-primary-600">{{ order.buyer?.name?.charAt(0) }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-medium text-canvas-900">{{ order.buyer?.name }}</p>
              <p class="text-sm text-canvas-500">{{ order.painting?.title || order.video?.title }}</p>
              <p class="text-xs text-canvas-400 mt-1">{{ order.order_number }} — {{ formatDate(order.created_at) }}</p>
            </div>
            <div class="text-right flex-shrink-0">
              <p class="font-semibold text-canvas-900">${{ order.total }}</p>
              <p class="text-sm text-green-600">+${{ order.payout_amount }} payout</p>
              <span :class="['text-xs px-2 py-0.5 rounded-full mt-1 inline-block', statusClass(order.status)]">
                {{ order.status }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-20 bg-white rounded-2xl">
        <Icon name="mdi:shopping-outline" class="w-16 h-16 text-canvas-300 mx-auto mb-4" />
        <p class="text-canvas-500 text-lg">No sales yet</p>
      </div>

      <div v-if="orders?.last_page > 1" class="flex justify-center mt-12 gap-2">
        <button
          v-for="page in orders.last_page"
          :key="page"
          @click="currentPage = page"
          :class="[
            'w-10 h-10 rounded-full text-sm font-medium transition',
            currentPage === page
              ? 'bg-primary-500 text-white'
              : 'bg-white text-canvas-600 hover:bg-canvas-100 border border-canvas-200'
          ]"
        >
          {{ page }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const { apiUrl } = useApi()
const currentPage = ref(1)

const { data: orders, pending } = await useFetch(`${apiUrl}/dashboard/orders`, {
  query: { page: currentPage },
  headers: import.meta.client ? { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } : {},
  watch: [currentPage],
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
  }
  return map[status] || 'bg-canvas-100 text-canvas-600'
}

useHead({ title: 'Sales Orders — Atelier Dashboard' })
</script>

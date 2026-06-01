<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div v-if="pending" class="max-w-4xl mx-auto px-4 py-12">
      <div class="animate-pulse space-y-6">
        <div class="h-8 bg-canvas-200 rounded w-1/3" />
        <div class="h-40 bg-canvas-200 rounded-2xl" />
      </div>
    </div>

    <div v-else-if="order" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div v-if="order.status === 'completed'" class="bg-green-50 border border-green-200 rounded-2xl p-6 mb-8 text-center">
        <Icon name="mdi:check-circle" class="w-12 h-12 text-green-500 mx-auto mb-3" />
        <h2 class="text-xl font-semibold text-green-800">Payment Successful</h2>
        <p class="text-green-600 mt-1">Thank you for your purchase!</p>
      </div>

      <div v-if="order.status === 'pending'" class="bg-yellow-50 border border-yellow-200 rounded-2xl p-6 mb-8 text-center">
        <Icon name="mdi:clock-outline" class="w-12 h-12 text-yellow-500 mx-auto mb-3" />
        <h2 class="text-xl font-semibold text-yellow-800">Payment Pending</h2>
        <p class="text-yellow-600 mt-1">Complete your payment to finalize the order.</p>
        <button
          @click="retryPayment"
          :disabled="retrying"
          class="mt-4 px-6 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-xl font-medium transition disabled:opacity-50"
        >
          {{ retrying ? 'Redirecting...' : 'Complete Payment' }}
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white rounded-2xl p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-canvas-900 mb-4">Order Details</h2>
            <div class="flex gap-4">
              <div class="w-24 h-24 rounded-xl overflow-hidden bg-canvas-100 flex-shrink-0">
                <img
                  v-if="order.painting?.images?.[0]"
                  :src="`${apiUrl}/storage/${order.painting.images[0].image_url}`"
                  class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center">
                  <Icon name="mdi:play-circle" class="w-8 h-8 text-canvas-300" />
                </div>
              </div>
              <div>
                <h3 class="font-serif font-semibold text-canvas-900">{{ order.painting?.title || order.video?.title }}</h3>
                <p class="text-sm text-canvas-500 mt-1">From {{ order.painter?.name }}</p>
                <p v-if="order.painting?.dimensions" class="text-sm text-canvas-400 mt-1">{{ order.painting.dimensions }}</p>
                <p class="text-sm text-canvas-400 mt-1 capitalize">{{ order.type }}</p>
              </div>
            </div>
          </div>

          <div v-if="order.shipping_address" class="bg-white rounded-2xl p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-canvas-900 mb-4">Shipping Address</h2>
            <p class="text-canvas-600 whitespace-pre-wrap">{{ order.shipping_address }}</p>
          </div>

          <div v-if="order.notes" class="bg-white rounded-2xl p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-canvas-900 mb-4">Order Notes</h2>
            <p class="text-canvas-600">{{ order.notes }}</p>
          </div>
        </div>

        <div class="lg:col-span-1">
          <div class="bg-white rounded-2xl p-6 shadow-sm sticky top-24">
            <h2 class="text-lg font-semibold text-canvas-900 mb-4">Summary</h2>
            <div class="space-y-3 text-sm">
              <div class="flex justify-between">
                <span class="text-canvas-500">Order Number</span>
                <span class="font-mono text-canvas-900">{{ order.order_number }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-canvas-500">Date</span>
                <span class="text-canvas-900">{{ formatDate(order.created_at) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-canvas-500">Status</span>
                <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', statusClass(order.status)]">
                  {{ order.status }}
                </span>
              </div>
              <div class="border-t border-canvas-100 pt-3 flex justify-between text-base">
                <span class="font-semibold text-canvas-900">Total</span>
                <span class="font-bold text-primary-600">${{ order.total }}</span>
              </div>
            </div>

            <NuxtLink
              v-if="order.type === 'video' && order.status === 'completed'"
              :to="`/video/${order.video_id}`"
              class="block w-full mt-4 py-3 text-center bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl transition"
            >
              Watch Video
            </NuxtLink>

            <NuxtLink
              v-if="order.type === 'painting' && order.painting"
              :to="`/painting/${order.painting_id}`"
              class="block w-full mt-4 py-3 text-center border border-canvas-200 text-canvas-700 font-medium rounded-xl hover:bg-canvas-100 transition"
            >
              View Painting
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-20">
      <Icon name="mdi:receipt-text-outline" class="w-16 h-16 text-canvas-300 mx-auto mb-4" />
      <p class="text-canvas-500 text-lg">Order not found</p>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const route = useRoute()
const { authFetch, apiUrl } = useApi()
const orderNumber = route.params.id as string

const retrying = ref(false)

const { data: order, pending } = await useFetch(`${apiUrl}/orders/${orderNumber}`, {
  headers: import.meta.client ? { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } : {},
})

const retryPayment = async () => {
  retrying.value = true
  try {
    const res = await authFetch<{ url: string }>('/orders/checkout', {
      method: 'POST',
      body: {
        type: order.value?.type,
        painting_id: order.value?.painting_id,
        video_id: order.value?.video_id,
      },
    })
    window.location.href = res.url
  } catch (e: any) {
    alert(e.data?.message || 'Failed to retry payment')
  } finally {
    retrying.value = false
  }
}

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

useHead({
  title: computed(() => order.value ? `Order ${order.value.order_number} — Atelier` : 'Order — Atelier'),
})
</script>

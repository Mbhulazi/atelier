<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <h1 class="text-3xl font-serif font-bold text-canvas-900 mb-2">Earnings & Payments</h1>
      <p class="text-canvas-500 mb-8">Track your sales and manage Stripe payouts</p>

      <div v-if="!stripeStatus?.connected" class="bg-gradient-to-br from-primary-50 to-primary-100/50 border border-primary-200 rounded-2xl p-8 mb-8">
        <div class="flex flex-col md:flex-row items-start gap-6">
          <div class="w-14 h-14 bg-primary-500 rounded-2xl flex items-center justify-center flex-shrink-0">
            <Icon name="mdi:credit-card-outline" class="w-7 h-7 text-white" />
          </div>
          <div class="flex-1">
            <h2 class="text-xl font-semibold text-canvas-900 mb-2">Connect with Stripe</h2>
            <p class="text-canvas-600 mb-4">Set up your Stripe account to receive payments directly. You'll need to provide your banking details to get started.</p>
            <button
              @click="connectStripe"
              :disabled="connecting"
              class="inline-flex items-center gap-2 px-6 py-3 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 text-white font-semibold rounded-xl transition"
            >
              <Icon name="mdi:credit-card" class="w-5 h-5" />
              {{ connecting ? 'Redirecting...' : 'Connect with Stripe' }}
            </button>
          </div>
        </div>
      </div>

      <div v-else class="bg-green-50 border border-green-200 rounded-2xl p-6 mb-8 flex items-center gap-4">
        <Icon name="mdi:check-circle" class="w-8 h-8 text-green-500 flex-shrink-0" />
        <div>
          <p class="font-medium text-green-800">Stripe Connected</p>
          <p class="text-sm text-green-600">You're ready to receive payments. Payouts are processed automatically.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <Icon name="mdi:cash-multiple" class="w-8 h-8 text-primary-500 mb-3" />
          <p class="text-2xl font-bold text-canvas-900">${{ formatMoney(earnings?.total_earnings) }}</p>
          <p class="text-sm text-canvas-500">Total Earnings</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <Icon name="mdi:clock-outline" class="w-8 h-8 text-yellow-500 mb-3" />
          <p class="text-2xl font-bold text-canvas-900">${{ formatMoney(earnings?.pending_earnings) }}</p>
          <p class="text-sm text-canvas-500">Pending Payout</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <Icon name="mdi:calendar-month" class="w-8 h-8 text-blue-500 mb-3" />
          <p class="text-2xl font-bold text-canvas-900">${{ formatMoney(earnings?.monthly_earnings) }}</p>
          <p class="text-sm text-canvas-500">This Month</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <Icon name="mdi:shopping" class="w-8 h-8 text-green-500 mb-3" />
          <p class="text-2xl font-bold text-canvas-900">{{ earnings?.total_sales || 0 }}</p>
          <p class="text-sm text-canvas-500">Total Sales</p>
        </div>
      </div>

      <div v-if="earnings?.monthly_data?.length" class="bg-white rounded-2xl p-6 shadow-sm mb-8">
        <h2 class="text-lg font-semibold text-canvas-900 mb-4">Monthly Earnings</h2>
        <div class="h-64">
          <client-only>
            <DashboardChart :data="earnings.monthly_data" />
          </client-only>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-canvas-900 mb-4">Recent Orders</h2>
        <div v-if="earnings?.recent_orders?.length" class="space-y-4">
          <div
            v-for="order in earnings.recent_orders"
            :key="order.id"
            class="flex items-center gap-4 py-3 border-b border-canvas-100 last:border-0"
          >
            <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
              <span class="text-sm font-serif font-bold text-primary-600">{{ order.buyer?.name?.charAt(0) }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-canvas-900 truncate">{{ order.buyer?.name }}</p>
              <p class="text-xs text-canvas-500">{{ order.type === 'painting' ? order.painting?.title : order.video?.title }}</p>
            </div>
            <div class="text-right flex-shrink-0">
              <p class="font-semibold text-green-600">+${{ order.payout_amount }}</p>
              <p class="text-xs text-canvas-400">{{ formatDate(order.paid_at) }}</p>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-8">
          <Icon name="mdi:receipt-text-outline" class="w-10 h-10 text-canvas-300 mx-auto mb-2" />
          <p class="text-canvas-500">No sales yet</p>
        </div>
        <NuxtLink
          v-if="earnings?.total_sales > 0"
          to="/dashboard/orders"
          class="block text-center mt-4 text-primary-600 hover:text-primary-700 font-medium text-sm"
        >
          View all orders
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const { authFetch, apiUrl } = useApi()
const connecting = ref(false)

const { data: stripeStatus } = await useFetch(`${apiUrl}/stripe/connect/status`, {
  headers: import.meta.client ? { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } : {},
})

const { data: earnings } = await useFetch(`${apiUrl}/dashboard/earnings`, {
  headers: import.meta.client ? { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } : {},
})

const connectStripe = async () => {
  connecting.value = true
  try {
    const res = await authFetch<{ url: string }>('/stripe/connect/onboard', {
      method: 'POST',
    })
    window.location.href = res.url
  } catch (e: any) {
    alert(e.data?.message || 'Failed to connect Stripe')
  } finally {
    connecting.value = false
  }
}

const formatMoney = (amount: number | undefined) => {
  if (!amount) return '0.00'
  return Number(amount).toFixed(2)
}

const formatDate = (date: string | null) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

useHead({ title: 'Earnings — Atelier Dashboard' })
</script>

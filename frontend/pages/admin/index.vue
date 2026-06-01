<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <h1 class="text-3xl font-serif font-bold text-canvas-900 mb-8">Admin Dashboard</h1>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <Icon name="mdi:account-group" class="w-8 h-8 text-primary-500 mb-3" />
          <p class="text-2xl font-bold text-canvas-900">{{ stats?.total_users || 0 }}</p>
          <p class="text-sm text-canvas-500">Total Users</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <Icon name="mdi:palette" class="w-8 h-8 text-blue-500 mb-3" />
          <p class="text-2xl font-bold text-canvas-900">{{ stats?.total_painters || 0 }}</p>
          <p class="text-sm text-canvas-500">Painters</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <Icon name="mdi:image" class="w-8 h-8 text-green-500 mb-3" />
          <p class="text-2xl font-bold text-canvas-900">{{ stats?.total_paintings || 0 }}</p>
          <p class="text-sm text-canvas-500">Paintings</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <Icon name="mdi:cash" class="w-8 h-8 text-yellow-500 mb-3" />
          <p class="text-2xl font-bold text-canvas-900">${{ formatMoney(stats?.total_revenue) }}</p>
          <p class="text-sm text-canvas-500">Total Revenue</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-6 shadow-sm">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-lg font-semibold text-canvas-900">Users</h2>
          <div class="flex gap-2">
            <select v-model="roleFilter" class="px-3 py-2 rounded-xl border border-canvas-200 text-sm bg-white">
              <option value="">All Roles</option>
              <option value="painter">Painters</option>
              <option value="collector">Collectors</option>
              <option value="admin">Admins</option>
            </select>
          </div>
        </div>

        <div v-if="pending" class="space-y-4">
          <div v-for="i in 5" :key="i" class="h-16 bg-canvas-100 rounded-xl animate-pulse" />
        </div>

        <div v-else-if="users?.data?.length" class="divide-y divide-canvas-100">
          <div
            v-for="u in users.data"
            :key="u.id"
            class="flex items-center gap-4 py-4"
          >
            <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
              <span class="text-sm font-serif font-bold text-primary-600">{{ u.name?.charAt(0) }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-medium text-canvas-900 truncate">{{ u.name }}</p>
              <p class="text-sm text-canvas-500 truncate">{{ u.email }}</p>
            </div>
            <span :class="[
              'text-xs px-2 py-0.5 rounded-full capitalize',
              u.role === 'admin' ? 'bg-purple-50 text-purple-700' :
              u.role === 'painter' ? 'bg-blue-50 text-blue-700' :
              'bg-canvas-100 text-canvas-600'
            ]">
              {{ u.role }}
            </span>
            <select
              :value="u.role"
              @change="updateRole(u.id, ($event.target as HTMLSelectElement).value)"
              class="px-2 py-1 rounded-lg border border-canvas-200 text-xs bg-white"
            >
              <option value="collector">Collector</option>
              <option value="painter">Painter</option>
              <option value="admin">Admin</option>
            </select>
            <button
              v-if="u.id !== currentUser?.id"
              @click="deleteUser(u.id, u.name)"
              class="p-2 text-canvas-400 hover:text-red-500 transition"
            >
              <Icon name="mdi:delete-outline" class="w-4 h-4" />
            </button>
          </div>
        </div>

        <div v-else class="text-center py-8">
          <p class="text-canvas-500">No users found</p>
        </div>

        <div v-if="users?.last_page > 1" class="flex justify-center mt-6 gap-2">
          <button
            v-for="page in users.last_page"
            :key="page"
            @click="currentPage = page"
            :class="[
              'w-8 h-8 rounded-full text-sm font-medium transition',
              currentPage === page
                ? 'bg-primary-500 text-white'
                : 'bg-canvas-100 text-canvas-600 hover:bg-canvas-200'
            ]"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'auth', layout: false })

const config = useRuntimeConfig()
const apiUrl = config.public.apiUrl
const { user: currentUser, isAdmin } = useAuth()

if (!isAdmin.value) {
  navigateTo('/dashboard')
}

const roleFilter = ref('')
const currentPage = ref(1)

const { data: stats } = await useFetch(`${apiUrl}/admin/stats`, {
  credentials: 'include',
})

const { data: users, pending, refresh } = await useFetch(`${apiUrl}/admin/users`, {
  query: computed(() => ({
    role: roleFilter.value,
    page: currentPage.value,
  })),
  credentials: 'include',
  watch: [roleFilter, currentPage],
})

const updateRole = async (userId: number, newRole: string) => {
  try {
    await $fetch(`${apiUrl}/admin/users/${userId}/role`, {
      method: 'PUT',
      body: { role: newRole },
      credentials: 'include',
    })
    refresh()
  } catch (e: any) {
    alert(e.data?.message || 'Failed to update role')
  }
}

const deleteUser = async (userId: number, name: string) => {
  if (!confirm(`Are you sure you want to delete ${name}?`)) return
  try {
    await $fetch(`${apiUrl}/admin/users/${userId}`, {
      method: 'DELETE',
      credentials: 'include',
    })
    refresh()
  } catch (e: any) {
    alert(e.data?.message || 'Failed to delete user')
  }
}

const formatMoney = (amount: number | undefined) => {
  if (!amount) return '0.00'
  return Number(amount).toFixed(2)
}

useHead({ title: 'Admin — Atelier' })
</script>

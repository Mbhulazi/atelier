<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-serif font-bold text-canvas-900">My Paintings</h1>
          <p class="text-canvas-500 mt-1">Manage your artwork collection</p>
        </div>
        <NuxtLink
          to="/dashboard/paintings/create"
          class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-500 hover:bg-primary-600 text-white font-medium rounded-xl transition"
        >
          <Icon name="mdi:plus" class="w-5 h-5" />
          Add Painting
        </NuxtLink>
      </div>

      <div v-if="pending" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="i in 6" :key="i" class="bg-white rounded-2xl overflow-hidden shadow-sm animate-pulse">
          <div class="aspect-[4/5] bg-canvas-200" />
          <div class="p-4 space-y-3">
            <div class="h-4 bg-canvas-200 rounded w-3/4" />
            <div class="h-3 bg-canvas-200 rounded w-1/2" />
          </div>
        </div>
      </div>

      <div v-else-if="paintings?.data?.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="painting in paintings.data"
          :key="painting.id"
          class="bg-white rounded-2xl overflow-hidden shadow-sm group"
        >
          <div class="relative aspect-[4/5] bg-canvas-100">
            <img
              :src="painting.images?.[0]?.image_url ? `${apiUrl}/storage/${painting.images[0].image_url}` : '/placeholder-painting.jpg'"
              :alt="painting.title"
              class="w-full h-full object-cover"
            />
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition flex items-center justify-center opacity-0 group-hover:opacity-100">
              <div class="flex gap-2">
                <NuxtLink
                  :to="`/dashboard/paintings/${painting.id}/edit`"
                  class="px-4 py-2 bg-white rounded-lg text-sm font-medium hover:bg-canvas-50 transition"
                >
                  Edit
                </NuxtLink>
                <button
                  @click="deletePainting(painting.id)"
                  class="px-4 py-2 bg-red-500 text-white rounded-lg text-sm font-medium hover:bg-red-600 transition"
                >
                  Delete
                </button>
              </div>
            </div>
            <div class="absolute top-3 right-3 flex gap-1">
              <span v-if="painting.is_featured" class="px-2 py-0.5 bg-primary-500 text-white text-xs rounded-full">Featured</span>
              <span v-if="!painting.is_available" class="px-2 py-0.5 bg-canvas-700 text-white text-xs rounded-full">Sold</span>
            </div>
          </div>
          <div class="p-4">
            <h3 class="font-serif font-semibold text-canvas-900">{{ painting.title }}</h3>
            <div class="flex items-center justify-between mt-2">
              <span v-if="painting.price" class="text-primary-600 font-semibold">${{ painting.price }}</span>
              <span v-else class="text-canvas-400 text-sm">No price</span>
              <span class="text-xs text-canvas-400">{{ formatDate(painting.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-20 bg-white rounded-2xl">
        <Icon name="mdi:image-outline" class="w-16 h-16 text-canvas-300 mx-auto mb-4" />
        <p class="text-canvas-500 text-lg mb-4">No paintings yet</p>
        <NuxtLink
          to="/dashboard/paintings/create"
          class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-500 hover:bg-primary-600 text-white font-medium rounded-xl transition"
        >
          <Icon name="mdi:plus" class="w-5 h-5" />
          Add Your First Painting
        </NuxtLink>
      </div>

      <div v-if="paintings?.last_page > 1" class="flex justify-center mt-12 gap-2">
        <button
          v-for="page in paintings.last_page"
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

const config = useRuntimeConfig()
const apiUrl = config.public.apiUrl
const currentPage = ref(1)

const { data: paintings, pending, refresh } = await useFetch(`${apiUrl}/dashboard/paintings`, {
  query: { page: currentPage },
  credentials: 'include',
  watch: [currentPage],
})

const deletePainting = async (id: number) => {
  if (!confirm('Are you sure you want to delete this painting?')) return
  await $fetch(`${apiUrl}/dashboard/paintings/${id}`, {
    method: 'DELETE',
    credentials: 'include',
  })
  refresh()
}

const formatDate = (date: string) => new Date(date).toLocaleDateString('en-US', {
  month: 'short', day: 'numeric', year: 'numeric'
})

useHead({ title: 'My Paintings — Atelier Dashboard' })
</script>

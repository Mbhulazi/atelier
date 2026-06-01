<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-serif font-bold text-canvas-900">My Videos</h1>
          <p class="text-canvas-500 mt-1">Manage your video tutorials and content</p>
        </div>
        <NuxtLink
          to="/dashboard/videos/create"
          class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-500 hover:bg-primary-600 text-white font-medium rounded-xl transition"
        >
          <Icon name="mdi:plus" class="w-5 h-5" />
          Add Video
        </NuxtLink>
      </div>

      <div v-if="pending" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div v-for="i in 4" :key="i" class="bg-white rounded-2xl overflow-hidden shadow-sm animate-pulse">
          <div class="aspect-video bg-canvas-200" />
          <div class="p-4 space-y-3">
            <div class="h-4 bg-canvas-200 rounded w-3/4" />
            <div class="h-3 bg-canvas-200 rounded w-1/2" />
          </div>
        </div>
      </div>

      <div v-else-if="videos?.data?.length" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div
          v-for="video in videos.data"
          :key="video.id"
          class="bg-white rounded-2xl overflow-hidden shadow-sm group"
        >
          <div class="relative aspect-video bg-canvas-900">
            <img
              v-if="video.thumbnail_url"
              :src="`${apiUrl}/storage/${video.thumbnail_url}`"
              class="w-full h-full object-cover opacity-80"
            />
            <div class="absolute bottom-2 right-2 bg-black/70 text-white text-xs px-2 py-1 rounded">
              {{ video.duration ? formatDuration(video.duration) : '--:--' }}
            </div>
            <div class="absolute top-2 left-2 flex gap-1">
              <span v-if="video.is_published" class="px-2 py-0.5 bg-green-500 text-white text-xs rounded-full">Published</span>
              <span v-else class="px-2 py-0.5 bg-canvas-500 text-white text-xs rounded-full">Draft</span>
              <span v-if="video.is_featured" class="px-2 py-0.5 bg-primary-500 text-white text-xs rounded-full">Featured</span>
            </div>
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition flex items-center justify-center opacity-0 group-hover:opacity-100">
              <div class="flex gap-2">
                <NuxtLink
                  :to="`/dashboard/videos/${video.id}/edit`"
                  class="px-4 py-2 bg-white rounded-lg text-sm font-medium hover:bg-canvas-50 transition"
                >
                  Edit
                </NuxtLink>
                <button
                  @click="deleteVideo(video.id)"
                  class="px-4 py-2 bg-red-500 text-white rounded-lg text-sm font-medium hover:bg-red-600 transition"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
          <div class="p-4">
            <div class="flex items-center gap-2 mb-2">
              <span v-if="video.category" class="text-xs bg-canvas-100 text-canvas-600 px-2 py-0.5 rounded-full capitalize">{{ video.category }}</span>
              <span v-if="video.difficulty" class="text-xs text-canvas-400 capitalize">{{ video.difficulty }}</span>
            </div>
            <h3 class="font-serif font-semibold text-canvas-900">{{ video.title }}</h3>
            <div class="flex items-center justify-between mt-2 text-sm">
              <span v-if="video.is_free" class="text-green-600 font-medium">Free</span>
              <span v-else-if="video.price" class="text-primary-600 font-semibold">${{ video.price }}</span>
              <span class="text-canvas-400">{{ video.views_count || 0 }} views</span>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-20 bg-white rounded-2xl">
        <Icon name="mdi:video-outline" class="w-16 h-16 text-canvas-300 mx-auto mb-4" />
        <p class="text-canvas-500 text-lg mb-4">No videos yet</p>
        <NuxtLink
          to="/dashboard/videos/create"
          class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-500 hover:bg-primary-600 text-white font-medium rounded-xl transition"
        >
          <Icon name="mdi:plus" class="w-5 h-5" />
          Upload Your First Video
        </NuxtLink>
      </div>

      <div v-if="videos?.last_page > 1" class="flex justify-center mt-12 gap-2">
        <button
          v-for="page in videos.last_page"
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

const { authFetch, apiUrl } = useApi()
const currentPage = ref(1)

const { data: videos, pending, refresh } = await useFetch(`${apiUrl}/dashboard/videos`, {
  query: { page: currentPage },
  headers: import.meta.client ? { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } : {},
  watch: [currentPage],
})

const deleteVideo = async (id: number) => {
  if (!confirm('Are you sure you want to delete this video?')) return
  await $fetch(`${apiUrl}/dashboard/videos/${id}`, {
    method: 'DELETE',
    headers: import.meta.client ? { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } : {},
  })
  refresh()
}

const formatDuration = (seconds: number) => {
  const m = Math.floor(seconds / 60)
  const s = seconds % 60
  return `${m}:${s.toString().padStart(2, '0')}`
}

useHead({ title: 'My Videos — Atelier Dashboard' })
</script>

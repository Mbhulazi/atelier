<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="text-center mb-12">
        <h1 class="text-4xl font-serif font-bold text-canvas-900 mb-4">Video Shop</h1>
        <p class="text-canvas-600 max-w-2xl mx-auto">Learn from master painters with tutorials, timelapses, and technique breakdowns</p>
      </div>

      <div class="flex flex-wrap gap-3 mb-8 justify-center">
        <button
          v-for="cat in categories"
          :key="cat.key"
          @click="selectedCategory = selectedCategory === cat.key ? null : cat.key"
          :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition',
            selectedCategory === cat.key
              ? 'bg-primary-500 text-white'
              : 'bg-white text-canvas-700 hover:bg-primary-50 border border-canvas-200'
          ]"
        >
          <Icon :name="cat.icon" class="w-4 h-4 mr-1 inline" />
          {{ cat.label }}
        </button>
      </div>

      <div class="flex items-center gap-4 mb-8">
        <label class="flex items-center gap-2">
          <input v-model="freeOnly" type="checkbox" class="w-4 h-4 rounded border-canvas-300 text-primary-500 focus:ring-primary-500" />
          <span class="text-sm text-canvas-700">Free only</span>
        </label>
        <select v-model="selectedDifficulty" class="px-3 py-2 rounded-xl border border-canvas-200 text-sm bg-white focus:ring-2 focus:ring-primary-500/20">
          <option value="">All levels</option>
          <option value="beginner">Beginner</option>
          <option value="intermediate">Intermediate</option>
          <option value="advanced">Advanced</option>
        </select>
      </div>

      <div v-if="pending" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="i in 6" :key="i" class="bg-white rounded-2xl overflow-hidden shadow-sm animate-pulse">
          <div class="aspect-video bg-canvas-200" />
          <div class="p-4 space-y-3">
            <div class="h-4 bg-canvas-200 rounded w-3/4" />
            <div class="h-3 bg-canvas-200 rounded w-1/2" />
          </div>
        </div>
      </div>

      <div v-else-if="videos?.data?.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <NuxtLink
          v-for="video in videos.data"
          :key="video.id"
          :to="`/video/${video.id}`"
          class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition group"
        >
          <div class="relative aspect-video bg-canvas-900">
            <img
              v-if="video.thumbnail_url"
              :src="`${apiUrl}/storage/${video.thumbnail_url}`"
              class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition"
            />
            <div class="absolute inset-0 flex items-center justify-center">
              <div class="w-14 h-14 bg-white/90 rounded-full flex items-center justify-center group-hover:scale-110 transition shadow-lg">
                <Icon name="mdi:play" class="w-6 h-6 text-primary-600 ml-1" />
              </div>
            </div>
            <div class="absolute bottom-2 right-2 bg-black/70 text-white text-xs px-2 py-1 rounded">
              {{ video.duration ? formatDuration(video.duration) : '--:--' }}
            </div>
            <div class="absolute top-2 left-2 flex gap-1">
              <span v-if="video.is_free" class="px-2 py-0.5 bg-green-500 text-white text-xs rounded-full font-medium">Free</span>
              <span v-if="video.is_featured" class="px-2 py-0.5 bg-primary-500 text-white text-xs rounded-full font-medium">Featured</span>
            </div>
          </div>
          <div class="p-4">
            <div class="flex items-center gap-2 mb-2">
              <span v-if="video.category" class="text-xs bg-canvas-100 text-canvas-600 px-2 py-0.5 rounded-full capitalize">{{ video.category }}</span>
              <span v-if="video.difficulty" class="text-xs text-canvas-400 capitalize">{{ video.difficulty }}</span>
            </div>
            <h3 class="font-serif font-semibold text-canvas-900 group-hover:text-primary-600 transition line-clamp-2">{{ video.title }}</h3>
            <div class="flex items-center justify-between mt-3">
              <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center">
                  <span class="text-xs font-serif font-bold text-primary-600">{{ video.user?.name?.charAt(0) }}</span>
                </div>
                <span class="text-sm text-canvas-500">{{ video.user?.name }}</span>
              </div>
              <span v-if="!video.is_free && video.price" class="font-semibold text-primary-600">${{ video.price }}</span>
              <span v-else-if="video.is_free" class="text-sm text-green-600 font-medium">Free</span>
            </div>
          </div>
        </NuxtLink>
      </div>

      <div v-else class="text-center py-20">
        <Icon name="mdi:video-outline" class="w-16 h-16 text-canvas-300 mx-auto mb-4" />
        <p class="text-canvas-500 text-lg">No videos found</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const config = useRuntimeConfig()
const apiUrl = config.public.apiUrl

const categories = [
  { key: 'tutorial', label: 'Tutorials', icon: 'mdi:school-outline' },
  { key: 'timelapse', label: 'Timelapses', icon: 'mdi:timelapse' },
  { key: 'technique', label: 'Techniques', icon: 'mdi:palette-outline' },
  { key: 'critique', label: 'Critiques', icon: 'mdi:comment-eye-outline' },
]

const selectedCategory = ref<string | null>(null)
const selectedDifficulty = ref('')
const freeOnly = ref(false)

const { data: videos, pending } = await useFetch(`${apiUrl}/videos`, {
  query: computed(() => ({
    category: selectedCategory.value,
    difficulty: selectedDifficulty.value,
    free: freeOnly.value || undefined,
    limit: 12,
  })),
  watch: [selectedCategory, selectedDifficulty, freeOnly],
})

const formatDuration = (seconds: number) => {
  const h = Math.floor(seconds / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  const s = seconds % 60
  if (h > 0) return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`
  return `${m}:${s.toString().padStart(2, '0')}`
}

useHead({ title: 'Video Shop — Atelier' })
</script>

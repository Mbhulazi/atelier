<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div v-if="pending" class="max-w-7xl mx-auto px-4 py-12">
      <div class="animate-pulse space-y-6">
        <div class="h-64 bg-canvas-200 rounded-2xl" />
        <div class="h-8 bg-canvas-200 rounded w-1/3" />
        <div class="h-4 bg-canvas-200 rounded w-2/3" />
      </div>
    </div>

    <div v-else-if="painter" class="relative">
      <div class="h-80 bg-gradient-to-br from-primary-500/20 via-canvas-100 to-canvas-200 relative overflow-hidden">
        <img
          v-if="painter.painter_profile?.featured_image"
          :src="`${apiUrl}/storage/${painter.painter_profile.featured_image}`"
          class="absolute inset-0 w-full h-full object-cover opacity-40"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-canvas-50 via-transparent to-transparent" />
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-32 relative z-10">
        <div class="flex flex-col md:flex-row items-start gap-6 mb-12">
          <div class="w-32 h-32 rounded-2xl bg-white shadow-lg overflow-hidden border-4 border-white">
            <div class="w-full h-full bg-primary-100 flex items-center justify-center">
              <span class="text-4xl font-serif font-bold text-primary-600">{{ painter.name?.charAt(0) }}</span>
            </div>
          </div>
          <div class="flex-1 pt-2">
            <h1 class="text-3xl font-serif font-bold text-canvas-900">{{ painter.name }}</h1>
            <p v-if="painter.painter_profile?.location" class="text-canvas-500 mt-1 flex items-center gap-1">
              <Icon name="mdi:map-marker-outline" class="w-4 h-4" />
              {{ painter.painter_profile.location }}
            </p>
            <p v-if="painter.bio" class="text-canvas-600 mt-3 max-w-2xl">{{ painter.bio }}</p>
            <div v-if="painter.painter_profile?.specialties" class="flex flex-wrap gap-2 mt-4">
              <span
                v-for="spec in painter.painter_profile.specialties"
                :key="spec"
                class="px-3 py-1 bg-primary-50 text-primary-700 rounded-full text-sm font-medium"
              >
                {{ spec }}
              </span>
            </div>
          </div>
        </div>

        <div class="flex gap-4 mb-8 border-b border-canvas-200">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="activeTab = tab.key"
            :class="[
              'pb-3 px-1 text-sm font-medium border-b-2 transition',
              activeTab === tab.key
                ? 'border-primary-500 text-primary-600'
                : 'border-transparent text-canvas-500 hover:text-canvas-700'
            ]"
          >
            {{ tab.label }} ({{ tab.count }})
          </button>
        </div>

        <div v-if="activeTab === 'paintings'" class="pb-20">
          <div v-if="paintings?.data?.length" class="columns-1 sm:columns-2 lg:columns-3 gap-6 space-y-6">
            <NuxtLink
              v-for="painting in paintings.data"
              :key="painting.id"
              :to="`/painting/${painting.id}`"
              class="break-inside-avoid bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition group"
            >
              <div class="relative overflow-hidden">
                <img
                  :src="painting.images?.[0]?.image_url ? `${apiUrl}/storage/${painting.images[0].image_url}` : '/placeholder-painting.jpg'"
                  :alt="painting.title"
                  class="w-full object-cover group-hover:scale-105 transition duration-500"
                  loading="lazy"
                />
                <div v-if="painting.price" class="absolute bottom-3 right-3 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-sm font-semibold text-primary-700">
                  ${{ painting.price }}
                </div>
              </div>
              <div class="p-4">
                <h3 class="font-serif font-semibold text-canvas-900">{{ painting.title }}</h3>
                <div class="flex items-center gap-2 mt-2">
                  <span v-if="painting.medium" class="text-xs bg-canvas-100 text-canvas-600 px-2 py-0.5 rounded-full">{{ painting.medium }}</span>
                </div>
              </div>
            </NuxtLink>
          </div>
          <div v-else class="text-center py-16">
            <Icon name="mdi:image-outline" class="w-12 h-12 text-canvas-300 mx-auto mb-3" />
            <p class="text-canvas-500">No paintings yet</p>
          </div>
        </div>

        <div v-if="activeTab === 'videos'" class="pb-20">
          <div v-if="videos?.data?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
              v-for="video in videos.data"
              :key="video.id"
              class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition group"
            >
              <div class="relative aspect-video bg-canvas-900">
                <img
                  v-if="video.thumbnail_url"
                  :src="video.thumbnail_url"
                  class="w-full h-full object-cover opacity-80"
                />
                <div class="absolute inset-0 flex items-center justify-center">
                  <div class="w-14 h-14 bg-white/90 rounded-full flex items-center justify-center group-hover:scale-110 transition">
                    <Icon name="mdi:play" class="w-6 h-6 text-primary-600 ml-1" />
                  </div>
                </div>
                <div class="absolute bottom-2 right-2 bg-black/70 text-white text-xs px-2 py-1 rounded">
                  {{ video.duration ? formatDuration(video.duration) : '--:--' }}
                </div>
              </div>
              <div class="p-4">
                <h3 class="font-semibold text-canvas-900 group-hover:text-primary-600 transition">{{ video.title }}</h3>
                <p class="text-sm text-canvas-500 mt-1">{{ video.category }}</p>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-16">
            <Icon name="mdi:video-outline" class="w-12 h-12 text-canvas-300 mx-auto mb-3" />
            <p class="text-canvas-500">No videos yet</p>
          </div>
        </div>

        <div v-if="activeTab === 'projects'" class="pb-20">
          <div v-if="projects?.data?.length" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div
              v-for="project in projects.data"
              :key="project.id"
              class="bg-white rounded-2xl overflow-hidden shadow-sm"
            >
              <div class="aspect-video bg-canvas-100">
                <img v-if="project.cover_image" :src="project.cover_image" class="w-full h-full object-cover" />
              </div>
              <div class="p-4">
                <div class="flex items-center gap-2 mb-2">
                  <span :class="[
                    'text-xs font-medium px-2 py-0.5 rounded-full',
                    project.status === 'completed' ? 'bg-green-100 text-green-700' :
                    project.status === 'in_progress' ? 'bg-blue-100 text-blue-700' :
                    'bg-canvas-100 text-canvas-600'
                  ]">
                    {{ project.status?.replace('_', ' ') }}
                  </span>
                </div>
                <h3 class="font-semibold text-canvas-900">{{ project.title }}</h3>
                <p v-if="project.description" class="text-sm text-canvas-500 mt-1 line-clamp-2">{{ project.description }}</p>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-16">
            <Icon name="mdi:folder-outline" class="w-12 h-12 text-canvas-300 mx-auto mb-3" />
            <p class="text-canvas-500">No projects yet</p>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-20">
      <Icon name="mdi:account-outline" class="w-16 h-16 text-canvas-300 mx-auto mb-4" />
      <p class="text-canvas-500 text-lg">Painter not found</p>
      <NuxtLink to="/painters" class="mt-4 inline-block text-primary-600 hover:text-primary-700 font-medium">
        Browse all painters
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const config = useRuntimeConfig()
const apiUrl = config.public.apiUrl
const slug = route.params.slug as string

const activeTab = ref('paintings')

const { data: painter, pending } = await useFetch(`${apiUrl}/painters/${slug}`)

const { data: paintings } = await useFetch(`${apiUrl}/painters/${slug}/paintings`, {
  query: { available: 1 },
})

const { data: videos } = await useFetch(`${apiUrl}/painters/${slug}/videos`)

const { data: projects } = await useFetch(`${apiUrl}/painters/${slug}/projects`, {
  default: () => ({ data: [] }),
})

const tabs = computed(() => [
  { key: 'paintings', label: 'Paintings', count: paintings.value?.total || 0 },
  { key: 'videos', label: 'Videos', count: videos.value?.total || 0 },
  { key: 'projects', label: 'Projects', count: projects.value?.data?.length || 0 },
])

const formatDuration = (seconds: number) => {
  const m = Math.floor(seconds / 60)
  const s = seconds % 60
  return `${m}:${s.toString().padStart(2, '0')}`
}

useHead({
  title: computed(() => painter.value ? `${painter.value.name} — Atelier` : 'Painter — Atelier'),
})
</script>

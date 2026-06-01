<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="text-center mb-12">
        <h1 class="text-4xl font-serif font-bold text-canvas-900 mb-4">Our Painters</h1>
        <p class="text-canvas-600 max-w-2xl mx-auto">Discover talented artists and explore their unique styles</p>
      </div>

      <div class="flex flex-wrap gap-3 mb-8 justify-center">
        <button
          v-for="spec in specialties"
          :key="spec"
          @click="selectedSpecialty = selectedSpecialty === spec ? null : spec"
          :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition',
            selectedSpecialty === spec
              ? 'bg-primary-500 text-white'
              : 'bg-white text-canvas-700 hover:bg-primary-50 border border-canvas-200'
          ]"
        >
          {{ spec }}
        </button>
      </div>

      <div v-if="pending" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="i in 6" :key="i" class="bg-white rounded-2xl p-6 shadow-sm animate-pulse">
          <div class="flex items-center gap-4 mb-4">
            <div class="w-16 h-16 rounded-full bg-canvas-200" />
            <div class="space-y-2">
              <div class="h-4 bg-canvas-200 rounded w-24" />
              <div class="h-3 bg-canvas-200 rounded w-16" />
            </div>
          </div>
          <div class="h-3 bg-canvas-200 rounded w-full mb-2" />
          <div class="h-3 bg-canvas-200 rounded w-2/3" />
        </div>
      </div>

      <div v-else-if="painters?.data?.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <NuxtLink
          v-for="painter in painters.data"
          :key="painter.id"
          :to="`/painter/${painter.slug}`"
          class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-lg transition group"
        >
          <div class="flex items-center gap-4 mb-4">
            <div class="w-16 h-16 rounded-full bg-primary-100 flex items-center justify-center overflow-hidden">
              <span class="text-2xl font-serif font-bold text-primary-600">{{ painter.name?.charAt(0) }}</span>
            </div>
            <div>
              <h3 class="font-semibold text-canvas-900 group-hover:text-primary-600 transition">{{ painter.name }}</h3>
              <p v-if="painter.painter_profile?.location" class="text-sm text-canvas-500 flex items-center gap-1">
                <Icon name="mdi:map-marker-outline" class="w-3 h-3" />
                {{ painter.painter_profile.location }}
              </p>
            </div>
          </div>
          <p v-if="painter.bio" class="text-sm text-canvas-600 line-clamp-2 mb-3">{{ painter.bio }}</p>
          <div v-if="painter.painter_profile?.specialties" class="flex flex-wrap gap-1">
            <span
              v-for="spec in painter.painter_profile.specialties.slice(0, 3)"
              :key="spec"
              class="text-xs bg-canvas-100 text-canvas-600 px-2 py-0.5 rounded-full"
            >
              {{ spec }}
            </span>
          </div>
          <div class="flex items-center gap-4 mt-4 text-sm text-canvas-500 border-t border-canvas-100 pt-4">
            <span class="flex items-center gap-1">
              <Icon name="mdi:image" class="w-4 h-4" />
              {{ painter.paintings_count || 0 }} paintings
            </span>
            <span class="flex items-center gap-1">
              <Icon name="mdi:video-outline" class="w-4 h-4" />
              {{ painter.videos_count || 0 }} videos
            </span>
          </div>
        </NuxtLink>
      </div>

      <div v-else class="text-center py-20">
        <Icon name="mdi:account-group-outline" class="w-16 h-16 text-canvas-300 mx-auto mb-4" />
        <p class="text-canvas-500 text-lg">No painters found</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const config = useRuntimeConfig()
const apiUrl = config.public.apiUrl

const specialties = ['Oil Painting', 'Acrylic', 'Watercolor', 'Abstract', 'Portraiture', 'Landscape', 'Mixed Media']
const selectedSpecialty = ref<string | null>(null)

const { data: painters, pending } = await useFetch(`${apiUrl}/painters`, {
  query: computed(() => ({
    specialty: selectedSpecialty.value,
  })),
  watch: [selectedSpecialty],
})

useHead({ title: 'Painters — Atelier' })
</script>

<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="text-center mb-12">
        <h1 class="text-4xl font-serif font-bold text-canvas-900 mb-4">Gallery</h1>
        <p class="text-canvas-600 max-w-2xl mx-auto">Explore original paintings from talented artists around the world</p>
      </div>

      <div class="flex flex-wrap gap-3 mb-8 justify-center">
        <button
          v-for="style in styles"
          :key="style"
          @click="selectedStyle = selectedStyle === style ? null : style"
          :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition',
            selectedStyle === style
              ? 'bg-primary-500 text-white'
              : 'bg-white text-canvas-700 hover:bg-primary-50 border border-canvas-200'
          ]"
        >
          {{ style }}
        </button>
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

      <div v-else-if="paintings?.data?.length" class="columns-1 sm:columns-2 lg:columns-3 gap-6 space-y-6">
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
            <h3 class="font-serif font-semibold text-canvas-900 group-hover:text-primary-600 transition">{{ painting.title }}</h3>
            <p class="text-sm text-canvas-500 mt-1">{{ painting.user?.name }}</p>
            <div class="flex items-center gap-2 mt-2">
              <span v-if="painting.medium" class="text-xs bg-canvas-100 text-canvas-600 px-2 py-0.5 rounded-full">{{ painting.medium }}</span>
              <span v-if="painting.width && painting.height" class="text-xs text-canvas-400">{{ painting.width }} × {{ painting.height }} cm</span>
            </div>
          </div>
        </NuxtLink>
      </div>

      <div v-else class="text-center py-20">
        <Icon name="mdi:image-outline" class="w-16 h-16 text-canvas-300 mx-auto mb-4" />
        <p class="text-canvas-500 text-lg">No paintings found</p>
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
const config = useRuntimeConfig()
const apiUrl = config.public.apiUrl

const currentPage = ref(1)
const selectedStyle = ref<string | null>(null)

const styles = ['Oil', 'Acrylic', 'Watercolor', 'Mixed Media', 'Abstract', 'Portrait', 'Landscape', 'Still Life']

const { data: paintings, pending } = await useFetch(`${apiUrl}/paintings`, {
  query: computed(() => ({
    page: currentPage.value,
    style: selectedStyle.value,
    limit: 12,
  })),
  watch: [currentPage, selectedStyle],
})
</script>

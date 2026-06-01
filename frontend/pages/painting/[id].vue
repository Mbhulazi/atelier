<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div v-if="pending" class="max-w-6xl mx-auto px-4 py-12">
      <div class="animate-pulse">
        <div class="h-96 bg-canvas-200 rounded-2xl mb-6" />
        <div class="h-8 bg-canvas-200 rounded w-1/3 mb-4" />
        <div class="h-4 bg-canvas-200 rounded w-1/4 mb-8" />
        <div class="h-20 bg-canvas-200 rounded" />
      </div>
    </div>

    <div v-else-if="painting" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
        <div>
          <div class="relative rounded-2xl overflow-hidden bg-white shadow-sm">
            <img
              :src="activeImage ? `${apiUrl}/storage/${activeImage.image_url}` : `${apiUrl}/storage/${painting.images?.[0]?.image_url}`"
              :alt="painting.title"
              class="w-full aspect-[4/5] object-contain bg-canvas-50"
            />
          </div>
          <div v-if="painting.images?.length > 1" class="flex gap-2 mt-4 overflow-x-auto pb-2">
            <button
              v-for="(image, idx) in painting.images"
              :key="image.id"
              @click="activeImage = image"
              :class="[
                'flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2 transition',
                (activeImage?.id || painting.images[0]?.id) === image.id
                  ? 'border-primary-500'
                  : 'border-transparent hover:border-canvas-300'
              ]"
            >
              <img :src="`${apiUrl}/storage/${image.image_url}`" class="w-full h-full object-cover" />
            </button>
          </div>
        </div>

        <div>
          <NuxtLink
            :to="`/painter/${painting.user?.slug}`"
            class="inline-flex items-center gap-2 text-sm text-canvas-500 hover:text-primary-600 transition mb-4"
          >
            <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
              <span class="text-sm font-serif font-bold text-primary-600">{{ painting.user?.name?.charAt(0) }}</span>
            </div>
            {{ painting.user?.name }}
          </NuxtLink>

          <h1 class="text-3xl font-serif font-bold text-canvas-900 mb-2">{{ painting.title }}</h1>

          <div v-if="painting.price" class="text-2xl font-semibold text-primary-600 mb-6">
            ${{ painting.price }}
            <span class="text-sm text-canvas-400 font-normal ml-1">{{ painting.currency }}</span>
          </div>

          <div class="space-y-4 mb-8">
            <div v-if="painting.description" class="text-canvas-600 leading-relaxed">
              {{ painting.description }}
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div v-if="painting.medium" class="bg-white rounded-xl p-4">
                <span class="text-xs text-canvas-400 uppercase tracking-wide">Medium</span>
                <p class="font-medium text-canvas-900 mt-1">{{ painting.medium }}</p>
              </div>
              <div v-if="painting.style" class="bg-white rounded-xl p-4">
                <span class="text-xs text-canvas-400 uppercase tracking-wide">Style</span>
                <p class="font-medium text-canvas-900 mt-1">{{ painting.style }}</p>
              </div>
              <div v-if="painting.width && painting.height" class="bg-white rounded-xl p-4">
                <span class="text-xs text-canvas-400 uppercase tracking-wide">Dimensions</span>
                <p class="font-medium text-canvas-900 mt-1">{{ painting.width }} × {{ painting.height }} cm</p>
              </div>
              <div v-if="painting.year_created" class="bg-white rounded-xl p-4">
                <span class="text-xs text-canvas-400 uppercase tracking-wide">Year</span>
                <p class="font-medium text-canvas-900 mt-1">{{ painting.year_created }}</p>
              </div>
            </div>
          </div>

          <div v-if="painting.tags?.length" class="flex flex-wrap gap-2 mb-8">
            <span
              v-for="tag in painting.tags"
              :key="tag"
              class="px-3 py-1 bg-canvas-100 text-canvas-600 rounded-full text-sm"
            >
              {{ tag }}
            </span>
          </div>

          <div class="space-y-3">
            <button
              v-if="painting.is_available"
              class="w-full py-3 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl transition"
            >
              Add to Cart
            </button>
            <button
              v-else
              class="w-full py-3 bg-canvas-200 text-canvas-500 font-semibold rounded-xl cursor-not-allowed"
            >
              Sold
            </button>
            <NuxtLink
              :to="`/painter/${painting.user?.slug}`"
              class="block w-full py-3 text-center border border-canvas-200 text-canvas-700 font-medium rounded-xl hover:bg-canvas-100 transition"
            >
              View Painter Profile
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-20">
      <Icon name="mdi:image-outline" class="w-16 h-16 text-canvas-300 mx-auto mb-4" />
      <p class="text-canvas-500 text-lg">Painting not found</p>
      <NuxtLink to="/gallery" class="mt-4 inline-block text-primary-600 hover:text-primary-700 font-medium">
        Browse gallery
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const config = useRuntimeConfig()
const apiUrl = config.public.apiUrl
const paintingId = route.params.id as string

const { data: painting, pending } = await useFetch(`${apiUrl}/paintings/${paintingId}`)

const activeImage = ref<any>(null)

useHead({
  title: computed(() => painting.value ? `${painting.value.title} — Atelier` : 'Painting — Atelier'),
})
</script>

<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div v-if="pending" class="max-w-5xl mx-auto px-4 py-12">
      <div class="animate-pulse">
        <div class="aspect-video bg-canvas-200 rounded-2xl mb-6" />
        <div class="h-8 bg-canvas-200 rounded w-1/3 mb-4" />
        <div class="h-4 bg-canvas-200 rounded w-2/3" />
      </div>
    </div>

    <div v-else-if="video" class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="aspect-video bg-canvas-900 rounded-2xl overflow-hidden mb-8 relative">
        <video
          v-if="canWatch"
          ref="videoPlayer"
          :src="`${apiUrl}/storage/${video.video_url}`"
          controls
          playsinline
          class="w-full h-full object-contain"
          @ended="video.incrementViews?.()"
        />
        <div v-else class="w-full h-full flex flex-col items-center justify-center text-white">
          <img
            v-if="video.thumbnail_url"
            :src="`${apiUrl}/storage/${video.thumbnail_url}`"
            class="absolute inset-0 w-full h-full object-cover opacity-40"
          />
          <div class="relative z-10 text-center">
            <Icon name="mdi:lock-outline" class="w-16 h-16 mx-auto mb-4 opacity-60" />
            <p class="text-lg font-medium mb-2">Purchase this video to watch</p>
            <p class="text-sm opacity-70 mb-6">{{ video.title }}</p>
            <button
              @click="purchaseVideo"
              :disabled="purchasing"
              class="px-8 py-3 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 rounded-xl font-semibold transition"
            >
              {{ purchasing ? 'Redirecting...' : `Buy for $${video.price}` }}
            </button>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
          <div class="flex items-center gap-2 mb-3">
            <span v-if="video.category" class="text-xs bg-primary-50 text-primary-700 px-2 py-0.5 rounded-full capitalize font-medium">{{ video.category }}</span>
            <span v-if="video.difficulty" class="text-xs bg-canvas-100 text-canvas-600 px-2 py-0.5 rounded-full capitalize">{{ video.difficulty }}</span>
            <span v-if="video.is_free" class="text-xs bg-green-50 text-green-700 px-2 py-0.5 rounded-full font-medium">Free</span>
          </div>

          <h1 class="text-3xl font-serif font-bold text-canvas-900 mb-4">{{ video.title }}</h1>

          <div class="flex items-center gap-4 mb-6">
            <NuxtLink :to="`/painter/${video.user?.slug}`" class="flex items-center gap-2">
              <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                <span class="text-sm font-serif font-bold text-primary-600">{{ video.user?.name?.charAt(0) }}</span>
              </div>
              <div>
                <p class="font-medium text-canvas-900 text-sm">{{ video.user?.name }}</p>
                <p class="text-xs text-canvas-500">Painter</p>
              </div>
            </NuxtLink>
            <div class="flex items-center gap-4 text-sm text-canvas-500">
              <span class="flex items-center gap-1">
                <Icon name="mdi:eye-outline" class="w-4 h-4" />
                {{ video.views_count || 0 }} views
              </span>
              <span v-if="video.duration" class="flex items-center gap-1">
                <Icon name="mdi:clock-outline" class="w-4 h-4" />
                {{ formatDuration(video.duration) }}
              </span>
            </div>
          </div>

          <div class="prose prose-canvas max-w-none">
            <p v-if="video.description" class="text-canvas-600 leading-relaxed whitespace-pre-wrap">{{ video.description }}</p>
          </div>

          <div v-if="video.tags?.length" class="flex flex-wrap gap-2 mt-6">
            <span
              v-for="tag in video.tags"
              :key="tag"
              class="px-3 py-1 bg-canvas-100 text-canvas-600 rounded-full text-sm"
            >
              {{ tag }}
            </span>
          </div>
        </div>

        <div class="lg:col-span-1">
          <div class="bg-white rounded-2xl p-6 shadow-sm sticky top-24">
            <div v-if="video.is_free" class="text-center mb-4">
              <span class="text-2xl font-bold text-green-600">Free</span>
              <p class="text-sm text-canvas-500 mt-1">Watch now at no cost</p>
            </div>
            <div v-else class="text-center mb-4">
              <span class="text-3xl font-bold text-primary-600">${{ video.price }}</span>
              <p class="text-sm text-canvas-500 mt-1">One-time purchase</p>
            </div>

            <button
              v-if="!video.is_free && !hasAccess"
              @click="purchaseVideo"
              :disabled="purchasing"
              class="w-full py-3 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 text-white font-semibold rounded-xl transition mb-3"
            >
              {{ purchasing ? 'Redirecting...' : 'Buy Now' }}
            </button>
            <button
              v-else
              @click="scrollToPlayer"
              class="w-full py-3 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl transition mb-3"
            >
              Watch Now
            </button>

            <div class="space-y-3 text-sm text-canvas-600 border-t border-canvas-100 pt-4 mt-4">
              <div class="flex justify-between">
                <span>Duration</span>
                <span class="font-medium text-canvas-900">{{ video.duration ? formatDuration(video.duration) : 'N/A' }}</span>
              </div>
              <div class="flex justify-between">
                <span>Category</span>
                <span class="font-medium text-canvas-900 capitalize">{{ video.category || 'N/A' }}</span>
              </div>
              <div class="flex justify-between">
                <span>Level</span>
                <span class="font-medium text-canvas-900 capitalize">{{ video.difficulty || 'All levels' }}</span>
              </div>
              <div class="flex justify-between">
                <span>Views</span>
                <span class="font-medium text-canvas-900">{{ video.views_count || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-20">
      <Icon name="mdi:video-outline" class="w-16 h-16 text-canvas-300 mx-auto mb-4" />
      <p class="text-canvas-500 text-lg">Video not found</p>
      <NuxtLink to="/shop" class="mt-4 inline-block text-primary-600 hover:text-primary-700 font-medium">
        Browse videos
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const config = useRuntimeConfig()
const apiUrl = config.public.apiUrl
const { user, isAuthenticated } = useAuth()
const videoId = route.params.id as string

const purchasing = ref(false)
const hasAccess = ref(false)

const { data: video, pending } = await useFetch(`${apiUrl}/videos/${videoId}`)

const canWatch = computed(() => {
  if (video.value?.is_free) return true
  if (hasAccess.value) return true
  if (route.query.purchased === '1') return true
  return false
})

onMounted(async () => {
  if (isAuthenticated.value && video.value && !video.value.is_free) {
    try {
      const res = await $fetch<{ has_access: boolean }>(`${apiUrl}/videos/${videoId}/access`, {
        credentials: 'include',
      })
      hasAccess.value = res.has_access
    } catch {}
  }
})

const scrollToPlayer = () => {
  document.querySelector('video')?.scrollIntoView({ behavior: 'smooth', block: 'center' })
}

const purchaseVideo = async () => {
  if (!isAuthenticated.value) {
    navigateTo(`/login?redirect=/video/${videoId}`)
    return
  }
  purchasing.value = true
  try {
    const res = await $fetch<{ url: string }>(`${apiUrl}/videos/${videoId}/purchase`, {
      method: 'POST',
      credentials: 'include',
    })
    window.location.href = res.url
  } catch (e: any) {
    alert(e.data?.message || 'Failed to initiate purchase')
  } finally {
    purchasing.value = false
  }
}

const formatDuration = (seconds: number) => {
  const h = Math.floor(seconds / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  const s = seconds % 60
  if (h > 0) return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`
  return `${m}:${s.toString().padStart(2, '0')}`
}

useHead({
  title: computed(() => video.value ? `${video.value.title} — Atelier` : 'Video — Atelier'),
})
</script>

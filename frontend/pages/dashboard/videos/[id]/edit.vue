<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="mb-8">
        <NuxtLink to="/dashboard/videos" class="text-sm text-canvas-500 hover:text-primary-600 transition flex items-center gap-1 mb-4">
          <Icon name="mdi:arrow-left" class="w-4 h-4" />
          Back to videos
        </NuxtLink>
        <h1 class="text-3xl font-serif font-bold text-canvas-900">Edit Video</h1>
      </div>

      <div v-if="loading" class="bg-white rounded-2xl p-6 shadow-sm animate-pulse space-y-4">
        <div class="h-40 bg-canvas-200 rounded" />
        <div class="h-8 bg-canvas-200 rounded w-1/3" />
      </div>

      <form v-else @submit.prevent="submitVideo" class="space-y-8">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-canvas-900 mb-4">Current Video</h2>
          <video
            :src="`${apiUrl}/storage/${form.video_url}`"
            controls
            class="w-full rounded-xl bg-canvas-900"
          />
          <p class="text-sm text-canvas-400 mt-2">Upload a new file to replace this video</p>
          <input type="file" accept="video/*" @change="handleVideoUpload" class="mt-3" />
          <video v-if="newVideoPreview" :src="newVideoPreview" controls class="w-full rounded-xl bg-canvas-900 mt-4" />
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-canvas-900 mb-4">Thumbnail</h2>
          <label class="block border-2 border-dashed border-canvas-300 hover:border-primary-400 rounded-xl p-8 text-center cursor-pointer transition">
            <img v-if="thumbnailPreview || form.thumbnail_url" :src="thumbnailPreview || `${apiUrl}/storage/${form.thumbnail_url}`" class="max-h-40 mx-auto rounded-lg mb-2" />
            <div v-else>
              <Icon name="mdi:image-plus-outline" class="w-8 h-8 text-canvas-400 mx-auto mb-2" />
              <p class="text-sm text-canvas-500">Add thumbnail image</p>
            </div>
            <input type="file" accept="image/*" @change="handleThumbnailUpload" class="hidden" />
          </label>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-canvas-900 mb-4">Details</h2>

          <div class="space-y-5">
            <div>
              <label class="block text-sm font-medium text-canvas-700 mb-1">Title *</label>
              <input v-model="form.title" type="text" required class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition" />
            </div>

            <div>
              <label class="block text-sm font-medium text-canvas-700 mb-1">Description</label>
              <textarea v-model="form.description" rows="4" class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition resize-none" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Category</label>
                <select v-model="form.category" class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition bg-white">
                  <option value="">Select category</option>
                  <option value="tutorial">Tutorial</option>
                  <option value="timelapse">Timelapse</option>
                  <option value="technique">Technique</option>
                  <option value="critique">Critique</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Difficulty</label>
                <select v-model="form.difficulty" class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition bg-white">
                  <option value="">All levels</option>
                  <option value="beginner">Beginner</option>
                  <option value="intermediate">Intermediate</option>
                  <option value="advanced">Advanced</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Price ($)</label>
                <input v-model.number="form.price" type="number" min="0" step="0.01" :disabled="form.is_free" class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition disabled:opacity-50 disabled:bg-canvas-50" />
              </div>
              <div class="flex items-end gap-6">
                <label class="flex items-center gap-2 pb-2.5">
                  <input v-model="form.is_free" type="checkbox" class="w-4 h-4 rounded border-canvas-300 text-primary-500 focus:ring-primary-500" />
                  <span class="text-sm text-canvas-700">Free</span>
                </label>
                <label class="flex items-center gap-2 pb-2.5">
                  <input v-model="form.is_published" type="checkbox" class="w-4 h-4 rounded border-canvas-300 text-primary-500 focus:ring-primary-500" />
                  <span class="text-sm text-canvas-700">Published</span>
                </label>
                <label class="flex items-center gap-2 pb-2.5">
                  <input v-model="form.is_featured" type="checkbox" class="w-4 h-4 rounded border-canvas-300 text-primary-500 focus:ring-primary-500" />
                  <span class="text-sm text-canvas-700">Featured</span>
                </label>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-canvas-700 mb-1">Tags</label>
              <div class="flex flex-wrap gap-2 mb-2">
                <span
                  v-for="(tag, idx) in form.tags"
                  :key="idx"
                  class="inline-flex items-center gap-1 px-3 py-1 bg-primary-50 text-primary-700 rounded-full text-sm"
                >
                  {{ tag }}
                  <button type="button" @click="removeTag(idx)" class="hover:text-red-500">
                    <Icon name="mdi:close" class="w-3 h-3" />
                  </button>
                </span>
              </div>
              <input
                v-model="newTag"
                @keydown.enter.prevent="addTag"
                type="text"
                class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition"
                placeholder="Type a tag and press Enter"
              />
            </div>
          </div>
        </div>

        <div class="flex gap-4">
          <button
            type="submit"
            :disabled="submitting"
            class="flex-1 py-3 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 text-white font-semibold rounded-xl transition"
          >
            {{ submitting ? 'Saving...' : 'Update Video' }}
          </button>
          <NuxtLink
            to="/dashboard/videos"
            class="px-6 py-3 border border-canvas-200 text-canvas-700 font-medium rounded-xl hover:bg-canvas-100 transition"
          >
            Cancel
          </NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const route = useRoute()
const { authFetch, apiUrl } = useApi()
const videoId = route.params.id as string

const loading = ref(true)
const form = reactive({
  title: '',
  description: '',
  video_url: '',
  thumbnail_url: '',
  category: '',
  difficulty: '',
  price: null as number | null,
  is_free: false,
  is_published: false,
  is_featured: false,
  tags: [] as string[],
})

const newVideoFile = ref<File | null>(null)
const newVideoPreview = ref('')
const thumbnailFile = ref<File | null>(null)
const thumbnailPreview = ref('')
const newTag = ref('')
const submitting = ref(false)

onMounted(async () => {
  try {
    const video = await authFetch(`/videos/${videoId}`)
    Object.assign(form, {
      title: video.title,
      description: video.description || '',
      video_url: video.video_url,
      thumbnail_url: video.thumbnail_url || '',
      category: video.category || '',
      difficulty: video.difficulty || '',
      price: video.price,
      is_free: video.is_free,
      is_published: video.is_published,
      is_featured: video.is_featured,
      tags: video.tags || [],
    })
  } finally {
    loading.value = false
  }
})

const handleVideoUpload = (e: Event) => {
  const input = e.target as HTMLInputElement
  if (!input.files?.[0]) return
  newVideoFile.value = input.files[0]
  newVideoPreview.value = URL.createObjectURL(input.files[0])
}

const handleThumbnailUpload = (e: Event) => {
  const input = e.target as HTMLInputElement
  if (!input.files?.[0]) return
  thumbnailFile.value = input.files[0]
  thumbnailPreview.value = URL.createObjectURL(input.files[0])
}

const addTag = () => {
  const tag = newTag.value.trim()
  if (tag && !form.tags.includes(tag)) form.tags.push(tag)
  newTag.value = ''
}

const removeTag = (idx: number) => form.tags.splice(idx, 1)

const submitVideo = async () => {
  submitting.value = true
  try {
    const formData = new FormData()
    formData.append('title', form.title)
    formData.append('description', form.description || '')
    formData.append('category', form.category || '')
    formData.append('difficulty', form.difficulty || '')
    if (form.price) formData.append('price', String(form.price))
    formData.append('is_free', form.is_free ? '1' : '0')
    formData.append('is_published', form.is_published ? '1' : '0')
    formData.append('is_featured', form.is_featured ? '1' : '0')
    form.tags.forEach((tag, i) => formData.append(`tags[${i}]`, tag))
    if (newVideoFile.value) formData.append('video', newVideoFile.value)
    if (thumbnailFile.value) formData.append('thumbnail', thumbnailFile.value)

    await authFetch(`/dashboard/videos/${videoId}`, {
      method: 'POST',
      body: formData,
    })
    navigateTo('/dashboard/videos')
  } finally {
    submitting.value = false
  }
}

useHead({ title: 'Edit Video — Atelier Dashboard' })
</script>

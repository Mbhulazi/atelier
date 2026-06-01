<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="mb-8">
        <NuxtLink to="/dashboard/videos" class="text-sm text-canvas-500 hover:text-primary-600 transition flex items-center gap-1 mb-4">
          <Icon name="mdi:arrow-left" class="w-4 h-4" />
          Back to videos
        </NuxtLink>
        <h1 class="text-3xl font-serif font-bold text-canvas-900">Upload Video</h1>
      </div>

      <form @submit.prevent="submitVideo" class="space-y-8">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-canvas-900 mb-4">Video File</h2>
          <p class="text-sm text-canvas-500 mb-4">Upload MP4, AVI, or MOV. Max 500MB.</p>

          <label
            v-if="!videoPreview"
            class="block border-2 border-dashed border-canvas-300 hover:border-primary-400 rounded-2xl p-12 text-center cursor-pointer transition"
          >
            <Icon name="mdi:cloud-upload-outline" class="w-12 h-12 text-canvas-400 mx-auto mb-3" />
            <p class="text-canvas-600 font-medium">Click to upload video</p>
            <p class="text-sm text-canvas-400 mt-1">or drag and drop</p>
            <input type="file" accept="video/*" @change="handleVideoUpload" class="hidden" />
          </label>

          <div v-else class="relative">
            <video :src="videoPreview" controls class="w-full rounded-xl bg-canvas-900" />
            <button
              type="button"
              @click="removeVideo"
              class="absolute top-3 right-3 w-8 h-8 bg-black/50 hover:bg-red-500 rounded-full flex items-center justify-center text-white transition"
            >
              <Icon name="mdi:close" class="w-5 h-5" />
            </button>
          </div>
          <p v-if="errors.video" class="text-red-500 text-sm mt-2">{{ errors.video[0] }}</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-canvas-900 mb-4">Thumbnail</h2>
          <label class="block border-2 border-dashed border-canvas-300 hover:border-primary-400 rounded-xl p-8 text-center cursor-pointer transition">
            <img v-if="thumbnailPreview" :src="thumbnailPreview" class="max-h-40 mx-auto rounded-lg mb-2" />
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
              <input
                v-model="form.title"
                type="text"
                required
                class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition"
                placeholder="e.g., Oil Painting Basics: Color Mixing"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-canvas-700 mb-1">Description</label>
              <textarea
                v-model="form.description"
                rows="4"
                class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition resize-none"
                placeholder="What will viewers learn in this video?"
              />
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
                <input
                  v-model.number="form.price"
                  type="number"
                  min="0"
                  step="0.01"
                  :disabled="form.is_free"
                  class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition disabled:opacity-50 disabled:bg-canvas-50"
                  placeholder="0.00"
                />
              </div>
              <div class="flex items-end">
                <label class="flex items-center gap-2 pb-2.5">
                  <input v-model="form.is_free" type="checkbox" class="w-4 h-4 rounded border-canvas-300 text-primary-500 focus:ring-primary-500" />
                  <span class="text-sm text-canvas-700">This video is free</span>
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
            {{ submitting ? 'Uploading...' : 'Upload Video' }}
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

const config = useRuntimeConfig()
const apiUrl = config.public.apiUrl

const form = reactive({
  title: '',
  description: '',
  category: '',
  difficulty: '',
  price: null as number | null,
  is_free: false,
  tags: [] as string[],
})

const videoFile = ref<File | null>(null)
const videoPreview = ref('')
const thumbnailFile = ref<File | null>(null)
const thumbnailPreview = ref('')
const newTag = ref('')
const submitting = ref(false)
const errors = ref<Record<string, string[]>>({})

const handleVideoUpload = (e: Event) => {
  const input = e.target as HTMLInputElement
  if (!input.files?.[0]) return
  videoFile.value = input.files[0]
  videoPreview.value = URL.createObjectURL(input.files[0])
}

const removeVideo = () => {
  if (videoPreview.value) URL.revokeObjectURL(videoPreview.value)
  videoFile.value = null
  videoPreview.value = ''
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
  if (!videoFile.value) {
    errors.value = { video: ['Video file is required'] }
    return
  }

  submitting.value = true
  errors.value = {}

  try {
    const formData = new FormData()
    formData.append('video', videoFile.value)
    formData.append('title', form.title)
    if (form.description) formData.append('description', form.description)
    if (form.category) formData.append('category', form.category)
    if (form.difficulty) formData.append('difficulty', form.difficulty)
    if (form.price) formData.append('price', String(form.price))
    formData.append('is_free', form.is_free ? '1' : '0')
    form.tags.forEach((tag, i) => formData.append(`tags[${i}]`, tag))
    if (thumbnailFile.value) formData.append('thumbnail', thumbnailFile.value)

    await $fetch(`${apiUrl}/dashboard/videos`, {
      method: 'POST',
      body: formData,
      credentials: 'include',
    })

    navigateTo('/dashboard/videos')
  } catch (e: any) {
    if (e.data?.errors) errors.value = e.data.errors
  } finally {
    submitting.value = false
  }
}

useHead({ title: 'Upload Video — Atelier Dashboard' })
</script>

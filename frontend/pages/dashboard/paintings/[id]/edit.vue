<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="mb-8">
        <NuxtLink to="/dashboard/paintings" class="text-sm text-canvas-500 hover:text-primary-600 transition flex items-center gap-1 mb-4">
          <Icon name="mdi:arrow-left" class="w-4 h-4" />
          Back to paintings
        </NuxtLink>
        <h1 class="text-3xl font-serif font-bold text-canvas-900">Edit Painting</h1>
      </div>

      <div v-if="loading" class="bg-white rounded-2xl p-6 shadow-sm animate-pulse space-y-4">
        <div class="h-40 bg-canvas-200 rounded" />
        <div class="h-8 bg-canvas-200 rounded w-1/3" />
        <div class="h-4 bg-canvas-200 rounded w-2/3" />
      </div>

      <form v-else @submit.prevent="submitPainting" class="space-y-8">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-canvas-900 mb-4">Images</h2>
          <p class="text-sm text-canvas-500 mb-4">Add more images or reorder existing ones.</p>
          
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            <div
              v-for="(image, idx) in allImages"
              :key="image.id || idx"
              class="relative aspect-square rounded-xl overflow-hidden bg-canvas-100 group"
            >
              <img :src="image.preview || `${apiUrl}/storage/${image.image_url}`" class="w-full h-full object-cover" />
              <button
                type="button"
                @click="removeImage(idx)"
                class="absolute top-2 right-2 w-7 h-7 bg-black/50 hover:bg-red-500 rounded-full flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition"
              >
                <Icon name="mdi:close" class="w-4 h-4" />
              </button>
              <div v-if="idx === 0" class="absolute bottom-2 left-2 bg-primary-500 text-white text-xs px-2 py-0.5 rounded-full">
                Cover
              </div>
            </div>

            <label
              v-if="allImages.length < 10"
              class="aspect-square rounded-xl border-2 border-dashed border-canvas-300 hover:border-primary-400 flex flex-col items-center justify-center cursor-pointer transition"
            >
              <Icon name="mdi:camera-plus-outline" class="w-8 h-8 text-canvas-400 mb-2" />
              <span class="text-xs text-canvas-500">Add Image</span>
              <input
                type="file"
                accept="image/*"
                multiple
                @change="handleImageUpload"
                class="hidden"
              />
            </label>
          </div>
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
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-canvas-700 mb-1">Description</label>
              <textarea
                v-model="form.description"
                rows="3"
                class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition resize-none"
              />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Medium</label>
                <select v-model="form.medium" class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition bg-white">
                  <option value="">Select medium</option>
                  <option v-for="m in mediums" :key="m" :value="m">{{ m }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Style</label>
                <select v-model="form.style" class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition bg-white">
                  <option value="">Select style</option>
                  <option v-for="s in styles" :key="s" :value="s">{{ s }}</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-5">
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Width (cm)</label>
                <input v-model.number="form.width" type="number" min="0" step="0.1" class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition" />
              </div>
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Height (cm)</label>
                <input v-model.number="form.height" type="number" min="0" step="0.1" class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition" />
              </div>
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Depth (cm)</label>
                <input v-model.number="form.depth" type="number" min="0" step="0.1" class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition" />
              </div>
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Year</label>
                <input v-model.number="form.year_created" type="number" min="1900" :max="new Date().getFullYear()" class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition" />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Price ($)</label>
                <input v-model.number="form.price" type="number" min="0" step="0.01" class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition" />
              </div>
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Currency</label>
                <select v-model="form.currency" class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition bg-white">
                  <option value="USD">USD</option>
                  <option value="EUR">EUR</option>
                  <option value="GBP">GBP</option>
                </select>
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

            <div class="flex items-center gap-6">
              <label class="flex items-center gap-2">
                <input v-model="form.is_available" type="checkbox" class="w-4 h-4 rounded border-canvas-300 text-primary-500 focus:ring-primary-500" />
                <span class="text-sm text-canvas-700">Available for sale</span>
              </label>
              <label class="flex items-center gap-2">
                <input v-model="form.is_featured" type="checkbox" class="w-4 h-4 rounded border-canvas-300 text-primary-500 focus:ring-primary-500" />
                <span class="text-sm text-canvas-700">Featured</span>
              </label>
            </div>
          </div>
        </div>

        <div class="flex gap-4">
          <button
            type="submit"
            :disabled="submitting"
            class="flex-1 py-3 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 text-white font-semibold rounded-xl transition"
          >
            {{ submitting ? 'Saving...' : 'Update Painting' }}
          </button>
          <NuxtLink
            to="/dashboard/paintings"
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
const paintingId = route.params.id as string

const mediums = ['Oil', 'Acrylic', 'Watercolor', 'Gouache', 'Pastel', 'Mixed Media', 'Ink', 'Other']
const styles = ['Abstract', 'Realism', 'Impressionism', 'Expressionism', 'Surrealism', 'Pop Art', 'Minimalist', 'Other']

const loading = ref(true)
const form = reactive({
  title: '',
  description: '',
  medium: '',
  style: '',
  width: null as number | null,
  height: null as number | null,
  depth: null as number | null,
  price: null as number | null,
  currency: 'USD',
  year_created: null as number | null,
  tags: [] as string[],
  is_available: true,
  is_featured: false,
})

const existingImages = ref<any[]>([])
const newImages = ref<File[]>([])
const newImagePreviews = ref<string[]>([])
const removedImageIds = ref<number[]>([])
const newTag = ref('')
const submitting = ref(false)

const allImages = computed(() => {
  return [
    ...existingImages.value.filter(img => !removedImageIds.value.includes(img.id)),
    ...newImages.value.map((file, i) => ({ id: `new-${i}`, preview: newImagePreviews.value[i], file })),
  ]
})

onMounted(async () => {
  try {
    const painting = await authFetch(`/paintings/${paintingId}`)
    Object.assign(form, {
      title: painting.title,
      description: painting.description || '',
      medium: painting.medium || '',
      style: painting.style || '',
      width: painting.width,
      height: painting.height,
      depth: painting.depth,
      price: painting.price,
      currency: painting.currency || 'USD',
      year_created: painting.year_created,
      tags: painting.tags || [],
      is_available: painting.is_available,
      is_featured: painting.is_featured,
    })
    existingImages.value = painting.images || []
  } finally {
    loading.value = false
  }
})

const handleImageUpload = (e: Event) => {
  const input = e.target as HTMLInputElement
  if (!input.files) return
  for (const file of Array.from(input.files)) {
    if (newImages.value.length + existingImages.value.length - removedImageIds.value.length >= 10) break
    newImages.value.push(file)
    newImagePreviews.value.push(URL.createObjectURL(file))
  }
  input.value = ''
}

const removeImage = (idx: number) => {
  const existing = existingImages.value.filter(img => !removedImageIds.value.includes(img.id))
  if (idx < existing.length) {
    removedImageIds.value.push(existing[idx].id)
  } else {
    const newIdx = idx - existing.length
    URL.revokeObjectURL(newImagePreviews.value[newIdx])
    newImages.value.splice(newIdx, 1)
    newImagePreviews.value.splice(newIdx, 1)
  }
}

const addTag = () => {
  const tag = newTag.value.trim()
  if (tag && !form.tags.includes(tag)) form.tags.push(tag)
  newTag.value = ''
}

const removeTag = (idx: number) => form.tags.splice(idx, 1)

const submitPainting = async () => {
  submitting.value = true
  try {
    const formData = new FormData()
    formData.append('title', form.title)
    formData.append('description', form.description || '')
    formData.append('medium', form.medium || '')
    formData.append('style', form.style || '')
    if (form.width) formData.append('width', String(form.width))
    if (form.height) formData.append('height', String(form.height))
    if (form.depth) formData.append('depth', String(form.depth))
    if (form.price) formData.append('price', String(form.price))
    formData.append('currency', form.currency)
    if (form.year_created) formData.append('year_created', String(form.year_created))
    form.tags.forEach((tag, i) => formData.append(`tags[${i}]`, tag))
    formData.append('is_available', form.is_available ? '1' : '0')
    formData.append('is_featured', form.is_featured ? '1' : '0')
    removedImageIds.value.forEach(id => formData.append(`removed_images[]`, String(id)))
    newImages.value.forEach(img => formData.append('images[]', img))

    await authFetch(`/dashboard/paintings/${paintingId}`, {
      method: 'POST',
      body: formData,
    })
    navigateTo('/dashboard/paintings')
  } finally {
    submitting.value = false
  }
}

useHead({ title: 'Edit Painting — Atelier Dashboard' })
</script>

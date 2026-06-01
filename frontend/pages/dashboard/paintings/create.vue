<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="mb-8">
        <NuxtLink to="/dashboard/paintings" class="text-sm text-canvas-500 hover:text-primary-600 transition flex items-center gap-1 mb-4">
          <Icon name="mdi:arrow-left" class="w-4 h-4" />
          Back to paintings
        </NuxtLink>
        <h1 class="text-3xl font-serif font-bold text-canvas-900">Add New Painting</h1>
      </div>

      <form @submit.prevent="submitPainting" class="space-y-8">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-canvas-900 mb-4">Images</h2>
          <p class="text-sm text-canvas-500 mb-4">Upload up to 10 images. First image will be the cover.</p>
          
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            <div
              v-for="(preview, idx) in imagePreviews"
              :key="idx"
              class="relative aspect-square rounded-xl overflow-hidden bg-canvas-100 group"
            >
              <img :src="preview" class="w-full h-full object-cover" />
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
              v-if="imagePreviews.length < 10"
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
          <p v-if="errors.images" class="text-red-500 text-sm mt-2">{{ errors.images[0] }}</p>
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
                placeholder="e.g., Sunset Over the Valley"
              />
              <p v-if="errors.title" class="text-red-500 text-sm mt-1">{{ errors.title[0] }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-canvas-700 mb-1">Description</label>
              <textarea
                v-model="form.description"
                rows="3"
                class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition resize-none"
                placeholder="Describe your painting, inspiration, technique..."
              />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Medium</label>
                <select
                  v-model="form.medium"
                  class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition bg-white"
                >
                  <option value="">Select medium</option>
                  <option v-for="m in mediums" :key="m" :value="m">{{ m }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Style</label>
                <select
                  v-model="form.style"
                  class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition bg-white"
                >
                  <option value="">Select style</option>
                  <option v-for="s in styles" :key="s" :value="s">{{ s }}</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-5">
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Width (cm)</label>
                <input
                  v-model.number="form.width"
                  type="number"
                  min="0"
                  step="0.1"
                  class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Height (cm)</label>
                <input
                  v-model.number="form.height"
                  type="number"
                  min="0"
                  step="0.1"
                  class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Depth (cm)</label>
                <input
                  v-model.number="form.depth"
                  type="number"
                  min="0"
                  step="0.1"
                  class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Year</label>
                <input
                  v-model.number="form.year_created"
                  type="number"
                  min="1900"
                  :max="new Date().getFullYear()"
                  class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition"
                />
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
                  class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition"
                  placeholder="0.00"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-canvas-700 mb-1">Currency</label>
                <select
                  v-model="form.currency"
                  class="w-full px-4 py-2.5 rounded-xl border border-canvas-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition bg-white"
                >
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
          </div>
        </div>

        <div class="flex gap-4">
          <button
            type="submit"
            :disabled="submitting"
            class="flex-1 py-3 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 text-white font-semibold rounded-xl transition"
          >
            {{ submitting ? 'Saving...' : 'Save Painting' }}
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

const { authFetch, apiUrl } = useApi()

const mediums = ['Oil', 'Acrylic', 'Watercolor', 'Gouache', 'Pastel', 'Mixed Media', 'Ink', 'Other']
const styles = ['Abstract', 'Realism', 'Impressionism', 'Expressionism', 'Surrealism', 'Pop Art', 'Minimalist', 'Other']

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
})

const images = ref<File[]>([])
const imagePreviews = ref<string[]>([])
const newTag = ref('')
const submitting = ref(false)
const errors = ref<Record<string, string[]>>({})

const handleImageUpload = (e: Event) => {
  const input = e.target as HTMLInputElement
  if (!input.files) return

  for (const file of Array.from(input.files)) {
    if (images.value.length >= 10) break
    images.value.push(file)
    imagePreviews.value.push(URL.createObjectURL(file))
  }
  input.value = ''
}

const removeImage = (idx: number) => {
  URL.revokeObjectURL(imagePreviews.value[idx])
  images.value.splice(idx, 1)
  imagePreviews.value.splice(idx, 1)
}

const addTag = () => {
  const tag = newTag.value.trim()
  if (tag && !form.tags.includes(tag)) {
    form.tags.push(tag)
  }
  newTag.value = ''
}

const removeTag = (idx: number) => {
  form.tags.splice(idx, 1)
}

const submitPainting = async () => {
  if (!images.value.length) {
    errors.value = { images: ['At least one image is required'] }
    return
  }

  submitting.value = true
  errors.value = {}

  try {
    const formData = new FormData()
    formData.append('title', form.title)
    if (form.description) formData.append('description', form.description)
    if (form.medium) formData.append('medium', form.medium)
    if (form.style) formData.append('style', form.style)
    if (form.width) formData.append('width', String(form.width))
    if (form.height) formData.append('height', String(form.height))
    if (form.depth) formData.append('depth', String(form.depth))
    if (form.price) formData.append('price', String(form.price))
    formData.append('currency', form.currency)
    if (form.year_created) formData.append('year_created', String(form.year_created))
    form.tags.forEach((tag, i) => formData.append(`tags[${i}]`, tag))
    images.value.forEach((img) => formData.append('images[]', img))

    await authFetch('/dashboard/paintings', {
      method: 'POST',
      body: formData,
    })

    navigateTo('/dashboard/paintings')
  } catch (e: any) {
    if (e.data?.errors) {
      errors.value = e.data.errors
    }
  } finally {
    submitting.value = false
  }
}

useHead({ title: 'Add Painting — Atelier Dashboard' })
</script>

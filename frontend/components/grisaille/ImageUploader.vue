<template>
  <div
    class="relative border-2 border-dashed rounded-2xl p-12 text-center transition"
    :class="[
      isDragging
        ? 'border-primary-500 bg-primary-50'
        : 'border-canvas-300 hover:border-primary-400 bg-canvas-50',
    ]"
    @dragover.prevent="isDragging = true"
    @dragleave="isDragging = false"
    @drop.prevent="handleDrop"
  >
    <input
      ref="fileInput"
      type="file"
      accept="image/*"
      class="hidden"
      @change="handleFileSelect"
    />

    <div v-if="!preview" class="space-y-4">
      <div class="w-16 h-16 mx-auto bg-primary-100 rounded-full flex items-center justify-center">
        <Icon name="mdi:cloud-upload" class="w-8 h-8 text-primary-600" />
      </div>
      <div>
        <p class="text-lg font-medium text-canvas-700">Drop your image here</p>
        <p class="text-sm text-canvas-500 mt-1">
          or
          <button @click="$refs.fileInput.click()" class="text-primary-600 font-medium hover:text-primary-700">
            browse
          </button>
          to choose a file
        </p>
      </div>
      <p class="text-xs text-canvas-400">Supports JPG, PNG, WebP. Max 20MB.</p>
    </div>

    <div v-else class="relative">
      <img :src="preview" alt="Uploaded image" class="max-h-96 mx-auto rounded-xl shadow-lg" />
      <button
        @click="clearImage"
        class="absolute top-3 right-3 w-10 h-10 bg-white rounded-full shadow-lg flex items-center justify-center hover:bg-canvas-100 transition"
      >
        <Icon name="mdi:close" class="w-5 h-5 text-canvas-600" />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
const emit = defineEmits<{
  (e: 'select', file: File): void
  (e: 'clear'): void
}>()

const fileInput = ref<HTMLInputElement>()
const isDragging = ref(false)
const preview = ref<string | null>(null)

const handleFileSelect = (event: Event) => {
  const input = event.target as HTMLInputElement
  if (input.files?.[0]) processFile(input.files[0])
}

const handleDrop = (event: DragEvent) => {
  isDragging.value = false
  if (event.dataTransfer?.files?.[0]) processFile(event.dataTransfer.files[0])
}

const processFile = (file: File) => {
  if (file.size > 20 * 1024 * 1024) { alert('File too large. Max 20MB.'); return }
  if (!file.type.startsWith('image/')) { alert('Please upload an image.'); return }

  const reader = new FileReader()
  reader.onload = (e) => { preview.value = e.target?.result as string }
  reader.readAsDataURL(file)
  emit('select', file)
}

const clearImage = () => {
  preview.value = null
  if (fileInput.value) fileInput.value.value = ''
  emit('clear')
}
</script>

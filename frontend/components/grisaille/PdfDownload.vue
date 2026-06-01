<template>
  <div class="flex items-center gap-4">
    <button
      @click="downloadPdf"
      :disabled="generating"
      class="flex items-center gap-2 px-6 py-3 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition disabled:opacity-50"
    >
      <Icon :name="generating ? 'mdi:loading' : 'mdi:file-pdf-box'" :class="['w-5 h-5', { 'animate-spin': generating }]" />
      {{ generating ? 'Generating PDF...' : 'Download PDF Report' }}
    </button>
    <span v-if="pdfUrl" class="text-sm text-canvas-500">
      PDF ready! <a :href="pdfUrl" target="_blank" class="text-primary-600 underline">Open</a>
    </span>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ analysisId: number }>()
const config = useRuntimeConfig()
const generating = ref(false)
const pdfUrl = ref<string | null>(null)

const downloadPdf = async () => {
  generating.value = true
  try {
    const response = await $fetch<{ url: string }>(
      `${config.public.apiUrl}/grisaille/${props.analysisId}/pdf`,
      { withCredentials: true }
    )
    pdfUrl.value = response.url
    window.open(response.url, '_blank')
  } catch (error) {
    console.error('PDF generation failed:', error)
  } finally {
    generating.value = false
  }
}
</script>

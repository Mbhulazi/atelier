<template>
  <div class="min-h-screen bg-canvas-50 pt-20 pb-16">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="text-center mb-12">
        <h1 class="text-4xl font-serif font-bold text-canvas-900">Grisaille Value Analysis</h1>
        <p class="mt-3 text-lg text-canvas-600 max-w-2xl mx-auto">
          Upload a reference photo to analyze its value structure, get palette recommendations,
          and receive glazing suggestions for your painting.
        </p>
      </div>

      <!-- Upload Area -->
      <GrisailleImageUploader v-if="!analysisResult" @select="handleFileSelect" />

      <!-- Results -->
      <div v-if="analysisResult" class="space-y-8">
        <div class="bg-white rounded-2xl shadow-sm p-6">
          <GrisailleValuePreview
            :original-url="originalPreviewUrl"
            :preview-url="analysisResult.preview"
          />
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6">
          <GrisailleValueHistogram :value-percentages="analysisResult.analysis.valuePercentages" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="bg-white rounded-2xl shadow-sm p-6">
            <GrisaillePaletteRecommendation :recommendations="analysisResult.palette" />
          </div>
          <div class="bg-white rounded-2xl shadow-sm p-6">
            <GrisailleGlazingSuggestions :suggestions="analysisResult.glazings" />
          </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-4 bg-white rounded-2xl shadow-sm p-6">
          <button
            @click="resetTool"
            class="px-6 py-3 text-canvas-700 border border-canvas-300 rounded-lg font-medium hover:border-primary-400 transition"
          >
            Analyze Another Image
          </button>
          <GrisaillePdfDownload v-if="savedAnalysisId" :analysis-id="savedAnalysisId" />
        </div>
      </div>

      <!-- Loading -->
      <div v-if="processing" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 text-center shadow-2xl">
          <div class="w-16 h-16 border-4 border-primary-200 border-t-primary-600 rounded-full animate-spin mx-auto" />
          <p class="mt-4 text-lg font-medium text-canvas-700">Analyzing values...</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'default' })

const { processImage, processing } = useGrisaille()
const { authFetch } = useApi()

const analysisResult = ref<{
  analysis: any
  preview: string
  palette: any[]
  glazings: any[]
} | null>(null)

const originalPreviewUrl = ref('')
const savedAnalysisId = ref<number | null>(null)

const handleFileSelect = async (file: File) => {
  const reader = new FileReader()
  reader.onload = (e) => { originalPreviewUrl.value = e.target?.result as string }
  reader.readAsDataURL(file)

  const result = await processImage(file)
  analysisResult.value = result

  try {
    const formData = new FormData()
    formData.append('image', file)
    formData.append('value_map', JSON.stringify(result.analysis.valueMap))
    formData.append('value_percentages', JSON.stringify(result.analysis.valuePercentages))
    formData.append('palette', JSON.stringify(result.palette))
    formData.append('glazings', JSON.stringify(result.glazings))

    const response = await authFetch<{ id: number }>(
      '/grisaille/analyze',
      { method: 'POST', body: formData }
    )
    savedAnalysisId.value = response.id
  } catch (error) {
    console.error('Failed to save analysis:', error)
  }
}

const resetTool = () => {
  analysisResult.value = null
  originalPreviewUrl.value = ''
  savedAnalysisId.value = null
}
</script>

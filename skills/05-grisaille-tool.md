# Skill 05: Grisaille Tool

## Overview
Build the flagship Grisaille analysis tool: upload a photo, get instant 0-9 value mapping (client-side), palette recommendations, glazing suggestions, and downloadable PDF (server-side).

---

## Architecture

```
Client (Nuxt/Canvas)                    Server (Laravel)
┌─────────────────────┐                ┌─────────────────────┐
│ Upload Image        │                │ Receive Image       │
│         ↓           │                │         ↓           │
│ Canvas API          │   POST image   │ Intervention Image  │
│ getImageData()      │ ──────────────→│ Process + Store     │
│         ↓           │                │         ↓           │
│ Map RGB → 0-9       │                │ Generate PDF        │
│ Live Preview        │   ←──────────  │ (DomPDF)            │
│ Value Histogram     │   PDF URL      │         ↓           │
│ Palette + Glazing   │                │ Store PDF in S3     │
│ (instant, client)   │                │         ↓           │
└─────────────────────┘                │ Return Analysis     │
                                       └─────────────────────┘
```

---

## Step 1: Grisaille Service (Client-Side)

### `composables/useGrisaille.ts`

```typescript
export interface ValueAnalysis {
  valueMap: number[]        // pixel count per value 0-9
  valuePercentages: number[] // normalized percentages
  totalPixels: number
  averageValue: number
}

export interface PaletteRecommendation {
  valueRange: string        // "0-2", "3-5", "6-8", "9"
  label: string             // "Darks", "Midtones", "Lights", "Highlights"
  colors: PaletteColor[]
}

export interface PaletteColor {
  name: string
  pigment: string
  hex: string
  valueRange: [number, number]
}

export interface GlazingSuggestion {
  fromValue: number
  toValue: number
  glaze: string
  medium: string
  technique: string
  description: string
}

export const useGrisaille = () => {
  const canvas = ref<HTMLCanvasElement | null>(null)
  const ctx = ref<CanvasRenderingContext2D | null>(null)
  const analysis = ref<ValueAnalysis | null>(null)
  const previewUrl = ref<string | null>(null)
  const processing = ref(false)

  // ITU-R BT.601 luminance coefficients
  const calculateLuminance = (r: number, g: number, b: number): number => {
    return 0.299 * r + 0.587 * g + 0.114 * b
  }

  // Map luminance (0-255) to value bucket (0-9)
  const luminanceToValue = (luminance: number): number => {
    return Math.min(9, Math.floor(luminance / 25.6))
  }

  // Analyze image and return value distribution
  const analyzeImage = (imageData: ImageData): ValueAnalysis => {
    const pixels = imageData.data
    const valueMap = new Array(10).fill(0)
    const totalPixels = pixels.length / 4

    for (let i = 0; i < pixels.length; i += 4) {
      const r = pixels[i]
      const g = pixels[i + 1]
      const b = pixels[i + 2]
      const luminance = calculateLuminance(r, g, b)
      const value = luminanceToValue(luminance)
      valueMap[value]++
    }

    const valuePercentages = valueMap.map(count => count / totalPixels)
    const averageValue = valuePercentages.reduce(
      (sum, pct, i) => sum + pct * i, 0
    )

    return {
      valueMap,
      valuePercentages,
      totalPixels,
      averageValue,
    }
  }

  // Generate grayscale preview with value overlay
  const generatePreview = (
    imageData: ImageData,
    canvasEl: HTMLCanvasElement
  ): string => {
    const tempCanvas = document.createElement('canvas')
    tempCanvas.width = imageData.width
    tempCanvas.height = imageData.height
    const tempCtx = tempCanvas.getContext('2d')!

    const output = tempCtx.createImageData(imageData.width, imageData.height)
    const pixels = imageData.data

    for (let i = 0; i < pixels.length; i += 4) {
      const r = pixels[i]
      const g = pixels[i + 1]
      const b = pixels[i + 2]
      const luminance = calculateLuminance(r, g, b)
      const value = luminanceToValue(luminance)

      // Map value to grayscale (0 = black, 9 = white)
      const gray = Math.round(value * 255 / 9)

      output.data[i] = gray
      output.data[i + 1] = gray
      output.data[i + 2] = gray
      output.data[i + 3] = 255
    }

    tempCtx.putImageData(output, 0, 0)
    return tempCanvas.toDataURL('image/png')
  }

  // Process uploaded image
  const processImage = async (file: File): Promise<{
    analysis: ValueAnalysis
    preview: string
    palette: PaletteRecommendation[]
    glazings: GlazingSuggestion[]
  }> => {
    processing.value = true

    try {
      const img = await loadImage(file)
      const canvasEl = document.createElement('canvas')
      canvasEl.width = img.width
      canvasEl.height = img.height
      const canvasCtx = canvasEl.getContext('2d')!
      canvasCtx.drawImage(img, 0, 0)

      const imageData = canvasCtx.getImageData(0, 0, canvasEl.width, canvasEl.height)

      // Client-side analysis
      const valueAnalysis = analyzeImage(imageData)
      const preview = generatePreview(imageData, canvasEl)

      // Client-side palette and glazing
      const palette = generatePaletteRecommendations(valueAnalysis)
      const glazings = generateGlazingSuggestions(valueAnalysis)

      analysis.value = valueAnalysis
      previewUrl.value = preview

      return {
        analysis: valueAnalysis,
        preview,
        palette,
        glazings,
      }
    } finally {
      processing.value = false
    }
  }

  // Load image file to HTMLImageElement
  const loadImage = (file: File): Promise<HTMLImageElement> => {
    return new Promise((resolve, reject) => {
      const img = new Image()
      img.onload = () => resolve(img)
      img.onerror = reject
      img.src = URL.createObjectURL(file)
    })
  }

  // Generate palette based on value analysis
  const generatePaletteRecommendations = (
    valueAnalysis: ValueAnalysis
  ): PaletteRecommendation[] => {
    const { valuePercentages } = valueAnalysis

    // Dark pigments (values 0-2)
    const darkPigments: PaletteColor[] = [
      { name: 'Bone Black', pigment: 'PBk9', hex: '#1a1a1a', valueRange: [0, 2] },
      { name: 'Burnt Umber', pigment: 'PBr7', hex: '#3d2b1f', valueRange: [0, 2] },
      { name: 'Raw Umber', pigment: 'PBr7', hex: '#4a3728', valueRange: [0, 2] },
      { name: 'Van Dyke Brown', pigment: 'PBr7', hex: '#3e2723', valueRange: [0, 2] },
    ]

    // Midtone pigments (values 3-5)
    const midtonePigments: PaletteColor[] = [
      { name: 'Yellow Ochre', pigment: 'PY43', hex: '#c8a951', valueRange: [3, 5] },
      { name: 'Cadmium Orange', pigment: 'PO20', hex: '#e87e04', valueRange: [3, 5] },
      { name: 'Transparent Oxide Red', pigment: 'PBr6', hex: '#8b4513', valueRange: [3, 5] },
      { name: 'Raw Sienna', pigment: 'PBr7', hex: '#a0522d', valueRange: [3, 5] },
    ]

    // Light pigments (values 6-8)
    const lightPigments: PaletteColor[] = [
      { name: 'Titanium White + Cad Yellow', pigment: 'PW6 + PY35', hex: '#f5e6a3', valueRange: [6, 8] },
      { name: 'Titanium White + Yellow Ochre', pigment: 'PW6 + PY43', hex: '#e8d5a3', valueRange: [6, 8] },
      { name: 'Naples Yellow', pigment: 'PY41', hex: '#f0e4a0', valueRange: [6, 8] },
      { name: 'Buff Titanium', pigment: 'PBr24', hex: '#d4c5a0', valueRange: [6, 8] },
    ]

    // Highlight (value 9)
    const highlightPigments: PaletteColor[] = [
      { name: 'Titanium White', pigment: 'PW6', hex: '#f5f5f5', valueRange: [9, 9] },
      { name: 'Zinc White', pigment: 'PW4', hex: '#f0f0f0', valueRange: [9, 9] },
    ]

    // Weight recommendations by actual value distribution
    const darkWeight = valuePercentages.slice(0, 3).reduce((a, b) => a + b, 0)
    const midWeight = valuePercentages.slice(3, 6).reduce((a, b) => a + b, 0)
    const lightWeight = valuePercentages.slice(6, 9).reduce((a, b) => a + b, 0)
    const highlightWeight = valuePercentages[9] || 0

    return [
      {
        valueRange: '0-2',
        label: `Darks (${Math.round(darkWeight * 100)}% of image)`,
        colors: darkPigments,
      },
      {
        valueRange: '3-5',
        label: `Midtones (${Math.round(midWeight * 100)}% of image)`,
        colors: midtonePigments,
      },
      {
        valueRange: '6-8',
        label: `Lights (${Math.round(lightWeight * 100)}% of image)`,
        colors: lightPigments,
      },
      {
        valueRange: '9',
        label: `Highlights (${Math.round(highlightWeight * 100)}% of image)`,
        colors: highlightPigments,
      },
    ]
  }

  // Generate glazing suggestions
  const generateGlazingSuggestions = (
    valueAnalysis: ValueAnalysis
  ): GlazingSuggestion[] => {
    return [
      {
        fromValue: 0,
        toValue: 3,
        glaze: 'Diluted Burnt Umber',
        medium: 'Odorless Mineral Spirits + Linseed Oil (3:1)',
        technique: 'Thin wash',
        description: 'Establish dark foundations with transparent Burnt Umber wash. Keep thin to allow underpainting to show through.',
      },
      {
        fromValue: 3,
        toValue: 5,
        glaze: 'Yellow Ochre + Transparent Oxide',
        medium: 'Linseed Oil',
        technique: 'Scumbling',
        description: 'Build midtones with semi-opaque Yellow Ochre. Use dry brush technique for broken color effects.',
      },
      {
        fromValue: 5,
        toValue: 7,
        glaze: 'Cadmium Yellow + White',
        medium: 'Liquin or Stand Oil',
        technique: 'Wet glaze',
        description: 'Add warmth to lights with a thin Cad Yellow tint. Blend edges while wet for smooth transitions.',
      },
      {
        fromValue: 7,
        toValue: 9,
        glaze: 'Titanium White + touch of Yellow',
        medium: 'Stand Oil',
        technique: 'Scumble over dry',
        description: 'Final highlights with nearly pure Titanium White. Apply sparingly over dry underlayers.',
      },
    ]
  }

  return {
    analysis,
    previewUrl,
    processing,
    processImage,
    analyzeImage,
    luminanceToValue,
    calculateLuminance,
  }
}
```

---

## Step 2: Upload Component

### `components/grisaille/ImageUploader.vue`

```vue
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
        <p class="text-lg font-medium text-canvas-700">
          Drop your image here
        </p>
        <p class="text-sm text-canvas-500 mt-1">
          or
          <button
            @click="$refs.fileInput.click()"
            class="text-primary-600 font-medium hover:text-primary-700"
          >
            browse
          </button>
          to choose a file
        </p>
      </div>
      <p class="text-xs text-canvas-400">
        Supports JPG, PNG, WebP. Max 20MB.
      </p>
    </div>

    <div v-else class="relative">
      <img
        :src="preview"
        alt="Uploaded image"
        class="max-h-96 mx-auto rounded-xl shadow-lg"
      />
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
  if (input.files?.[0]) {
    processFile(input.files[0])
  }
}

const handleDrop = (event: DragEvent) => {
  isDragging.value = false
  if (event.dataTransfer?.files?.[0]) {
    processFile(event.dataTransfer.files[0])
  }
}

const processFile = (file: File) => {
  if (file.size > 20 * 1024 * 1024) {
    alert('File too large. Max 20MB.')
    return
  }

  if (!file.type.startsWith('image/')) {
    alert('Please upload an image file.')
    return
  }

  const reader = new FileReader()
  reader.onload = (e) => {
    preview.value = e.target?.result as string
  }
  reader.readAsDataURL(file)

  emit('select', file)
}

const clearImage = () => {
  preview.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
  emit('clear')
}
</script>
```

---

## Step 3: Value Preview Component

### `components/grisaille/ValuePreview.vue`

```vue
<template>
  <div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Original -->
      <div>
        <h4 class="text-sm font-medium text-canvas-700 mb-2">Original</h4>
        <div class="rounded-xl overflow-hidden bg-canvas-100 aspect-square">
          <img
            :src="originalUrl"
            alt="Original"
            class="w-full h-full object-contain"
          />
        </div>
      </div>

      <!-- Value Mapped -->
      <div>
        <h4 class="text-sm font-medium text-canvas-700 mb-2">Value Map (0-9)</h4>
        <div class="rounded-xl overflow-hidden bg-canvas-100 aspect-square">
          <img
            :src="previewUrl"
            alt="Value preview"
            class="w-full h-full object-contain"
          />
        </div>
      </div>
    </div>

    <!-- Value Scale Legend -->
    <div class="flex items-center gap-2">
      <span class="text-xs text-canvas-500">0 (Black)</span>
      <div class="flex-1 flex gap-0.5">
        <div
          v-for="i in 10"
          :key="i"
          class="flex-1 h-6 first:rounded-l last:rounded-r"
          :style="{ background: `rgb(${(i-1)*28.3}, ${(i-1)*28.3}, ${(i-1)*28.3})` }"
          :title="`Value ${i-1}`"
        />
      </div>
      <span class="text-xs text-canvas-500">9 (White)</span>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  originalUrl: string
  previewUrl: string
}>()
</script>
```

---

## Step 4: Value Histogram

### `components/grisaille/ValueHistogram.vue`

```vue
<template>
  <div>
    <h4 class="text-sm font-medium text-canvas-700 mb-4">Value Distribution</h4>
    <div class="relative h-48">
      <canvas ref="chartCanvas" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

const props = defineProps<{
  valuePercentages: number[]
}>()

const chartCanvas = ref<HTMLCanvasElement>()
let chart: Chart | null = null

onMounted(() => {
  if (chartCanvas.value) {
    chart = new Chart(chartCanvas.value, {
      type: 'bar',
      data: {
        labels: ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        datasets: [{
          label: 'Percentage',
          data: props.valuePercentages.map(p => p * 100),
          backgroundColor: props.valuePercentages.map((_, i) => {
            const gray = Math.round(i * 255 / 9)
            return `rgb(${gray}, ${gray}, ${gray})`
          }),
          borderWidth: 0,
          borderRadius: 4,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (ctx) => `${ctx.parsed.y.toFixed(1)}%`,
            },
          },
        },
        scales: {
          x: {
            title: { display: true, text: 'Value' },
            grid: { display: false },
          },
          y: {
            title: { display: true, text: '% of Image' },
            beginAtZero: true,
            grid: { color: '#e5e5e5' },
          },
        },
      },
    })
  }
})

onUnmounted(() => {
  chart?.destroy()
})

watch(() => props.valuePercentages, (newData) => {
  if (chart) {
    chart.data.datasets[0].data = newData.map(p => p * 100)
    chart.update()
  }
}, { deep: true })
</script>
```

---

## Step 5: Palette Recommendation Component

### `components/grisaille/PaletteRecommendation.vue`

```vue
<template>
  <div>
    <h4 class="text-sm font-medium text-canvas-700 mb-4">Recommended Palette</h4>
    <div class="space-y-4">
      <div
        v-for="group in recommendations"
        :key="group.valueRange"
        class="p-4 bg-canvas-50 rounded-xl"
      >
        <div class="flex items-center justify-between mb-3">
          <h5 class="font-medium text-canvas-900">
            Values {{ group.valueRange }}
          </h5>
          <span class="text-xs text-canvas-500 bg-canvas-100 px-2 py-1 rounded">
            {{ group.label }}
          </span>
        </div>
        <div class="flex flex-wrap gap-2">
          <div
            v-for="color in group.colors"
            :key="color.name"
            class="flex items-center gap-2 bg-white rounded-lg px-3 py-2 border border-canvas-200"
          >
            <div
              class="w-8 h-8 rounded-full border border-canvas-200 shadow-inner"
              :style="{ background: color.hex }"
            />
            <div>
              <p class="text-sm font-medium text-canvas-800">{{ color.name }}</p>
              <p class="text-xs text-canvas-500">{{ color.pigment }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { PaletteRecommendation } from '~/composables/useGrisaille'

defineProps<{
  recommendations: PaletteRecommendation[]
}>()
</script>
```

---

## Step 6: Glazing Suggestions Component

### `components/grisaille/GlazingSuggestions.vue`

```vue
<template>
  <div>
    <h4 class="text-sm font-medium text-canvas-700 mb-4">Glazing Suggestions</h4>
    <div class="space-y-3">
      <div
        v-for="(suggestion, index) in suggestions"
        :key="index"
        class="p-4 bg-canvas-50 rounded-xl border-l-4 border-primary-500"
      >
        <div class="flex items-start justify-between">
          <div>
            <h5 class="font-medium text-canvas-900">
              Value {{ suggestion.fromValue }} → {{ suggestion.toValue }}
            </h5>
            <p class="text-sm text-canvas-600 mt-1">{{ suggestion.glaze }}</p>
          </div>
          <span class="text-xs bg-primary-100 text-primary-700 px-2 py-1 rounded font-medium">
            {{ suggestion.technique }}
          </span>
        </div>
        <div class="mt-3 text-sm text-canvas-600">
          <p><span class="font-medium">Medium:</span> {{ suggestion.medium }}</p>
          <p class="mt-1">{{ suggestion.description }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { GlazingSuggestion } from '~/composables/useGrisaille'

defineProps<{
  suggestions: GlazingSuggestion[]
}>()
</script>
```

---

## Step 7: PDF Download Component

### `components/grisaille/PdfDownload.vue`

```vue
<template>
  <div class="flex items-center gap-4">
    <button
      @click="downloadPdf"
      :disabled="generating"
      class="flex items-center gap-2 px-6 py-3 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition disabled:opacity-50"
    >
      <Icon
        :name="generating ? 'mdi:loading' : 'mdi:file-pdf-box'"
        :class="['w-5 h-5', { 'animate-spin': generating }]"
      />
      {{ generating ? 'Generating PDF...' : 'Download PDF Report' }}
    </button>
    <span v-if="pdfUrl" class="text-sm text-canvas-500">
      PDF ready! <a :href="pdfUrl" target="_blank" class="text-primary-600 underline">Open</a>
    </span>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  analysisId: number
}>()

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
```

---

## Step 8: Main Grisaille Page

### `pages/grisaille.vue`

```vue
<template>
  <div class="min-h-screen bg-canvas-50 pt-20 pb-16">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="text-center mb-12">
        <h1 class="text-4xl font-serif font-bold text-canvas-900">
          Grisaille Value Analysis
        </h1>
        <p class="mt-3 text-lg text-canvas-600 max-w-2xl mx-auto">
          Upload a reference photo to analyze its value structure, get palette recommendations,
          and receive glazing suggestions for your painting.
        </p>
      </div>

      <!-- Upload Area -->
      <GrisailleImageUploader
        v-if="!analysisResult"
        @select="handleFileSelect"
      />

      <!-- Results -->
      <div v-if="analysisResult" class="space-y-8">
        <!-- Value Preview -->
        <div class="bg-white rounded-2xl shadow-sm p-6">
          <GrisailleValuePreview
            :original-url="originalPreviewUrl"
            :preview-url="analysisResult.preview"
          />
        </div>

        <!-- Histogram -->
        <div class="bg-white rounded-2xl shadow-sm p-6">
          <GrisailleValueHistogram
            :value-percentages="analysisResult.analysis.valuePercentages"
          />
        </div>

        <!-- Palette & Glazing -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="bg-white rounded-2xl shadow-sm p-6">
            <GrisaillePaletteRecommendation
              :recommendations="analysisResult.palette"
            />
          </div>
          <div class="bg-white rounded-2xl shadow-sm p-6">
            <GrisailleGlazingSuggestions
              :suggestions="analysisResult.glazings"
            />
          </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-wrap items-center justify-between gap-4 bg-white rounded-2xl shadow-sm p-6">
          <button
            @click="resetTool"
            class="px-6 py-3 text-canvas-700 border border-canvas-300 rounded-lg font-medium hover:border-primary-400 transition"
          >
            Analyze Another Image
          </button>

          <GrisaillePdfDownload
            v-if="savedAnalysisId"
            :analysis-id="savedAnalysisId"
          />
        </div>
      </div>

      <!-- Loading State -->
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
const config = useRuntimeConfig()

const analysisResult = ref<{
  analysis: any
  preview: string
  palette: any[]
  glazings: any[]
} | null>(null)

const originalPreviewUrl = ref<string>('')
const savedAnalysisId = ref<number | null>(null)

const handleFileSelect = async (file: File) => {
  // Show local preview immediately
  const reader = new FileReader()
  reader.onload = (e) => {
    originalPreviewUrl.value = e.target?.result as string
  }
  reader.readAsDataURL(file)

  // Process with Canvas API
  const result = await processImage(file)
  analysisResult.value = result

  // Save to server for PDF generation
  try {
    const formData = new FormData()
    formData.append('image', file)
    formData.append('value_map', JSON.stringify(result.analysis.valueMap))
    formData.append('value_percentages', JSON.stringify(result.analysis.valuePercentages))
    formData.append('palette', JSON.stringify(result.palette))
    formData.append('glazings', JSON.stringify(result.glazings))

    const response = await $fetch<{ id: number }>(
      `${config.public.apiUrl}/grisaille/analyze`,
      {
        method: 'POST',
        body: formData,
        withCredentials: true,
      }
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
```

---

## Step 9: Backend Controller

### `app/Http/Controllers/Api/GrisailleController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GrisailleAnalysis;
use App\Jobs\GenerateGrisaillePdf;
use Illuminate\Http\Request;
use Intervention\Image\ImageManagerStatic as Image;

class GrisailleController extends Controller
{
    public function analyze(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:20480',
            'value_map' => 'required|json',
            'value_percentages' => 'required|json',
            'palette' => 'required|json',
            'glazings' => 'required|json',
        ]);

        $imagePath = $request->file('image')->store('grisaille', 'public');

        $analysis = GrisailleAnalysis::create([
            'user_id' => $request->user()->id,
            'original_image_url' => $imagePath,
            'value_map' => json_decode($request->value_map, true),
            'value_percentages' => json_decode($request->value_percentages, true),
            'palette_recommendation' => json_decode($request->palette, true),
            'glazing_suggestions' => json_decode($request->glazings, true),
        ]);

        // Dispatch PDF generation job
        GenerateGrisaillePdf::dispatch($analysis);

        return response()->json([
            'id' => $analysis->id,
            'message' => 'Analysis saved. PDF will be available shortly.',
        ], 201);
    }

    public function history(Request $request)
    {
        $analyses = GrisailleAnalysis::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return response()->json($analyses);
    }

    public function downloadPdf(Request $request, $id)
    {
        $analysis = GrisailleAnalysis::where('user_id', $request->user()->id)
            ->findOrFail($id);

        if (!$analysis->pdf_url) {
            return response()->json(['message' => 'PDF not ready yet'], 404);
        }

        return response()->json([
            'url' => Storage::disk('s3')->temporaryUrl(
                $analysis->pdf_url,
                now()->addMinutes(30)
            ),
        ]);
    }
}
```

---

## Step 10: PDF Generation Job

### `app/Jobs/GenerateGrisaillePdf.php`

```php
<?php

namespace App\Jobs;

use App\Models\GrisailleAnalysis;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateGrisaillePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public GrisailleAnalysis $analysis
    ) {}

    public function handle(): void
    {
        $analysis = $this->analysis;

        // Get image URL
        $imageUrl = Storage::disk('s3')->temporaryUrl(
            $analysis->original_image_url,
            now()->addHour()
        );

        // Build PDF data
        $data = [
            'analysis' => $analysis,
            'value_map' => $analysis->value_map,
            'value_percentages' => $analysis->value_percentages,
            'palette' => $analysis->palette_recommendation,
            'glazings' => $analysis->glazing_suggestions,
            'image_url' => $imageUrl,
            'generated_at' => now()->format('F j, Y \a\t g:i A'),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.grisaille-report', $data)
            ->setPaper('a4', 'portrait');

        $pdfContent = $pdf->output();

        // Store PDF
        $pdfPath = "grisaille/{$analysis->id}/report.pdf";
        Storage::disk('s3')->put($pdfPath, $pdfContent);

        // Update analysis with PDF URL
        $analysis->update(['pdf_url' => $pdfPath]);
    }
}
```

---

## Step 11: PDF Template

### `resources/views/pdf/grisaille-report.blade.php`

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Grisaille Analysis Report</title>
    <style>
        body { font-family: 'Georgia', serif; margin: 40px; color: #333; }
        .header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #d68222; padding-bottom: 20px; }
        .header h1 { font-size: 28px; margin: 0; color: #1a1a1a; }
        .header p { color: #666; margin-top: 8px; }
        .section { margin-bottom: 30px; }
        .section h2 { font-size: 18px; color: #d68222; border-bottom: 1px solid #e5e5e5; padding-bottom: 8px; }
        .values { display: flex; gap: 4px; margin: 15px 0; }
        .value-bar { flex: 1; height: 40px; display: flex; align-items: flex-end; justify-content: center; }
        .value-bar span { font-size: 10px; color: white; padding: 2px; }
        .palette-group { margin: 15px 0; padding: 15px; background: #fafaf7; border-radius: 8px; }
        .palette-group h3 { font-size: 14px; margin: 0 0 10px 0; }
        .color-swatch { display: inline-block; width: 24px; height: 24px; border-radius: 50%; border: 1px solid #ddd; margin-right: 8px; vertical-align: middle; }
        .color-name { font-size: 13px; }
        .glazing { margin: 10px 0; padding: 12px; border-left: 3px solid #d68222; background: #fafaf7; }
        .glazing h4 { font-size: 14px; margin: 0 0 6px 0; }
        .glazing p { font-size: 12px; margin: 4px 0; color: #666; }
        .footer { margin-top: 40px; text-align: center; font-size: 11px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Grisaille Value Analysis</h1>
        <p>Generated on {{ $generated_at }} by Atelier</p>
    </div>

    <div class="section">
        <h2>Value Distribution</h2>
        <div class="values">
            @foreach($value_percentages as $i => $pct)
                <div class="value-bar" style="background: rgb({{ $i * 28 }}, {{ $i * 28 }}, {{ $i * 28 }}); height: {{ max(20, $pct * 200) }}px;">
                    <span>{{ $i }}</span>
                </div>
            @endforeach
        </div>
        <p style="font-size: 12px; color: #666;">
            Average value: {{ number_format($analysis->averageValue ?? 0, 1) }}
        </p>
    </div>

    <div class="section">
        <h2>Recommended Palette</h2>
        @foreach($palette as $group)
            <div class="palette-group">
                <h3>Values {{ $group['valueRange'] }} — {{ $group['label'] }}</h3>
                @foreach($group['colors'] as $color)
                    <span class="color-swatch" style="background: {{ $color['hex'] }}"></span>
                    <span class="color-name">{{ $color['name'] }} ({{ $color['pigment'] }})</span><br>
                @endforeach
            </div>
        @endforeach
    </div>

    <div class="section">
        <h2>Glazing Suggestions</h2>
        @foreach($glazings as $glaze)
            <div class="glazing">
                <h4>Value {{ $glaze['fromValue'] }} → {{ $glaze['toValue'] }}: {{ $glaze['glaze'] }}</h4>
                <p><strong>Medium:</strong> {{ $glaze['medium'] }}</p>
                <p><strong>Technique:</strong> {{ $glaze['technique'] }}</p>
                <p>{{ $glaze['description'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="footer">
        <p>Atelier — Professional Platform for Painters</p>
        <p>This analysis is a guide. Adjust based on your artistic vision.</p>
    </div>
</body>
</html>
```

---

## Verification Checklist

- [ ] Image upload works (drag-drop + file select)
- [ ] Canvas API processes image client-side
- [ ] Value histogram renders correctly
- [ ] Palette recommendations display
- [ ] Glazing suggestions display
- [ ] PDF generates server-side
- [ ] PDF downloads successfully
- [ ] Analysis saves to database

---

## Next Steps

Proceed to [Skill 06: Painter Sites](./06-painter-sites.md) for public profiles and galleries.

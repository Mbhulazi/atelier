<template>
  <div>
    <h4 class="text-sm font-medium text-canvas-700 mb-4">Recommended Palette</h4>
    <div class="space-y-4">
      <div v-for="group in recommendations" :key="group.valueRange" class="p-4 bg-canvas-50 rounded-xl">
        <div class="flex items-center justify-between mb-3">
          <h5 class="font-medium text-canvas-900">Values {{ group.valueRange }}</h5>
          <span class="text-xs text-canvas-500 bg-canvas-100 px-2 py-1 rounded">{{ group.label }}</span>
        </div>
        <div class="flex flex-wrap gap-2">
          <div v-for="color in group.colors" :key="color.name"
            class="flex items-center gap-2 bg-white rounded-lg px-3 py-2 border border-canvas-200">
            <div class="w-8 h-8 rounded-full border border-canvas-200 shadow-inner" :style="{ background: color.hex }" />
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
defineProps<{ recommendations: PaletteRecommendation[] }>()
</script>

<template>
  <div>
    <h4 class="text-sm font-medium text-canvas-700 mb-4">Glazing Suggestions</h4>
    <div class="space-y-3">
      <div v-for="(suggestion, index) in suggestions" :key="index"
        class="p-4 bg-canvas-50 rounded-xl border-l-4 border-primary-500">
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
defineProps<{ suggestions: GlazingSuggestion[] }>()
</script>

<template>
  <div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <h4 class="text-sm font-medium text-canvas-700 mb-2">Original</h4>
        <div class="rounded-xl overflow-hidden bg-canvas-100 aspect-square">
          <img :src="originalUrl" alt="Original" class="w-full h-full object-contain" />
        </div>
      </div>
      <div>
        <h4 class="text-sm font-medium text-canvas-700 mb-2">Value Map (0-9)</h4>
        <div class="rounded-xl overflow-hidden bg-canvas-100 aspect-square">
          <img :src="previewUrl" alt="Value preview" class="w-full h-full object-contain" />
        </div>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <span class="text-xs text-canvas-500">0 (Black)</span>
      <div class="flex-1 flex gap-0.5">
        <div
          v-for="i in 10" :key="i"
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
defineProps<{ originalUrl: string; previewUrl: string }>()
</script>

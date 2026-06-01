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

const props = defineProps<{ valuePercentages: number[] }>()

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
          tooltip: { callbacks: { label: (ctx) => `${ctx.parsed.y.toFixed(1)}%` } },
        },
        scales: {
          x: { title: { display: true, text: 'Value' }, grid: { display: false } },
          y: { title: { display: true, text: '% of Image' }, beginAtZero: true, grid: { color: '#e5e5e5' } },
        },
      },
    })
  }
})

onUnmounted(() => { chart?.destroy() })

watch(() => props.valuePercentages, (newData) => {
  if (chart) {
    chart.data.datasets[0].data = newData.map(p => p * 100)
    chart.update()
  }
}, { deep: true })
</script>

<template>
  <div class="w-full h-full">
    <Bar v-if="chartReady" :data="chartData" :options="chartOptions" />
  </div>
</template>

<script setup lang="ts">
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

const props = defineProps<{
  data: Array<{ month: string; earnings: number; sales: number }>
}>()

const chartReady = ref(false)

onMounted(() => {
  chartReady.value = true
})

const chartData = computed(() => ({
  labels: props.data.map(d => {
    const [year, month] = d.month.split('-')
    return new Date(parseInt(year), parseInt(month) - 1).toLocaleDateString('en-US', { month: 'short', year: '2-digit' })
  }),
  datasets: [
    {
      label: 'Earnings ($)',
      data: props.data.map(d => Number(d.earnings)),
      backgroundColor: '#d68222',
      borderRadius: 8,
      barThickness: 32,
    },
  ],
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#1f2937',
      titleColor: '#f9fafb',
      bodyColor: '#f9fafb',
      padding: 12,
      cornerRadius: 8,
      callbacks: {
        label: (ctx: any) => `$${ctx.raw.toLocaleString()}`,
      },
    },
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { color: '#9ca3af', font: { size: 12 } },
    },
    y: {
      grid: { color: '#f3f4f6' },
      ticks: {
        color: '#9ca3af',
        font: { size: 12 },
        callback: (val: any) => `$${val}`,
      },
    },
  },
}
</script>

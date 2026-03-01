<template>
  <div class="w-full">
    <canvas ref="chartRef"></canvas>
  </div>
</template>
<script setup>
import { ref, onMounted, watch } from 'vue'
import { Chart, LineElement, PointElement, LineController, CategoryScale, LinearScale, Tooltip, Legend, Filler } from 'chart.js'
Chart.register(LineElement, PointElement, LineController, CategoryScale, LinearScale, Tooltip, Legend, Filler)

const props = defineProps({ data: { type: Array, default: () => [] } })
const chartRef = ref(null)
let chart = null

onMounted(() => createChart())
watch(() => props.data, () => { if (chart) { chart.destroy(); createChart() } })

function createChart() {
  if (!chartRef.value || !props.data.length) return
  const labels = props.data.slice(-14).map(d => {
    const date = new Date(d.date)
    return date.toLocaleDateString('en', { month: 'short', day: 'numeric' })
  })
  const minutes = props.data.slice(-14).map(d => Math.round(d.minutes / 60 * 10) / 10)
  chart = new Chart(chartRef.value, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Hours',
        data: minutes,
        borderColor: '#3B82F6',
        backgroundColor: 'rgba(59,130,246,0.1)',
        fill: true,
        tension: 0.4,
        pointRadius: 3,
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => `${ctx.raw}h` } } },
      scales: { y: { beginAtZero: true, ticks: { callback: v => `${v}h` } }, x: { ticks: { maxTicksLimit: 7 } } }
    }
  })
}
</script>

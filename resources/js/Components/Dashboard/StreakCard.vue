<template>
  <div class="bg-gradient-to-br from-orange-400 to-red-500 rounded-2xl p-6 text-white">
    <div class="flex items-center gap-2 mb-4">
      <span class="text-3xl">🔥</span>
      <div>
        <div class="text-4xl font-bold">{{ streak }}</div>
        <div class="text-orange-100 text-sm">Day streak</div>
      </div>
    </div>
    <p class="text-orange-100 text-sm">{{ motivationalMessage }}</p>
    <div class="mt-4 grid grid-cols-7 gap-1">
      <div v-for="day in last7Days" :key="day.date"
        :class="['w-6 h-6 rounded-md text-xs flex items-center justify-center',
          day.active ? 'bg-white text-orange-500 font-bold' : 'bg-orange-300/30 text-orange-200']">
        {{ day.label }}
      </div>
    </div>
  </div>
</template>
<script setup>
import { computed } from 'vue'
const props = defineProps({ streak: { type: Number, default: 0 }, activity: { type: Array, default: () => [] } })
const motivationalMessage = computed(() => {
  if (props.streak >= 30) return '🏆 Amazing! 30+ day streak!'
  if (props.streak >= 14) return '⭐ Two weeks strong!'
  if (props.streak >= 7) return '🎯 One week streak!'
  if (props.streak >= 3) return '💪 Keep it up!'
  return '🌱 Start your streak today!'
})
const last7Days = computed(() => {
  const activeDates = new Set(props.activity.filter(d => d.minutes > 0).map(d => d.date))
  return Array.from({ length: 7 }, (_, i) => {
    const date = new Date()
    date.setDate(date.getDate() - (6 - i))
    const dateStr = date.toISOString().split('T')[0]
    return { date: dateStr, label: ['S','M','T','W','T','F','S'][date.getDay()], active: activeDates.has(dateStr) }
  })
})
</script>

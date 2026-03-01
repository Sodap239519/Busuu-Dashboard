<template>
  <div :class="['rounded-2xl p-6 flex flex-col gap-3 shadow-sm border border-gray-100', bgClass]">
    <div class="flex items-center justify-between">
      <span class="text-2xl">{{ icon }}</span>
      <span v-if="trend" :class="['text-xs font-medium px-2 py-1 rounded-full', trend === 'up' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
        {{ trend === 'up' ? '↑' : '↓' }} {{ trendValue }}
      </span>
    </div>
    <div>
      <div class="text-3xl font-bold text-gray-900">{{ value }}</div>
      <div class="text-sm text-gray-600 mt-1">{{ title }}</div>
    </div>
  </div>
</template>
<script setup>
import { computed } from 'vue'
const props = defineProps({
  title: String,
  value: [String, Number],
  icon: String,
  color: { type: String, default: 'blue' },
  trend: String,
  trendValue: String,
})
const bgClass = computed(() => {
  const map = { blue: 'bg-blue-50', green: 'bg-green-50', orange: 'bg-orange-50', purple: 'bg-purple-50', red: 'bg-red-50', yellow: 'bg-yellow-50' }
  return map[props.color] || 'bg-gray-50'
})
</script>

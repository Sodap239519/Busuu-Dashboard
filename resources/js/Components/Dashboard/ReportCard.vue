<template>
  <a :href="href"
    class="flex flex-col gap-3 bg-gray-50 hover:bg-blue-50 border border-gray-100 hover:border-blue-200 rounded-2xl p-5 transition-colors group">
    <div class="text-3xl">{{ icon }}</div>
    <div>
      <div class="font-semibold text-gray-800 group-hover:text-blue-700 mb-1">{{ title }}</div>
      <div class="text-sm text-gray-500">{{ description }}</div>
    </div>
    <div class="text-xs text-blue-600 font-medium mt-auto">Download CSV →</div>
  </a>
</template>
<script setup>
defineProps({
  title:       { type: String, required: true },
  description: { type: String, default: '' },
  icon:        { type: String, default: '📄' },
  href:        { type: String, required: true },
})
</script>

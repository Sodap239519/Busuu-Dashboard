<template>
  <div class="space-y-3">
    <div v-if="activities.length === 0" class="text-center text-gray-400 py-8">No recent activities</div>
    <div v-for="activity in activities" :key="activity.id" class="flex items-start gap-3 p-3 rounded-xl hover:bg-gray-50">
      <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
        <span class="text-sm">{{ activity.completed ? '✅' : '📖' }}</span>
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-medium text-gray-800 truncate">{{ activity.course_name }}</p>
        <p v-if="activity.lesson_title" class="text-xs text-gray-500 truncate">{{ activity.lesson_title }}</p>
        <div class="flex items-center gap-2 mt-1">
          <span class="text-xs text-gray-400">⏱ {{ activity.duration_minutes }}min</span>
          <span class="text-xs text-yellow-600">⭐ {{ activity.xp_earned }} XP</span>
        </div>
      </div>
      <div class="text-xs text-gray-400 flex-shrink-0">{{ formatDate(activity.session_date) }}</div>
    </div>
  </div>
</template>
<script setup>
defineProps({ activities: { type: Array, default: () => [] } })
function formatDate(dateStr) {
  const date = new Date(dateStr)
  const today = new Date()
  const diff = Math.floor((today - date) / 86400000)
  if (diff === 0) return 'Today'
  if (diff === 1) return 'Yesterday'
  return `${diff}d ago`
}
</script>

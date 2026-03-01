<template>
  <div>
    <div v-if="achievements.length === 0" class="text-center text-gray-400 py-4">
      <div class="text-3xl mb-2">🏆</div>
      <p class="text-sm">No achievements yet. Keep learning!</p>
    </div>
    <div class="grid grid-cols-3 gap-3">
      <div v-for="badge in displayBadges" :key="badge.id || badge.name"
        :class="['flex flex-col items-center p-3 rounded-xl text-center', badge.earned ? 'bg-yellow-50' : 'bg-gray-50 opacity-50']"
        :title="badge.description">
        <span class="text-2xl mb-1">{{ badge.icon }}</span>
        <span class="text-xs font-medium text-gray-700 leading-tight">{{ badge.name }}</span>
      </div>
    </div>
  </div>
</template>
<script setup>
import { computed } from 'vue'
const props = defineProps({ achievements: { type: Array, default: () => [] } })
const allBadges = [
  { name: '7-Day Streak', icon: '🔥', description: 'Study 7 days in a row' },
  { name: '30-Day Streak', icon: '💎', description: 'Study 30 days in a row' },
  { name: '1000 XP', icon: '⭐', description: 'Earn 1000 XP' },
  { name: 'First Course', icon: '🎓', description: 'Complete your first course' },
  { name: '5 Courses', icon: '📚', description: 'Complete 5 courses' },
  { name: '10 Courses', icon: '🏆', description: 'Complete 10 courses' },
]
const displayBadges = computed(() => {
  const earnedNames = new Set(props.achievements.map(a => a.name))
  return allBadges.map(b => ({ ...b, earned: earnedNames.has(b.name) }))
})
</script>

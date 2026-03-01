<template>
  <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
    <div class="flex items-center gap-3 mb-4">
      <div :style="{ backgroundColor: course.color + '20' }" class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl">
        {{ course.icon || '📚' }}
      </div>
      <div>
        <h3 class="font-semibold text-gray-900">{{ course.name }}</h3>
        <p class="text-xs text-gray-500">{{ course.language }} · {{ course.level }}</p>
      </div>
    </div>
    <div class="mb-2">
      <div class="flex justify-between text-sm text-gray-600 mb-1">
        <span>{{ course.lessons_completed }}/{{ course.total_lessons }} lessons</span>
        <span>{{ Math.round(course.progress) }}%</span>
      </div>
      <div class="w-full bg-gray-100 rounded-full h-2">
        <div :style="{ width: course.progress + '%', backgroundColor: course.color }" class="h-2 rounded-full transition-all duration-500"></div>
      </div>
    </div>
    <p v-if="course.next_lesson" class="text-xs text-gray-500 mt-2">Next: {{ course.next_lesson }}</p>
  </div>
</template>
<script setup>
defineProps({ course: { type: Object, required: true } })
</script>

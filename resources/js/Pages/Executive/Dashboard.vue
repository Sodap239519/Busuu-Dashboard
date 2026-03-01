<template>
  <Head title="Dashboard" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">📊 Executive Dashboard</h2>
    </template>

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Stats Row -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <StatsCard title="Total Hours" :value="stats.total_hours + 'h'" icon="⏱" color="blue" />
          <StatsCard title="Total XP" :value="stats.total_xp.toLocaleString()" icon="⭐" color="yellow" />
          <StatsCard title="Courses" :value="stats.courses_count" icon="📚" color="green" />
          <StatsCard title="Completed" :value="stats.completed_courses" icon="🎓" color="purple" />
        </div>

        <!-- Main Bento Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
          <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h3 class="font-semibold text-gray-800 mb-4">📈 Learning Activity (Last 30 days)</h3>
            <ProgressChart :data="weeklyActivity" />
          </div>

          <!-- Streak card not meaningful platform-wide; keep slot for future KPI -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h3 class="font-semibold text-gray-800 mb-2">📌 KPI</h3>
            <p class="text-sm text-gray-600">Add platform KPI here (DAU/MAU, avg minutes/day, etc.).</p>
          </div>
        </div>

        <!-- Second Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h3 class="font-semibold text-gray-800 mb-4">🏆 Latest Achievements</h3>
            <AchievementBadges :achievements="achievements" />
          </div>

          <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h3 class="font-semibold text-gray-800 mb-4">📝 Recent Activity</h3>
            <ActivityTimeline :activities="recentActivities" />
          </div>
        </div>

        <!-- Courses Row (Top courses) -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <h3 class="font-semibold text-gray-800 mb-4">📚 Top Courses</h3>

          <div v-if="courses.length === 0" class="text-center text-gray-400 py-8">
            <p>No course activity yet.</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <CourseCard
              v-for="course in courses"
              :key="course.id"
              :course="{
                id: course.id,
                name: course.name,
                language: course.language,
                level: course.level,
                icon: course.icon,
                color: course.color,
                // CourseCard expects these fields in your current dashboard:
                progress: null,
                lessons_completed: null,
                total_lessons: null,
                next_lesson: null,
              }"
            />
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import StatsCard from '@/Components/Dashboard/StatsCard.vue'
import ProgressChart from '@/Components/Dashboard/ProgressChart.vue'
import ActivityTimeline from '@/Components/Dashboard/ActivityTimeline.vue'
import AchievementBadges from '@/Components/Dashboard/AchievementBadges.vue'
import CourseCard from '@/Components/Dashboard/CourseCard.vue'

defineProps({
  stats: { type: Object, required: true },
  weeklyActivity: { type: Array, default: () => [] },
  recentActivities: { type: Array, default: () => [] },
  achievements: { type: Array, default: () => [] },
  courses: { type: Array, default: () => [] },
})
</script>
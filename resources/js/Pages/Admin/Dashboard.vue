<template>
  <Head title="Admin Dashboard" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">⚙️ Admin Dashboard</h2>
    </template>
    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <StatsCard title="Total Users" :value="stats.total_users" icon="👥" color="blue" />
          <StatsCard title="Total Sessions" :value="stats.total_sessions.toLocaleString()" icon="📊" color="green" />
          <StatsCard title="Total Courses" :value="stats.total_courses" icon="📚" color="purple" />
          <StatsCard title="Total Hours" :value="stats.total_hours + 'h'" icon="⏱" color="orange" />
        </div>

        <!-- Recent Sessions -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
          <div class="flex justify-between items-center mb-4">
            <h3 class="font-semibold text-gray-800">📋 Recent Sessions</h3>
            <Link :href="route('admin.import')" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
              Import Data →
            </Link>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-gray-100">
                  <th class="text-left py-3 px-4 text-gray-600 font-medium">User</th>
                  <th class="text-left py-3 px-4 text-gray-600 font-medium">Course</th>
                  <th class="text-left py-3 px-4 text-gray-600 font-medium">Duration</th>
                  <th class="text-left py-3 px-4 text-gray-600 font-medium">XP</th>
                  <th class="text-left py-3 px-4 text-gray-600 font-medium">Date</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="session in recentSessions" :key="session.id" class="border-b border-gray-50 hover:bg-gray-50">
                  <td class="py-3 px-4 font-medium text-gray-800">{{ session.user_name }}</td>
                  <td class="py-3 px-4 text-gray-600">{{ session.course_name }}</td>
                  <td class="py-3 px-4 text-gray-600">{{ session.duration_minutes }}min</td>
                  <td class="py-3 px-4 text-yellow-600">⭐ {{ session.xp_earned }}</td>
                  <td class="py-3 px-4 text-gray-400">{{ session.session_date }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import StatsCard from '@/Components/Dashboard/StatsCard.vue'
defineProps({
  stats: { type: Object, required: true },
  recentSessions: { type: Array, default: () => [] },
})
</script>

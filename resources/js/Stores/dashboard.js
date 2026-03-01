import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useDashboardStore = defineStore('dashboard', () => {
    const stats = ref(null)
    const courses = ref([])
    const recentActivities = ref([])
    const weeklyActivity = ref([])
    const achievements = ref([])

    function setDashboardData(data) {
        if (data.stats) stats.value = data.stats
        if (data.courses) courses.value = data.courses
        if (data.recentActivities) recentActivities.value = data.recentActivities
        if (data.weeklyActivity) weeklyActivity.value = data.weeklyActivity
        if (data.achievements) achievements.value = data.achievements
    }

    return { stats, courses, recentActivities, weeklyActivity, achievements, setDashboardData }
})

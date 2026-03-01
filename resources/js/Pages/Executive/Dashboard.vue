<template>
  <Head title="Executive Dashboard" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">📊 Executive Dashboard</h2>
    </template>

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Tab navigation -->
        <div class="flex gap-1 mb-6 bg-white rounded-2xl p-1 shadow-sm border border-gray-100 overflow-x-auto">
          <button
            v-for="tab in tabs" :key="tab.id"
            @click="activeTab = tab.id"
            :class="['px-4 py-2 rounded-xl text-sm font-medium transition-colors whitespace-nowrap',
              activeTab === tab.id ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100']"
          >{{ tab.label }}</button>
        </div>

        <!-- ============================================================ -->
        <!-- TAB 1: OVERALL DASHBOARD                                      -->
        <!-- ============================================================ -->
        <div v-show="activeTab === 'overall'">

          <!-- Licence metrics -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <StatsCard title="Total Licences"     :value="licences.total"               icon="🪪"  color="blue" />
            <StatsCard title="Active (In Use)"    :value="licences.active"              icon="✅"  color="green" />
            <StatsCard title="Pending Invites"    :value="licences.pending"             icon="⏳"  color="orange" />
            <StatsCard title="Weekly Active (%)"  :value="walPct + '%'"                 icon="📈"  color="purple" />
          </div>

          <!-- Learning totals -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <StatsCard title="Total Learning Hours"   :value="totalHours + 'h'"         icon="⏱"  color="blue" />
            <StatsCard title="Total Lessons Completed" :value="totalLessons.toLocaleString()" icon="📝" color="green" />
            <template v-for="(count, level) in cefr" :key="level">
              <StatsCard :title="'Certificates ' + level" :value="count" icon="🎓" color="purple" />
            </template>
          </div>

          <!-- Activity Chart -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
            <h3 class="font-semibold text-gray-800 mb-4">📈 Learning Activity (Last 30 days)</h3>
            <ProgressChart :data="weeklyActivity" />
          </div>
        </div>

        <!-- ============================================================ -->
        <!-- TAB 2: TEAMS REPORT                                           -->
        <!-- ============================================================ -->
        <div v-show="activeTab === 'teams'">
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-semibold text-gray-800">👥 Teams Report</h3>
              <div class="flex gap-2">
                <button @click="teamSort = 'pending'" :class="sortBtnClass('pending')">Sort: Pending ↓</button>
                <button @click="teamSort = 'active'"  :class="sortBtnClass('active')">Sort: Active ↓</button>
                <button @click="teamSort = 'total'"   :class="sortBtnClass('total')">Sort: Total ↓</button>
                <a :href="route('dashboard.export', 'teams')" class="px-3 py-1 text-xs rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700">Export CSV</a>
              </div>
            </div>
            <div v-if="sortedTeams.length === 0" class="text-center text-gray-400 py-8">No team data yet.</div>
            <div v-else class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="text-left text-gray-500 border-b border-gray-100">
                    <th class="pb-2 pr-4">Team</th>
                    <th class="pb-2 pr-4 text-right">Total</th>
                    <th class="pb-2 pr-4 text-right">Active</th>
                    <th class="pb-2 pr-4 text-right">Pending</th>
                    <th class="pb-2 text-right">Active %</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="t in sortedTeams" :key="t.team" class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="py-2 pr-4 font-medium">{{ t.team }}</td>
                    <td class="py-2 pr-4 text-right">{{ t.total }}</td>
                    <td class="py-2 pr-4 text-right text-green-600">{{ t.active }}</td>
                    <td class="py-2 pr-4 text-right text-orange-500">{{ t.pending }}</td>
                    <td class="py-2 text-right">
                      <span :class="['px-2 py-0.5 rounded-full text-xs font-medium',
                        t.active_ratio >= 70 ? 'bg-green-100 text-green-700' :
                        t.active_ratio >= 40 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700']">
                        {{ t.active_ratio }}%
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ============================================================ -->
        <!-- TAB 3: STUDENTS REPORT                                        -->
        <!-- ============================================================ -->
        <div v-show="activeTab === 'students'">
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <div class="flex flex-wrap items-center gap-3 mb-4">
              <h3 class="font-semibold text-gray-800 mr-auto">🎓 Students Report</h3>

              <!-- Filters -->
              <select v-model="studentFilters.status" @change="applyStudentFilters"
                class="text-sm border border-gray-200 rounded-lg px-3 py-1.5">
                <option value="">All Statuses</option>
                <option v-for="s in filterOptions.statuses" :key="s" :value="s">{{ s }}</option>
              </select>
              <select v-model="studentFilters.team" @change="applyStudentFilters"
                class="text-sm border border-gray-200 rounded-lg px-3 py-1.5">
                <option value="">All Teams</option>
                <option v-for="t in filterOptions.teams" :key="t" :value="t">{{ t }}</option>
              </select>
              <select v-model="studentFilters.faculty" @change="applyStudentFilters"
                class="text-sm border border-gray-200 rounded-lg px-3 py-1.5">
                <option value="">All Faculties</option>
                <option v-for="f in filterOptions.faculties" :key="f" :value="f">{{ f }}</option>
              </select>

              <a :href="buildExportUrl('students')" class="px-3 py-1.5 text-xs rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700">Export CSV</a>
            </div>

            <div class="text-xs text-gray-400 mb-3">{{ filteredStudents.length }} students</div>

            <div v-if="filteredStudents.length === 0" class="text-center text-gray-400 py-8">No students found.</div>
            <div v-else class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="text-left text-gray-500 border-b border-gray-100">
                    <th class="pb-2 pr-4">Email</th>
                    <th class="pb-2 pr-4">Name</th>
                    <th class="pb-2 pr-4">Team</th>
                    <th class="pb-2 pr-4">Faculty</th>
                    <th class="pb-2 pr-4">Major</th>
                    <th class="pb-2 pr-4">Group</th>
                    <th class="pb-2">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="s in filteredStudents" :key="s.id" class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="py-2 pr-4 text-blue-600">{{ s.email }}</td>
                    <td class="py-2 pr-4">{{ s.name }}</td>
                    <td class="py-2 pr-4 text-gray-500">{{ s.team }}</td>
                    <td class="py-2 pr-4 text-gray-500">{{ s.faculty }}</td>
                    <td class="py-2 pr-4 text-gray-500">{{ s.major }}</td>
                    <td class="py-2 pr-4 text-gray-500">{{ s.busuu_user_group }}</td>
                    <td class="py-2">
                      <span :class="['px-2 py-0.5 rounded-full text-xs font-medium',
                        s.status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700']">
                        {{ s.status }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ============================================================ -->
        <!-- TAB 4: GENERATED REPORTS                                      -->
        <!-- ============================================================ -->
        <div v-show="activeTab === 'reports'">
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h3 class="font-semibold text-gray-800 mb-6">📄 Generated Reports</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <ReportCard
                v-for="report in reportCards" :key="report.type"
                :title="report.title" :description="report.description" :icon="report.icon"
                :href="route('dashboard.export', report.type)"
              />
            </div>
          </div>
        </div>

        <!-- ============================================================ -->
        <!-- TAB 5: MEETING INSIGHTS                                       -->
        <!-- ============================================================ -->
        <div v-show="activeTab === 'insights'">

          <!-- Top lists -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <!-- Top 5 by pending -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
              <h3 class="font-semibold text-gray-800 mb-4">⏳ Top 5 Teams – Pending Invites</h3>
              <div v-if="!meetingInsights.top_pending?.length" class="text-gray-400 text-sm">No data.</div>
              <ul v-else class="space-y-2">
                <li v-for="(t, i) in meetingInsights.top_pending" :key="t.team"
                  class="flex items-center justify-between px-3 py-2 rounded-lg bg-orange-50">
                  <span class="text-sm font-medium">{{ i + 1 }}. {{ t.team }}</span>
                  <span class="text-sm font-bold text-orange-600">{{ t.count }}</span>
                </li>
              </ul>
            </div>

            <!-- Top 5 by active -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
              <h3 class="font-semibold text-gray-800 mb-4">✅ Top 5 Teams – Active</h3>
              <div v-if="!meetingInsights.top_active?.length" class="text-gray-400 text-sm">No data.</div>
              <ul v-else class="space-y-2">
                <li v-for="(t, i) in meetingInsights.top_active" :key="t.team"
                  class="flex items-center justify-between px-3 py-2 rounded-lg bg-green-50">
                  <span class="text-sm font-medium">{{ i + 1 }}. {{ t.team }}</span>
                  <span class="text-sm font-bold text-green-600">{{ t.count }}</span>
                </li>
              </ul>
            </div>
          </div>

          <!-- Zero-lessons learners -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
            <h3 class="font-semibold text-gray-800 mb-4">🚫 Active Learners with 0 Lessons Completed</h3>
            <div v-if="!meetingInsights.zero_lessons?.length" class="text-gray-400 text-sm py-4">None – all active learners have started lessons!</div>
            <div v-else class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="text-left text-gray-500 border-b border-gray-100">
                    <th class="pb-2 pr-4">Email</th><th class="pb-2 pr-4">Name</th><th class="pb-2">Team</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="u in meetingInsights.zero_lessons" :key="u.email" class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="py-2 pr-4 text-blue-600">{{ u.email }}</td>
                    <td class="py-2 pr-4">{{ u.name }}</td>
                    <td class="py-2 text-gray-500">{{ u.team }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Charts row -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Usage by Faculty chart -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
              <h3 class="font-semibold text-gray-800 mb-4">🏫 % Usage by Faculty</h3>
              <canvas ref="facultyChartRef"></canvas>
            </div>

            <!-- Weekly trend chart -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
              <h3 class="font-semibold text-gray-800 mb-4">📅 Weekly Trend (WAL% & Hours)</h3>
              <canvas ref="trendChartRef"></canvas>
            </div>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import StatsCard from '@/Components/Dashboard/StatsCard.vue'
import ProgressChart from '@/Components/Dashboard/ProgressChart.vue'
import ReportCard from '@/Components/Dashboard/ReportCard.vue'
import {
  Chart,
  BarElement, BarController,
  LineElement, LineController, PointElement,
  CategoryScale, LinearScale,
  Tooltip, Legend,
} from 'chart.js'
Chart.register(BarElement, BarController, LineElement, LineController, PointElement, CategoryScale, LinearScale, Tooltip, Legend)

const props = defineProps({
  weeklyActivity:  { type: Array,  default: () => [] },
  licences:        { type: Object, default: () => ({ total: 0, active: 0, pending: 0 }) },
  walPct:          { type: Number, default: 0 },
  totalHours:      { type: Number, default: 0 },
  totalLessons:    { type: Number, default: 0 },
  cefr:            { type: Object, default: () => ({}) },
  teamsReport:     { type: Array,  default: () => [] },
  studentsReport:  { type: Array,  default: () => [] },
  meetingInsights: { type: Object, default: () => ({}) },
  filterOptions:   { type: Object, default: () => ({ statuses: [], teams: [], faculties: [] }) },
})

// ── Tabs ─────────────────────────────────────────────────────────────────────
const tabs = [
  { id: 'overall',  label: '📊 Overall Dashboard' },
  { id: 'teams',    label: '👥 Teams Report' },
  { id: 'students', label: '🎓 Students Report' },
  { id: 'reports',  label: '📄 Generated Reports' },
  { id: 'insights', label: '💡 Meeting Insights' },
]
const activeTab = ref('overall')

// ── Teams sort ────────────────────────────────────────────────────────────────
const teamSort = ref('total')
const sortedTeams = computed(() => {
  return [...props.teamsReport].sort((a, b) => b[teamSort.value] - a[teamSort.value])
})
function sortBtnClass(val) {
  return ['px-3 py-1 text-xs rounded-lg',
    teamSort.value === val ? 'bg-blue-600 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700']
}

// ── Students filters ──────────────────────────────────────────────────────────
const studentFilters = ref({ status: '', team: '', faculty: '' })
const filteredStudents = ref([...props.studentsReport])

async function applyStudentFilters() {
  const params = {}
  if (studentFilters.value.status)  params.status  = studentFilters.value.status
  if (studentFilters.value.team)    params.team    = studentFilters.value.team
  if (studentFilters.value.faculty) params.faculty = studentFilters.value.faculty

  if (Object.keys(params).length === 0) {
    filteredStudents.value = [...props.studentsReport]
    return
  }
  try {
    const { data } = await axios.get(route('dashboard.students'), { params })
    filteredStudents.value = data
  } catch {
    filteredStudents.value = props.studentsReport.filter(s => {
      return (!params.status  || s.status  === params.status)
          && (!params.team    || s.team    === params.team)
          && (!params.faculty || s.faculty === params.faculty)
    })
  }
}

function buildExportUrl(type) {
  const base = route('dashboard.export', type)
  const params = new URLSearchParams()
  if (studentFilters.value.status)  params.set('status',  studentFilters.value.status)
  if (studentFilters.value.team)    params.set('team',    studentFilters.value.team)
  if (studentFilters.value.faculty) params.set('faculty', studentFilters.value.faculty)
  const qs = params.toString()
  return qs ? base + '?' + qs : base
}

// ── Report cards ──────────────────────────────────────────────────────────────
const reportCards = [
  { type: 'progress',     title: 'Student Progress Report',        description: 'Course progress per student.',          icon: '📈' },
  { type: 'achievements', title: 'Student Achievement Report',     description: 'Placement tests & certificates.',       icon: '🏆' },
  { type: 'teams',        title: 'Team Progress Report',           description: 'Active/Pending breakdown by team.',     icon: '👥' },
  { type: 'students',     title: 'Course Completion Rate',         description: 'All students with enrolment status.',   icon: '📋' },
  { type: 'credit',       title: 'Live Credit Status',             description: 'Active vs Pending licence snapshot.',   icon: '🪪' },
]

// ── Charts ────────────────────────────────────────────────────────────────────
const facultyChartRef = ref(null)
const trendChartRef   = ref(null)
let facultyChart = null
let trendChart   = null

function buildFacultyChart() {
  if (!facultyChartRef.value) return
  const data = props.meetingInsights?.by_faculty ?? []
  if (!data.length) return

  if (facultyChart) facultyChart.destroy()
  facultyChart = new Chart(facultyChartRef.value, {
    type: 'bar',
    data: {
      labels: data.map(d => d.faculty),
      datasets: [{
        label: 'Usage %',
        data: data.map(d => d.usage_pct),
        backgroundColor: 'rgba(59,130,246,0.7)',
        borderRadius: 4,
      }],
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } },
        x: { ticks: { maxRotation: 45 } },
      },
    },
  })
}

function buildTrendChart() {
  if (!trendChartRef.value) return
  const data = props.meetingInsights?.trend ?? []
  if (!data.length) return

  if (trendChart) trendChart.destroy()
  const labels = data.map(d => d.week)
  trendChart = new Chart(trendChartRef.value, {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'WAL %',
          data: data.map(d => d.wal_pct),
          borderColor: '#3B82F6',
          backgroundColor: 'rgba(59,130,246,0.1)',
          yAxisID: 'yPct',
          tension: 0.4,
          pointRadius: 3,
        },
        {
          label: 'Hours',
          data: data.map(d => d.hours),
          borderColor: '#10B981',
          backgroundColor: 'rgba(16,185,129,0.1)',
          yAxisID: 'yHours',
          tension: 0.4,
          pointRadius: 3,
        },
      ],
    },
    options: {
      responsive: true,
      interaction: { mode: 'index', intersect: false },
      plugins: { legend: { display: true } },
      scales: {
        yPct:   { type: 'linear', position: 'left',  beginAtZero: true, ticks: { callback: v => v + '%' } },
        yHours: { type: 'linear', position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, ticks: { callback: v => v + 'h' } },
        x:      { ticks: { maxTicksLimit: 8 } },
      },
    },
  })
}

watch(activeTab, async (tab) => {
  if (tab === 'insights') {
    await nextTick()
    buildFacultyChart()
    buildTrendChart()
  }
})

onMounted(() => {
  if (activeTab.value === 'insights') {
    buildFacultyChart()
    buildTrendChart()
  }
})
</script>

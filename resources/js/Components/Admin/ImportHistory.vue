<template>
  <div class="overflow-x-auto">
    <table v-if="history.length" class="w-full text-sm">
      <thead>
        <tr class="border-b border-gray-100">
          <th class="text-left py-3 px-4 text-gray-600 font-medium">File</th>
          <th class="text-left py-3 px-4 text-gray-600 font-medium">Type</th>
          <!-- <th class="text-left py-3 px-4 text-gray-600 font-medium">Rows</th> -->
          <th class="text-left py-3 px-4 text-gray-600 font-medium">Status</th>
          <th class="text-left py-3 px-4 text-gray-600 font-medium">Date</th>
        </tr>
      </thead>

      <tbody>
        <tr
          v-for="(item, i) in history"
          :key="i"
          class="border-b border-gray-50 hover:bg-gray-50"
        >
          <td class="py-3 px-4 font-medium text-gray-800">📄 {{ item.filename }}</td>
          <td class="py-3 px-4 text-gray-600 capitalize">{{ item.type }}</td>
          <!-- <td class="py-3 px-4 text-gray-600">{{ item.rows }}</td> -->

          <td class="py-3 px-4">
            <span
              :class="[
                'px-2 py-1 rounded-full text-xs font-medium',
                badgeClass(item.status),
              ]"
              :title="item.status === 'error' ? (item.error || 'Import failed') : ''"
            >
              {{ badgeText(item.status) }}
            </span>

            <div v-if="item.status === 'error' && item.error" class="mt-1 text-xs text-red-600">
              {{ item.error }}
            </div>
          </td>

          <td class="py-3 px-4 text-gray-400">{{ formatDate(item.imported_at) }}</td>
        </tr>
      </tbody>
    </table>

    <div v-else class="text-center text-gray-400 py-8">
      <div class="text-3xl mb-2">📂</div>
      <p>No import history yet</p>
    </div>
  </div>
</template>

<script setup>
defineProps({ history: { type: Array, default: () => [] } })

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('en', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function normalizeStatus(status) {
  return (status || '').toString().toLowerCase()
}

function badgeText(status) {
  status = normalizeStatus(status)

  if (status === 'success') return 'Success'
  if (status === 'queued') return 'Queued'
  if (status === 'processing' || status === 'running') return 'Processing'
  if (status === 'error' || status === 'failed') return 'Error'

  // เผื่อมีสถานะอื่นจาก backend
  return status ? status : '—'
}

function badgeClass(status) {
  status = normalizeStatus(status)

  if (status === 'success') return 'bg-green-100 text-green-700'
  if (status === 'queued') return 'bg-yellow-100 text-yellow-800'
  if (status === 'processing' || status === 'running') return 'bg-blue-100 text-blue-700'
  if (status === 'error' || status === 'failed') return 'bg-red-100 text-red-700'

  return 'bg-gray-100 text-gray-700'
}
</script>
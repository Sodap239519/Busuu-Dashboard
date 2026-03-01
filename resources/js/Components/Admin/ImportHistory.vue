<template>
  <div class="overflow-x-auto">
    <table v-if="history.length" class="w-full text-sm">
      <thead>
        <tr class="border-b border-gray-100">
          <th class="text-left py-3 px-4 text-gray-600 font-medium">File</th>
          <th class="text-left py-3 px-4 text-gray-600 font-medium">Type</th>
          <th class="text-left py-3 px-4 text-gray-600 font-medium">Rows</th>
          <th class="text-left py-3 px-4 text-gray-600 font-medium">Status</th>
          <th class="text-left py-3 px-4 text-gray-600 font-medium">Date</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, i) in history" :key="i" class="border-b border-gray-50 hover:bg-gray-50">
          <td class="py-3 px-4 font-medium text-gray-800">📄 {{ item.filename }}</td>
          <td class="py-3 px-4 text-gray-600 capitalize">{{ item.type }}</td>
          <td class="py-3 px-4 text-gray-600">{{ item.rows }}</td>
          <td class="py-3 px-4">
            <span :class="['px-2 py-1 rounded-full text-xs font-medium', item.status === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
              {{ item.status === 'success' ? '✅ Success' : '❌ Error' }}
            </span>
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
  return new Date(dateStr).toLocaleString('en', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>

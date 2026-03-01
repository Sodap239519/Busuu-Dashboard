<template>
  <div>
    <div
      @dragover.prevent="isDragging = true"
      @dragleave="isDragging = false"
      @drop.prevent="onDrop"
      :class="['border-2 border-dashed rounded-2xl p-10 text-center transition-colors', isDragging ? 'border-blue-400 bg-blue-50' : 'border-gray-300 hover:border-blue-300']"
    >
      <div class="text-4xl mb-3">📤</div>
      <p class="text-gray-600 mb-2">Drop Excel file here or</p>
      <label class="cursor-pointer inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
        Browse File
        <input type="file" class="hidden" accept=".xlsx,.xls,.csv" @change="onFileSelect" />
      </label>
      <p class="text-xs text-gray-400 mt-2">Supported: .xlsx, .xls, .csv (max 10MB)</p>
    </div>

    <div v-if="selectedFile" class="mt-4 p-4 bg-gray-50 rounded-xl flex items-center justify-between">
      <div class="flex items-center gap-3">
        <span class="text-2xl">📄</span>
        <div>
          <p class="font-medium text-gray-800">{{ selectedFile.name }}</p>
          <p class="text-xs text-gray-500">{{ formatSize(selectedFile.size) }}</p>
        </div>
      </div>
      <button @click="selectedFile = null" class="text-gray-400 hover:text-gray-600">✕</button>
    </div>

    <div v-if="selectedFile" class="mt-4">
      <label class="block text-sm font-medium text-gray-700 mb-2">Import Type</label>
      <select v-model="importType" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="sessions">Learning Sessions</option>
        <option value="courses">Courses</option>
        <option value="monthly_report">Monthly Report (Busuu)</option>
      </select>
    </div>

    <button
      v-if="selectedFile"
      @click="$emit('upload', { file: selectedFile, type: importType })"
      :disabled="uploading"
      class="mt-4 w-full bg-blue-600 text-white py-3 rounded-xl font-medium hover:bg-blue-700 transition-colors disabled:opacity-50"
    >
      {{ uploading ? 'Importing...' : 'Import Data' }}
    </button>
  </div>
</template>
<script setup>
import { ref } from 'vue'
defineProps({ uploading: Boolean })
defineEmits(['upload'])
const isDragging = ref(false)
const selectedFile = ref(null)
const importType = ref('sessions')
function onDrop(e) {
  isDragging.value = false
  const file = e.dataTransfer.files[0]
  if (file) selectedFile.value = file
}
function onFileSelect(e) { selectedFile.value = e.target.files[0] }
function formatSize(bytes) {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / 1024 / 1024).toFixed(1) + ' MB'
}
</script>

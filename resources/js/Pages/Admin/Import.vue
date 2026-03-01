<template>
  <Head title="Import Data" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">📤 Import Excel Data</h2>
    </template>

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Success/Error Messages -->
        <div
          v-if="$page.props.flash?.success"
          class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl"
        >
          ✅ {{ $page.props.flash.success }}
        </div>

        <div
          v-if="errors.file"
          class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl"
        >
          ❌ {{ errors.file }}
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <!-- File Uploader -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 lg:col-span-4">
            <h3 class="font-semibold text-gray-800 mb-4">Upload Workbook</h3>
            <FileUploader :uploading="form.processing" @upload="handleUpload" />

            <div class="mt-6 p-4 bg-gray-50 rounded-xl">
              <h4 class="text-sm font-semibold text-gray-700 mb-2">📌 Note</h4>
              <p class="text-xs text-gray-600">
                Upload the Busuu monthly workbook (.xlsx/.xls). The system will automatically read multiple sheets and
                update the dashboard data.
              </p>
            </div>
          </div>

          <!-- Import History -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 lg:col-span-8">
            <h3 class="font-semibold text-gray-800 mb-4">📜 Import History</h3>
            <ImportHistory :history="history" />
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import FileUploader from '@/Components/Admin/FileUploader.vue'
import ImportHistory from '@/Components/Admin/ImportHistory.vue'

defineProps({
  history: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) },
})

const form = useForm({ file: null })

function handleUpload({ file }) {
  form.file = file
  form.post(route('admin.import.upload'), {
    forceFormData: true,
  })
}
</script>
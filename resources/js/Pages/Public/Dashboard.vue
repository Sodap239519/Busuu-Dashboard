<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue'

defineProps({
  canLogin: Boolean,
  canRegister: Boolean,
})
</script>

<template>
  <PublicLayout :can-login="canLogin" :can-register="canRegister">
    <h1 class="text-2xl font-semibold">Dashboard</h1>
    <p class="mt-2 text-gray-600">
      หน้านี้เป็น Dashboard/รายงานสำหรับผู้ใช้ที่ยังไม่ login
    </p>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
      <div class="rounded-lg border bg-white p-4">
        <div class="text-sm text-gray-500">Report A</div>
        <div class="mt-2 text-xl font-semibold">—</div>
      </div>

      <div class="rounded-lg border bg-white p-4">
        <div class="text-sm text-gray-500">Report B</div>
        <div class="mt-2 text-xl font-semibold">—</div>
      </div>
    </div>
  </PublicLayout>
</template>
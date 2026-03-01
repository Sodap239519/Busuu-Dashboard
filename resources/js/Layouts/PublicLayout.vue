<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  canLogin: { type: Boolean, default: true },
  canRegister: { type: Boolean, default: true },
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 text-gray-900">
    <header class="border-b bg-white">
      <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
        <Link href="/" class="text-lg font-semibold">
          Busuu Dashboard
        </Link>

        <nav class="flex items-center gap-2">
          <Link
            v-if="canLogin"
            :href="route('login')"
            class="rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-100"
          >
            Login
          </Link>

          <Link
            v-if="canRegister"
            :href="route('register')"
            class="rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-800"
          >
            Register
          </Link>
        </nav>
      </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-6">
      <slot />
    </main>
  </div>
</template>
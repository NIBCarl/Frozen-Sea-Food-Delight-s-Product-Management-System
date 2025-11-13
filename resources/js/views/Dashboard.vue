<template>
  <div class="pa-8" style="display:flex;align-items:center;justify-content:center;height:60vh;">
    <v-progress-circular :size="48" indeterminate color="primary" />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

onMounted(async () => {
  // Ensure user is loaded when visiting /dashboard directly
  if (!authStore.user && authStore.token) {
    try { await authStore.fetchUser() } catch (e) { router.replace('/login'); return }
  }

  if (authStore.hasRole('admin')) {
    router.replace('/admin/dashboard')
  } else if (authStore.hasRole('customer')) {
    router.replace('/customer/products')
  } else if (authStore.hasRole('supplier')) {
    router.replace('/supplier/shipments')
  } else if (authStore.hasRole('delivery_personnel')) {
    router.replace('/delivery/today')
  } else {
    router.replace('/customer/products')
  }
})
</script>

<style scoped></style>

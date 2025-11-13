<template>
  <v-app>
    <!-- Loading state during initialization -->
    <template v-if="authStore.loading">
      <div class="loading-screen">
        <v-progress-circular
          :size="50"
          color="primary"
          indeterminate
        ></v-progress-circular>
        <p style="margin-top: 16px; color: #666;">Initializing...</p>
      </div>
    </template>
    
    <!-- Authenticated Routes with AppLayout -->
    <template v-else-if="authStore.isAuthenticated">
      <AppLayout>
        <router-view />
      </AppLayout>
    </template>
    
    <!-- Guest Routes (Login, Register) -->
    <template v-else>
      <router-view />
    </template>
    
    <GlobalNotifications />
  </v-app>
</template>

<script setup>
import { onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import AppLayout from '@/components/layouts/AppLayout.vue';
import GlobalNotifications from '@/components/GlobalNotifications.vue';

const authStore = useAuthStore();

onMounted(async () => {
  authStore.loading = true;
  try {
    await authStore.initializeAuth();
  } catch (error) {
    console.error('App initialization error:', error);
  } finally {
    authStore.loading = false;
  }
});
</script>

<style scoped>
.loading-screen {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  height: 100vh;
  background: #f5f5f5;
}
</style>

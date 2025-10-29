<template>
  <v-app>
    <!-- Authenticated Routes with AppLayout -->
    <template v-if="authStore.isAuthenticated">
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

onMounted(() => {
  authStore.initializeAuth();
});
</script>

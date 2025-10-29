<template>
  <div class="global-notifications">
    <v-snackbar
      v-for="notification in notificationStore.activeNotifications"
      :key="notification.id"
      v-model="notification.show"
      :color="getColor(notification.type)"
      location="top right"
      :timeout="0"
      multi-line
    >
      <div class="d-flex align-center">
        <v-icon :icon="notification.icon" class="mr-2"></v-icon>
        <span>{{ notification.message }}</span>
      </div>
      
      <template v-slot:actions>
        <v-btn
          color="white"
          variant="text"
          size="small"
          @click="notificationStore.hideNotification(notification.id)"
        >
          Close
        </v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script setup>
import { useNotificationStore } from '@/stores/notifications';

const notificationStore = useNotificationStore();

const getColor = (type) => {
  const colors = {
    success: 'green',
    error: 'red',
    warning: 'orange',
    info: 'blue',
  };
  return colors[type] || 'grey';
};
</script>

<style scoped>
.global-notifications {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
  pointer-events: none;
}

.global-notifications .v-snackbar {
  pointer-events: auto;
  margin-bottom: 10px;
}
</style>

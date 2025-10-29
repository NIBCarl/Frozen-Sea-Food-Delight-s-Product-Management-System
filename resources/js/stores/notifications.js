import { defineStore } from 'pinia';

export const useNotificationStore = defineStore('notifications', {
  state: () => ({
    notifications: [],
    nextId: 1,
  }),

  getters: {
    activeNotifications: (state) => state.notifications.filter(n => n.show),
  },

  actions: {
    showSuccess(message, duration = 5000) {
      this.addNotification({
        message,
        type: 'success',
        duration,
        icon: 'mdi-check-circle',
      });
    },

    showError(message, duration = 8000) {
      this.addNotification({
        message,
        type: 'error',
        duration,
        icon: 'mdi-alert-circle',
      });
    },

    showWarning(message, duration = 6000) {
      this.addNotification({
        message,
        type: 'warning',
        duration,
        icon: 'mdi-alert',
      });
    },

    showInfo(message, duration = 5000) {
      this.addNotification({
        message,
        type: 'info',
        duration,
        icon: 'mdi-information',
      });
    },

    addNotification(notification) {
      const id = this.nextId++;
      const newNotification = {
        id,
        show: true,
        ...notification,
      };

      this.notifications.push(newNotification);

      // Auto-hide after duration
      if (notification.duration > 0) {
        setTimeout(() => {
          this.hideNotification(id);
        }, notification.duration);
      }

      return id;
    },

    hideNotification(id) {
      const notification = this.notifications.find(n => n.id === id);
      if (notification) {
        notification.show = false;
      }
    },

    removeNotification(id) {
      const index = this.notifications.findIndex(n => n.id === id);
      if (index > -1) {
        this.notifications.splice(index, 1);
      }
    },

    clearAll() {
      this.notifications.forEach(n => n.show = false);
    },

    // Helper methods for common error scenarios
    showApiError(error) {
      let message = 'An unexpected error occurred';
      
      if (error.response) {
        const status = error.response.status;
        const data = error.response.data;
        
        if (status === 401) {
          message = 'Session expired. Please login again.';
        } else if (status === 403) {
          message = 'You do not have permission to perform this action.';
        } else if (status === 404) {
          message = 'The requested resource was not found.';
        } else if (status === 422) {
          // Validation errors
          if (data.errors) {
            const errors = Object.values(data.errors).flat();
            message = errors.join(' ');
          } else {
            message = data.message || 'Validation failed.';
          }
        } else if (status >= 500) {
          message = 'Server error. Please try again later.';
        } else {
          message = data.message || `Error: ${status}`;
        }
      } else if (error.request) {
        message = 'Network error. Please check your connection.';
      }
      
      this.showError(message);
    },

    showValidationErrors(errors) {
      if (typeof errors === 'object') {
        const messages = Object.values(errors).flat();
        messages.forEach(message => this.showError(message, 10000));
      } else {
        this.showError(errors);
      }
    },
  },
});

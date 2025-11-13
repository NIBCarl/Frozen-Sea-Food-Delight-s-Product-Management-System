import { defineStore } from 'pinia';
import axios from 'axios';

// helper: ensure roles array of strings in snake_case
const toSnake = (str) => str
  .replace(/([a-z])([A-Z])/g, '$1_$2')
  .replace(/\s+/g, '_')
  .replace(/-+/g, '_')
  .toLowerCase();
const normalizeRoles = (roles) => {
  if (!roles) return [];
  if (Array.isArray(roles)) {
    const arr = roles.map(r => (typeof r === 'string' ? r : r.name));
    return arr.map(toSnake);
  }
  if (typeof roles === 'object') {
    return Object.values(roles).map(r => toSnake(typeof r === 'string' ? r : r.name));
  }
  return [];
};
import { useAuthStore } from './auth';

export const useSettingsStore = defineStore('settings', {
  state: () => ({
    profile: {
      name: '',
      username: '',
      email: '',
      avatar: null,
    },
    preferences: {
      theme: 'light',
      notifications: {
        email_notifications: true,
        low_stock_alerts: true,
        system_updates: true,
      },
      dashboard: {
        show_charts: true,
        items_per_page: 15,
      }
    },
    loading: false,
    updating: false,
    uploadingAvatar: false,
    error: null,
  }),

  getters: {
    hasUnsavedChanges: (state) => {
      const authStore = useAuthStore();
      if (!authStore.user) return false;
      
      return (
        state.profile.name !== authStore.user.name ||
        state.profile.username !== authStore.user.username ||
        state.profile.email !== authStore.user.email
      );
    },
  },

  actions: {
    async loadProfile() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get('/api/v1/profile');
        const user = response.data.user;
        
        this.profile = {
          name: user.name,
          username: user.username, 
          email: user.email,
          avatar: user.avatar,
        };

        // Update auth store user data as well (normalize roles)
        const authStore = useAuthStore();
        user.roles = normalizeRoles(user.roles);
        authStore.user = user;
        
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to load profile';
        console.error('Load profile error:', error);
      } finally {
        this.loading = false;
      }
    },

    async loadPreferences() {
      try {
        const response = await axios.get('/api/v1/profile/preferences');
        this.preferences = response.data.preferences;
      } catch (error) {
        console.error('Load preferences error:', error);
        // Use defaults if loading fails
      }
    },

    async updateProfile(profileData) {
      this.updating = true;
      this.error = null;

      try {
        const response = await axios.put('/api/v1/profile', profileData);
        const user = response.data.user;
        
        this.profile = {
          name: user.name,
          username: user.username,
          email: user.email,
          avatar: user.avatar,
        };

        // Update auth store (normalize roles)
        const authStore = useAuthStore();
        user.roles = normalizeRoles(user.roles);
        authStore.user = user;

        return { success: true, message: response.data.message };
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update profile';
        return { 
          success: false, 
          message: this.error, 
          errors: error.response?.data?.errors 
        };
      } finally {
        this.updating = false;
      }
    },

    async changePassword(passwordData) {
      this.updating = true;
      this.error = null;

      try {
        const response = await axios.put('/api/v1/profile/password', passwordData);
        return { success: true, message: response.data.message };
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to change password';
        return { 
          success: false, 
          message: this.error, 
          errors: error.response?.data?.errors 
        };
      } finally {
        this.updating = false;
      }
    },

    async updateAvatar(avatarFile) {
      this.uploadingAvatar = true;
      this.error = null;

      try {
        const formData = new FormData();
        formData.append('avatar', avatarFile);

        const response = await axios.post('/api/v1/profile/avatar', formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        });

        const user = response.data.user;
        this.profile.avatar = user.avatar;

        // Update auth store
        const authStore = useAuthStore();
        authStore.user = user;

        return { success: true, message: response.data.message };
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to upload avatar';
        return { 
          success: false, 
          message: this.error, 
          errors: error.response?.data?.errors 
        };
      } finally {
        this.uploadingAvatar = false;
      }
    },

    async removeAvatar() {
      this.uploadingAvatar = true;
      this.error = null;

      try {
        const response = await axios.delete('/api/v1/profile/avatar');
        const user = response.data.user;
        this.profile.avatar = null;

        // Update auth store
        const authStore = useAuthStore();
        authStore.user = user;

        return { success: true, message: response.data.message };
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to remove avatar';
        return { success: false, message: this.error };
      } finally {
        this.uploadingAvatar = false;
      }
    },

    async updatePreferences(preferencesData) {
      this.updating = true;
      this.error = null;

      try {
        const response = await axios.put('/api/v1/profile/preferences', preferencesData);
        this.preferences = response.data.preferences;
        return { success: true, message: response.data.message };
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update preferences';
        return { 
          success: false, 
          message: this.error, 
          errors: error.response?.data?.errors 
        };
      } finally {
        this.updating = false;
      }
    },

    resetError() {
      this.error = null;
    },

    initializeFromAuth() {
      const authStore = useAuthStore();
      if (authStore.user) {
        this.profile = {
          name: authStore.user.name,
          username: authStore.user.username,
          email: authStore.user.email,
          avatar: authStore.user.avatar,
        };
      }
    }
  }
});

import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    isAuthenticated: false,
    loading: false,
  }),

  getters: {
    isLoggedIn: (state) => !!state.token,
    hasRole: (state) => (role) => {
      if (!state.user?.roles) return false;
      if (Array.isArray(role)) {
        return role.some(r => state.user.roles.some(userRole => userRole.name === r));
      }
      return state.user.roles.some(userRole => userRole.name === role);
    },
    hasPermission: (state) => (permission) => {
      if (!state.user?.permissions) return false;
      if (Array.isArray(permission)) {
        return permission.some(p => state.user.permissions.some(userPerm => userPerm.name === p));
      }
      return state.user.permissions.some(userPerm => userPerm.name === permission);
    },
    isAdmin: (state) => state.user?.roles?.some(role => role.name === 'admin') || false,
    isManager: (state) => state.user?.roles?.some(role => role.name === 'manager') || false,
    canAccessAdminDashboard: (state) => {
      return state.user?.roles?.some(role => role.name === 'admin') || false;
    },
  },

  actions: {
    async login(credentials) {
      this.loading = true;
      try {
        const response = await axios.post('/api/v1/auth/login', credentials);
        this.token = response.data.token;
        this.user = response.data.user;
        this.isAuthenticated = true;
        localStorage.setItem('token', this.token);
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async register(userData) {
      this.loading = true;
      try {
        const { name, username, email, password, password_confirmation } = userData;
        const response = await axios.post('/api/v1/auth/register', {
          name,
          username,
          email,
          password,
          password_confirmation
        });
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async logout() {
      try {
        await axios.post('/api/v1/auth/logout');
      } catch (error) {
        console.error('Logout error:', error);
      } finally {
        this.clearAuth();
      }
    },

    async fetchUser() {
      try {
        const response = await axios.get('/api/v1/auth/user');
        this.user = response.data.user || response.data;
        this.isAuthenticated = true;
      } catch (error) {
        console.error('Failed to fetch user:', error);
        this.clearAuth();
        throw error;
      }
    },

    clearAuth() {
      this.user = null;
      this.token = null;
      this.isAuthenticated = false;
      localStorage.removeItem('token');
      delete axios.defaults.headers.common['Authorization'];
    },

    async refreshToken() {
      try {
        const response = await axios.post('/api/v1/auth/refresh');
        this.token = response.data.token;
        localStorage.setItem('token', this.token);
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
        return response.data;
      } catch (error) {
        this.clearAuth();
        throw error;
      }
    },

    async initializeAuth() {
      if (this.token) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
        try {
          await this.fetchUser();
        } catch (error) {
          // If user fetch fails, clear auth silently
          console.warn('Authentication initialization failed:', error.message);
        }
      }
    }
  }
});

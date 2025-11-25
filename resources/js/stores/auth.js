import { defineStore } from 'pinia';
import axios from 'axios';

// Helper to normalize roles to array of strings
const toSnake = (str) => str
  .replace(/([a-z])([A-Z])/g, '$1_$2')   // camelCase -> camel_Case
  .replace(/\s+/g, '_')                  // spaces -> _
  .replace(/-+/g, '_')                    // dashes -> _
  .toLowerCase();

const normalizeRoles = (roles) => {
  let arr;
  if (!roles) arr = [];
  else if (Array.isArray(roles)) {
    arr = roles.map(r => (typeof r === 'string' ? r : r.name));
  } else if (typeof roles === 'object') {
    arr = Object.values(roles).map(r => (typeof r === 'string' ? r : r.name));
  } else {
    arr = [];
  }
  return arr.map(toSnake);
};

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
      if (Array.isArray(state.user.roles)) {
        // Optimized format from login: array of role names
        return state.user.roles.includes(role);
      }
      // Legacy format: array of role objects
      return state.user.roles.some(userRole => userRole.name === role);
    },
    hasAnyRole: (state) => (roles) => {
      if (!state.user?.roles) return false;
      if (Array.isArray(state.user.roles)) {
        return roles.some(role => state.user.roles.includes(role));
      }
      return roles.some(role => state.user.roles.some(userRole => userRole.name === role));
    },
    isAdmin: (state) => {
      if (!state.user?.roles) return false;
      if (Array.isArray(state.user.roles)) {
        return state.user.roles.includes('admin');
      }
      return state.user.roles.some(role => role.name === 'admin');
    },
    canAccessAdminDashboard: (state) => {
      if (!state.user?.roles) return false;
      if (Array.isArray(state.user.roles)) {
        return state.user.roles.includes('admin');
      }
      return state.user.roles.some(role => role.name === 'admin');
    },
  },

  actions: {
    async login(credentials) {
      this.loading = true;
      try {
        const response = await axios.post('/api/v1/auth/login', credentials);
        this.token = response.data.token;
        const user = response.data.user;
        user.roles = normalizeRoles(user.roles);
        this.user = user;
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
      // Note: Don't set this.loading = true here, as it triggers the global loading screen
      // The Register component has its own local loading state
      try {
        const { name, username, email, contact_number, password, password_confirmation } = userData;
        const response = await axios.post('/api/v1/auth/register', {
          name,
          username,
          email,
          contact_number,
          password,
          password_confirmation
        });
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    async verifyOtp(data) {
      // Note: Don't set this.loading = true here, as it triggers the global loading screen
      try {
        const response = await axios.post('/api/v1/auth/verify-otp', data);
        this.token = response.data.token;
        const user = response.data.user;
        user.roles = normalizeRoles(user.roles);
        this.user = user;
        this.isAuthenticated = true;
        localStorage.setItem('token', this.token);
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    async resendOtp(email) {
      // Note: Don't set this.loading = true here, as it triggers the global loading screen
      try {
        const response = await axios.post('/api/v1/auth/resend-otp', { email });
        return response.data;
      } catch (error) {
        throw error;
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
        // Normalize roles to array of role names for consistency
        const apiUser = response.data.user || response.data;
        apiUser.roles = normalizeRoles(apiUser.roles);
        this.user = apiUser;
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
          // Fetch user data to ensure proper authentication state
          await this.fetchUser();
        } catch (error) {
          // If token is invalid, clear auth
          console.warn('Token validation failed during initialization:', error.message);
          this.clearAuth();
        }
      }
    },

    async ensureUserLoaded() {
      if (this.isAuthenticated && !this.user) {
        try {
          await this.fetchUser();
        } catch (error) {
          console.warn('Failed to load user data:', error.message);
          this.clearAuth();
        }
      }
    }
  }
});

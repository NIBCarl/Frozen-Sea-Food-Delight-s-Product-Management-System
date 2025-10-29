import { defineStore } from 'pinia';
import axios from 'axios';

export const useUserStore = defineStore('users', {
  state: () => ({
    users: [],
    roles: [],
    pagination: {
      current_page: 1,
      per_page: 10,
      total: 0,
    },
    loading: false,
    error: null,
  }),

  actions: {
    async fetchUsers(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const response = await axios.get('/api/v1/users', { params });
        this.users = response.data.data;
        this.pagination = {
          current_page: response.data.current_page,
          per_page: response.data.per_page,
          total: response.data.total,
        };
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch users';
        console.error(this.error);
      } finally {
        this.loading = false;
      }
    },

    async fetchRoles() {
      try {
        const response = await axios.get('/api/v1/roles'); // Assuming an endpoint to get all roles
        this.roles = response.data.data;
      } catch (error) {
        console.error('Failed to fetch roles:', error);
      }
    },

    async createUser(userData) {
      this.loading = true;
      this.error = null;
      try {
        await axios.post('/api/v1/users', userData);
        await this.fetchUsers({ page: this.pagination.current_page });
      } catch (error) {
        this.error = error.response?.data?.errors || { message: 'Failed to create user' };
        console.error(this.error);
        throw error;
      }
       finally {
        this.loading = false;
      }
    },

    async updateUser(id, userData) {
      this.loading = true;
      this.error = null;
      try {
        await axios.put(`/api/v1/users/${id}`, userData);
        await this.fetchUsers({ page: this.pagination.current_page });
      } catch (error) {
        this.error = error.response?.data?.errors || { message: 'Failed to update user' };
        console.error(this.error);
        throw error;
      }
       finally {
        this.loading = false;
      }
    },

    async deleteUser(id) {
      this.loading = true;
      this.error = null;
      try {
        await axios.delete(`/api/v1/users/${id}`);
        await this.fetchUsers({ page: this.pagination.current_page });
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete user';
        console.error(this.error);
      } finally {
        this.loading = false;
      }
    },
  },
});

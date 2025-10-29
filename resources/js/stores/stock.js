import { defineStore } from 'pinia';
import axios from 'axios';

export const useStockStore = defineStore('stock', {
  state: () => ({
    movements: [],
    pagination: {
      current_page: 1,
      per_page: 15,
      total: 0,
    },
    loading: false,
  }),

  actions: {
    async fetchMovements(params = {}) {
      this.loading = true;
      try {
        const response = await axios.get('/api/v1/stock-movements', { params });
        this.movements = response.data.data;
        this.pagination = {
          current_page: response.data.current_page,
          per_page: response.data.per_page,
          total: response.data.total,
        };
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createMovement(movementData) {
      this.loading = true;
      try {
        const response = await axios.post('/api/v1/stock-movements', movementData);
        // We don't add to the list directly, we refetch to get the latest state.
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },
  },
});

import { defineStore } from 'pinia';
import axios from 'axios';

export const useProductStore = defineStore('products', {
  state: () => ({
    products: [],
    product: null,
    loading: false,
    pagination: {
      current_page: 1,
      per_page: 15,
      total: 0,
    },
    filters: {
      search: '',
      category_id: null,
      status: null,
      low_stock: false,
    },
  }),

  getters: {
    totalProducts: (state) => state.pagination.total,
    currentPage: (state) => state.pagination.current_page,
    lowStockProducts: (state) => state.products.filter(p => p.is_low_stock),
    activeProducts: (state) => state.products.filter(p => p.status === 'active'),
    availableProducts: (state) => state.products.filter(p => p.is_available !== false && p.status === 'active'),
  },

  actions: {
    async fetchProducts(params = {}) {
      this.loading = true;
      try {
        const response = await axios.get('/api/v1/products', { params });
        this.products = response.data.data;
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

    async fetchProduct(id) {
      this.loading = true;
      try {
        const response = await axios.get(`/api/v1/products/${id}`);
        this.product = response.data;
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createProduct(productData) {
      this.loading = true;
      try {
        const response = await axios.post('/api/v1/products', productData);
        const product = response.data.data || response.data;
        this.products.unshift(product);
        return product;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateProduct(id, productData) {
      this.loading = true;
      try {
        const response = await axios.put(`/api/v1/products/${id}`, productData);
        const product = response.data.data || response.data;
        const index = this.products.findIndex(p => p.id === id);
        if (index !== -1) {
          this.products[index] = product;
        }
        return product;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteProduct(id) {
      try {
        await axios.delete(`/api/v1/products/${id}`);
        this.products = this.products.filter(p => p.id !== id);
      } catch (error) {
        throw error;
      }
    },

    async uploadProductImage(productId, imageFile) {
      const formData = new FormData();
      formData.append('image', imageFile);
      
      try {
        const response = await axios.post(
          `/api/v1/products/${productId}/images`,
          formData,
          { headers: { 'Content-Type': 'multipart/form-data' } }
        );
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    async setPrimaryImage(productId, imageId) {
      try {
        const response = await axios.patch(
          `/api/v1/products/${productId}/images/${imageId}/primary`
        );
        if (this.product && this.product.id === productId) {
          this.product = response.data;
        }
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    async deleteProductImage(productId, imageId) {
      try {
        await axios.delete(`/api/v1/products/${productId}/images/${imageId}`);
        if (this.product && this.product.id === productId) {
          this.product.images = this.product.images.filter(img => img.id !== imageId);
        }
      } catch (error) {
        throw error;
      }
    },

    async getLowStockProducts() {
      this.loading = true;
      try {
        const response = await axios.get('/api/v1/products', { 
          params: { low_stock: true } 
        });
        return response.data.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    setFilters(filters) {
      this.filters = { ...this.filters, ...filters };
    },

    resetFilters() {
      this.filters = {
        search: '',
        category_id: null,
        status: null,
        low_stock: false,
      };
    },
  }
});

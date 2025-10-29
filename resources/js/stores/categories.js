import { defineStore } from 'pinia';
import axios from 'axios';

export const useCategoryStore = defineStore('categories', {
  state: () => ({
    categories: [],
    category: null,
    loading: false,
    tree: [],
  }),

  getters: {
    parentCategories: (state) => state.categories.filter(c => !c.parent_id),
    childCategories: (state) => state.categories.filter(c => c.parent_id),
    activeCategories: (state) => state.categories.filter(c => c.status === 'active'),
    allCategories: (state) => state.categories,
  },

  actions: {
    async fetchCategories(params = {}) {
      this.loading = true;
      try {
        const response = await axios.get('/api/v1/categories', { params });
        this.categories = response.data.data;
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchCategory(id) {
      this.loading = true;
      try {
        const response = await axios.get(`/api/v1/categories/${id}`);
        this.category = response.data;
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createCategory(categoryData) {
      this.loading = true;
      try {
        const response = await axios.post('/api/v1/categories', categoryData);
        this.categories.unshift(response.data);
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateCategory(id, categoryData) {
      this.loading = true;
      try {
        const response = await axios.put(`/api/v1/categories/${id}`, categoryData);
        const index = this.categories.findIndex(c => c.id === id);
        if (index !== -1) {
          this.categories[index] = response.data;
        }
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteCategory(id) {
      try {
        await axios.delete(`/api/v1/categories/${id}`);
        this.categories = this.categories.filter(c => c.id !== id);
      } catch (error) {
        throw error;
      }
    },

    async uploadCategoryImage(categoryId, imageFile) {
      const formData = new FormData();
      formData.append('image', imageFile);
      
      try {
        const response = await axios.post(
          `/api/v1/categories/${categoryId}/images`,
          formData,
          { headers: { 'Content-Type': 'multipart/form-data' } }
        );
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    async getCategoryProducts(categoryId) {
      this.loading = true;
      try {
        const response = await axios.get(`/api/v1/categories/${categoryId}/products`);
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchAllCategories() {
      return this.fetchCategories({ all: true });
    },

    buildCategoryTree() {
      const tree = [];
      const categoryMap = {};

      // Create map of all categories
      this.categories.forEach(category => {
        categoryMap[category.id] = { ...category, children: [] };
      });

      // Build tree structure
      this.categories.forEach(category => {
        if (category.parent_id) {
          if (categoryMap[category.parent_id]) {
            categoryMap[category.parent_id].children.push(categoryMap[category.id]);
          }
        } else {
          tree.push(categoryMap[category.id]);
        }
      });

      this.tree = tree;
      return tree;
    },
  }
});

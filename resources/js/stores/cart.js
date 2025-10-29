import { defineStore } from 'pinia'
import axios from 'axios'

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: [],
    total: 0,
    itemCount: 0,
    loading: false,
    error: null,
  }),

  getters: {
    cartTotal: (state) => state.total,
    cartItemCount: (state) => state.itemCount,
    hasItems: (state) => state.items.length > 0,
  },

  actions: {
    async fetchCart() {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.get('/api/v1/cart')
        this.items = response.data.data.items
        this.total = response.data.data.total
        this.itemCount = response.data.data.item_count
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch cart'
        throw error
      } finally {
        this.loading = false
      }
    },

    async addItem(productId, quantity = 1) {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.post('/api/v1/cart/items', {
          product_id: productId,
          quantity
        })
        await this.fetchCart()
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to add item to cart'
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateItem(productId, quantity) {
      this.loading = true
      this.error = null
      
      try {
        await axios.put(`/api/v1/cart/items/${productId}`, { quantity })
        await this.fetchCart()
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update cart item'
        throw error
      } finally {
        this.loading = false
      }
    },

    async removeItem(productId) {
      this.loading = true
      this.error = null
      
      try {
        await axios.delete(`/api/v1/cart/items/${productId}`)
        await this.fetchCart()
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to remove item'
        throw error
      } finally {
        this.loading = false
      }
    },

    async clearCart() {
      this.loading = true
      this.error = null
      
      try {
        await axios.delete('/api/v1/cart/clear')
        this.items = []
        this.total = 0
        this.itemCount = 0
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to clear cart'
        throw error
      } finally {
        this.loading = false
      }
    },

    clearError() {
      this.error = null
    },
  },
})


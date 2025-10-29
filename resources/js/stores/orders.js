import { defineStore } from 'pinia'
import axios from 'axios'

export const useOrderStore = defineStore('orders', {
  state: () => ({
    orders: [],
    currentOrder: null,
    loading: false,
    error: null,
  }),

  getters: {
    pendingOrders: (state) => state.orders.filter(o => o.status === 'pending'),
    processingOrders: (state) => state.orders.filter(o => o.status === 'processing'),
    inTransitOrders: (state) => state.orders.filter(o => o.status === 'in_transit'),
    deliveredOrders: (state) => state.orders.filter(o => o.status === 'delivered'),
    
    orderById: (state) => (id) => state.orders.find(o => o.id === id),
  },

  actions: {
    async fetchOrders(filters = {}) {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.get('/api/v1/orders', { params: filters })
        this.orders = response.data.data.data || response.data.data
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch orders'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchOrder(id) {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.get(`/api/v1/orders/${id}`)
        this.currentOrder = response.data.data
        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch order'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createOrder(orderData) {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.post('/api/v1/orders', orderData)
        this.orders.unshift(response.data.data)
        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create order'
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateOrderStatus(orderId, status) {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.patch(`/api/v1/orders/${orderId}/status`, { status })
        const index = this.orders.findIndex(o => o.id === orderId)
        if (index !== -1) {
          this.orders[index] = response.data.data
        }
        if (this.currentOrder?.id === orderId) {
          this.currentOrder = response.data.data
        }
        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update order status'
        throw error
      } finally {
        this.loading = false
      }
    },

    async cancelOrder(orderId) {
      this.loading = true
      this.error = null
      
      try {
        await axios.delete(`/api/v1/orders/${orderId}`)
        this.orders = this.orders.filter(o => o.id !== orderId)
        if (this.currentOrder?.id === orderId) {
          this.currentOrder = null
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to cancel order'
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


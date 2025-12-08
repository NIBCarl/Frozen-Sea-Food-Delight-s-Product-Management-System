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
        // Handle Laravel pagination structure
        const payload = response?.data?.data
        let ordersArray = []

        if (Array.isArray(payload)) {
          // Simple array response
          ordersArray = payload
        } else if (payload && payload.data && Array.isArray(payload.data)) {
          // Laravel pagination response
          ordersArray = payload.data
          this.pagination = {
            current_page: payload.current_page,
            per_page: payload.per_page,
            total: payload.total,
            last_page: payload.last_page
          }
        } else {
          console.warn('Unexpected orders response structure:', payload)
          ordersArray = []
        }

        this.orders = ordersArray
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch orders'
        // Do not clear orders on error, so the user can still see the old data
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
      this.error = null
      try {
        const response = await axios.post('/api/v1/orders', orderData)
        // Optimistically prepend the new order; backend returns full order object
        this.orders.unshift(response.data.data)
        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create order'
        throw error
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
        
        // Update status in local list instead of removing
        const index = this.orders.findIndex(o => o.id === orderId)
        if (index !== -1) {
          this.orders[index] = { ...this.orders[index], status: 'cancelled' }
        }

        // Update current order if it's the one being cancelled
        if (this.currentOrder?.id === orderId) {
          this.currentOrder = { ...this.currentOrder, status: 'cancelled' }
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


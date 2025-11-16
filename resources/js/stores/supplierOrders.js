import { defineStore } from 'pinia'
import axios from 'axios'

export const useSupplierOrderStore = defineStore('supplierOrders', {
  state: () => ({
    orders: [],
    currentOrder: null,
    statistics: {
      total_orders: 0,
      pending_orders: 0,
      processing_orders: 0,
      completed_orders: 0,
      cancelled_orders: 0,
      total_revenue: 0,
      total_items_sold: 0
    },
    recentOrders: [],
    loading: false,
    error: null,
  }),

  getters: {
    pendingOrders: (state) => state.orders.filter(order => order.status === 'pending'),
    processingOrders: (state) => state.orders.filter(order => order.status === 'processing'),
    completedOrders: (state) => state.orders.filter(order => order.status === 'delivered'),
    totalRevenue: (state) => state.statistics.total_revenue,
    totalItemsSold: (state) => state.statistics.total_items_sold,
  },

  actions: {
    async fetchOrders(filters = {}) {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.get('/api/v1/supplier/orders', { params: filters })
        this.orders = response.data.data.data || response.data.data
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch orders'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchOrder(orderId) {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.get(`/api/v1/supplier/orders/${orderId}`)
        this.currentOrder = response.data.data
        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch order'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchStatistics() {
      this.error = null
      
      try {
        const response = await axios.get('/api/v1/supplier/orders/statistics')
        this.statistics = response.data.data
        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch statistics'
        throw error
      }
    },

    async fetchRecentOrders() {
      this.error = null
      
      try {
        const response = await axios.get('/api/v1/supplier/orders/recent')
        this.recentOrders = response.data.data
        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch recent orders'
        throw error
      }
    },

    async markOrderAsReady(orderId) {
      this.error = null
      
      try {
        const response = await axios.patch(`/api/v1/supplier/orders/${orderId}/mark-ready`)
        
        // Update the order in the orders list
        const orderIndex = this.orders.findIndex(order => order.id === orderId)
        if (orderIndex !== -1) {
          this.orders[orderIndex] = response.data.data
        }
        
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to mark order as ready'
        throw error
      }
    },

    async reportOrderIssue(orderId, issueData) {
      this.error = null
      
      try {
        const response = await axios.post(`/api/v1/supplier/orders/${orderId}/report-issue`, issueData)
        
        // Update the order in the orders list
        const orderIndex = this.orders.findIndex(order => order.id === orderId)
        if (orderIndex !== -1) {
          this.orders[orderIndex] = response.data.data
        }
        
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to report issue'
        throw error
      }
    },

    clearCurrentOrder() {
      this.currentOrder = null
    },

    clearError() {
      this.error = null
    }
  }
})

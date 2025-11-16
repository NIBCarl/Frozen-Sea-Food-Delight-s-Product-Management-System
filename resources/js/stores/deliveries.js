import { defineStore } from 'pinia'
import axios from 'axios'

export const useDeliveryStore = defineStore('deliveries', {
  state: () => ({
    deliveries: [],
    todayDeliveries: [],
    todayStatistics: {
      scheduled: 0,
      out_for_delivery: 0,
      in_transit: 0,
      delivered: 0,
      failed: 0,
      total: 0
    },
    historyDeliveries: [],
    currentDelivery: null,
    loading: false,
    error: null,
  }),

  getters: {
    scheduledDeliveries: (state) => state.deliveries.filter(d => d.status === 'scheduled'),
    outForDelivery: (state) => state.deliveries.filter(d => d.status === 'out_for_delivery'),
    completedDeliveries: (state) => state.deliveries.filter(d => d.status === 'delivered'),
    failedDeliveries: (state) => state.deliveries.filter(d => d.status === 'failed'),
  },

  actions: {
    async fetchDeliveries(filters = {}) {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.get('/api/v1/deliveries', { params: filters })
        this.deliveries = response.data.data.data || response.data.data
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch deliveries'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchTodayDeliveries() {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.get('/api/v1/deliveries/today')
        this.todayDeliveries = response.data.data
        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch today\'s deliveries'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchTodayStatistics() {
      this.error = null
      
      try {
        console.log('Fetching today statistics...')
        const response = await axios.get('/api/v1/deliveries/today/statistics')
        console.log('Statistics response:', response.data)
        this.todayStatistics = response.data.data
        return response.data.data
      } catch (error) {
        console.error('Statistics fetch error:', error)
        this.error = error.response?.data?.message || 'Failed to fetch today\'s statistics'
        throw error
      }
    },

    async fetchHistoryDeliveries(page = 1) {
      this.loading = true
      this.error = null
      try {
        const response = await axios.get('/api/v1/deliveries/history', { params: { page } })
        this.historyDeliveries = response.data.data.data || response.data.data
        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch delivery history'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createDelivery(deliveryData) {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.post('/api/v1/deliveries', deliveryData)
        this.deliveries.unshift(response.data.data)
        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create delivery'
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateDeliveryStatus(deliveryId, statusData) {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.patch(`/api/v1/deliveries/${deliveryId}/status`, statusData)
        
        // Update in deliveries list
        const index = this.deliveries.findIndex(d => d.id === deliveryId)
        if (index !== -1) {
          this.deliveries[index] = response.data.data
        }
        
        // Update in today's deliveries
        const todayIndex = this.todayDeliveries.findIndex(d => d.id === deliveryId)
        if (todayIndex !== -1) {
          this.todayDeliveries[todayIndex] = response.data.data
        }
        
        if (this.currentDelivery?.id === deliveryId) {
          this.currentDelivery = response.data.data
        }
        
        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update delivery status'
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


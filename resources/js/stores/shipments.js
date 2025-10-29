import { defineStore } from 'pinia'
import axios from 'axios'

export const useShipmentStore = defineStore('shipments', {
  state: () => ({
    shipments: [],
    currentShipment: null,
    loading: false,
    error: null,
  }),

  getters: {
    pendingShipments: (state) => state.shipments.filter(s => s.status === 'pending'),
    inTransitShipments: (state) => state.shipments.filter(s => s.status === 'in_transit'),
    arrivedShipments: (state) => state.shipments.filter(s => s.status === 'arrived'),
    confirmedShipments: (state) => state.shipments.filter(s => s.status === 'confirmed'),
  },

  actions: {
    async fetchShipments(filters = {}) {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.get('/api/v1/shipments', { params: filters })
        this.shipments = response.data.data.data || response.data.data
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch shipments'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createShipment(shipmentData) {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.post('/api/v1/shipments', shipmentData)
        this.shipments.unshift(response.data.data)
        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create shipment'
        throw error
      } finally {
        this.loading = false
      }
    },

    async markAsArrived(shipmentId) {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.post(`/api/v1/shipments/${shipmentId}/mark-arrived`)
        const index = this.shipments.findIndex(s => s.id === shipmentId)
        if (index !== -1) {
          this.shipments[index] = response.data.data
        }
        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to mark shipment as arrived'
        throw error
      } finally {
        this.loading = false
      }
    },

    async confirmArrival(shipmentId) {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.post(`/api/v1/shipments/${shipmentId}/confirm-arrival`)
        const index = this.shipments.findIndex(s => s.id === shipmentId)
        if (index !== -1) {
          this.shipments[index] = response.data.data
        }
        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to confirm shipment arrival'
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


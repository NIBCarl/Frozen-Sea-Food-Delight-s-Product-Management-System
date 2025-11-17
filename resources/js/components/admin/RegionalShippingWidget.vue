<template>
  <v-card class="regional-shipping-widget">
    <v-card-title class="d-flex align-center justify-space-between pa-4">
      <div class="d-flex align-center">
        <v-icon color="primary" class="me-2">mdi-map-marker-radius</v-icon>
        <span class="text-h6">Cebu-Surigao Shipping</span>
      </div>
      <v-chip color="primary" size="small" variant="flat">
        {{ totalOrders }} orders
      </v-chip>
    </v-card-title>

    <v-divider></v-divider>

    <v-card-text class="pa-0">
      <!-- Loading -->
      <div v-if="loading" class="py-8 text-center">
        <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
        <p class="text-grey mt-4">Loading shipping data...</p>
      </div>

      <!-- Zone Statistics -->
      <div v-else class="zone-stats-container">
        <!-- Cebu Local Section -->
        <div class="zone-section">
          <div class="zone-section-header">
            <v-icon size="18" color="success">mdi-home-city</v-icon>
            <span class="zone-section-title">Cebu Local</span>
            <v-chip size="x-small" color="success">{{ cebuOrders }} orders</v-chip>
          </div>
          
          <div class="zone-items">
            <div
              v-for="zone in cebuZones"
              :key="zone.id"
              class="zone-item"
            >
              <div class="zone-info">
                <div class="zone-name">{{ zone.name }}</div>
                <div class="zone-details">
                  <span class="detail-badge">
                    <v-icon size="10">mdi-truck</v-icon>
                    {{ zone.order_count || 0 }} orders
                  </span>
                  <span class="detail-badge">
                    <v-icon size="10">mdi-currency-php</v-icon>
                    {{ zone.shipping_rate }}
                  </span>
                </div>
              </div>
              <div class="zone-revenue">
                ₱{{ formatNumber(zone.revenue || 0) }}
              </div>
            </div>
          </div>
        </div>

        <v-divider></v-divider>

        <!-- Surigao Region Section -->
        <div class="zone-section">
          <div class="zone-section-header">
            <v-icon size="18" color="warning">mdi-ferry</v-icon>
            <span class="zone-section-title">Surigao Region</span>
            <v-chip size="x-small" color="warning">{{ surigaoOrders }} orders</v-chip>
          </div>
          
          <div class="zone-items">
            <div
              v-for="zone in surigaoZones"
              :key="zone.id"
              class="zone-item zone-item-sea"
            >
              <div class="zone-info">
                <div class="zone-name">
                  {{ zone.name }}
                  <v-icon v-if="zone.requires_sea_transport" size="12" color="info">
                    mdi-ferry
                  </v-icon>
                </div>
                <div class="zone-details">
                  <span class="detail-badge">
                    <v-icon size="10">mdi-package-variant</v-icon>
                    {{ zone.order_count || 0 }} orders
                  </span>
                  <span class="detail-badge">
                    <v-icon size="10">mdi-calendar</v-icon>
                    {{ zone.estimated_delivery_days }}d
                  </span>
                </div>
              </div>
              <div class="zone-revenue">
                ₱{{ formatNumber(zone.revenue || 0) }}
              </div>
            </div>
          </div>
        </div>

        <!-- Summary Bar -->
        <div class="summary-bar">
          <div class="summary-item">
            <span class="summary-label">Total Shipping Revenue</span>
            <span class="summary-value">₱{{ formatNumber(totalShippingRevenue) }}</span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Avg. Delivery Time</span>
            <span class="summary-value">{{ averageDeliveryDays }} days</span>
          </div>
        </div>
      </div>
    </v-card-text>

    <v-divider></v-divider>

    <v-card-actions class="pa-3">
      <v-btn variant="text" color="primary" size="small" @click="refreshData" :loading="loading">
        <v-icon start>mdi-refresh</v-icon>
        Refresh
      </v-btn>
      <v-spacer></v-spacer>
      <v-btn variant="text" color="primary" size="small" @click="viewFullReport">
        Full Report
        <v-icon end>mdi-arrow-right</v-icon>
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const loading = ref(false)
const shippingZones = ref([])

const cebuZones = computed(() => 
  shippingZones.value.filter(z => z.province === 'Cebu')
)

const surigaoZones = computed(() => 
  shippingZones.value.filter(z => 
    z.province.includes('Surigao') || 
    z.province.includes('Agusan') || 
    z.province.includes('Dinagat')
  )
)

const cebuOrders = computed(() => 
  cebuZones.value.reduce((sum, z) => sum + (z.order_count || 0), 0)
)

const surigaoOrders = computed(() => 
  surigaoZones.value.reduce((sum, z) => sum + (z.order_count || 0), 0)
)

const totalOrders = computed(() => cebuOrders.value + surigaoOrders.value)

const totalShippingRevenue = computed(() => 
  shippingZones.value.reduce((sum, z) => sum + (z.shipping_revenue || 0), 0)
)

const averageDeliveryDays = computed(() => {
  if (shippingZones.value.length === 0) return 0
  const total = shippingZones.value.reduce((sum, z) => sum + (z.estimated_delivery_days || 0), 0)
  return Math.round((total / shippingZones.value.length) * 10) / 10
})

const fetchShippingData = async () => {
  loading.value = true
  try {
    // Fetch shipping zones with order statistics
    const zonesResponse = await axios.get('/api/v1/shipping-zones')
    const zones = zonesResponse.data.data || []

    // Fetch orders to calculate zone statistics
    const ordersResponse = await axios.get('/api/v1/orders', {
      params: { include_zone: true }
    })
    const orders = ordersResponse.data.data || []

    // Calculate statistics for each zone
    shippingZones.value = zones.map(zone => {
      const zoneOrders = orders.filter(o => o.shipping_zone_id === zone.id)
      return {
        ...zone,
        order_count: zoneOrders.length,
        revenue: zoneOrders.reduce((sum, o) => sum + parseFloat(o.total_amount || 0), 0),
        shipping_revenue: zoneOrders.reduce((sum, o) => sum + parseFloat(o.shipping_cost || 0), 0),
        shipping_rate: `₱${zone.base_shipping_rate}`
      }
    })
  } catch (error) {
    console.error('Failed to fetch shipping data:', error)
    shippingZones.value = []
  } finally {
    loading.value = false
  }
}

const formatNumber = (num) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(num || 0)
}

const refreshData = () => {
  fetchShippingData()
}

const viewFullReport = () => {
  router.push({ name: 'admin.orders' })
}

onMounted(() => {
  fetchShippingData()
})
</script>

<style scoped>
.regional-shipping-widget {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.zone-stats-container {
  max-height: 500px;
  overflow-y: auto;
}

.zone-section {
  padding: 16px;
}

.zone-section-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 2px solid #f5f5f5;
}

.zone-section-title {
  font-weight: 600;
  font-size: 0.9rem;
  flex: 1;
}

.zone-items {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.zone-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 8px;
  transition: background 0.2s;
}

.zone-item:hover {
  background: #e9ecef;
}

.zone-item-sea {
  border-left: 3px solid #2196f3;
}

.zone-info {
  flex: 1;
}

.zone-name {
  font-weight: 600;
  font-size: 0.875rem;
  margin-bottom: 4px;
  display: flex;
  align-items: center;
  gap: 4px;
}

.zone-details {
  display: flex;
  gap: 12px;
  font-size: 0.75rem;
  color: #666;
}

.detail-badge {
  display: flex;
  align-items: center;
  gap: 4px;
}

.zone-revenue {
  font-weight: 700;
  font-size: 0.9rem;
  color: #1976d2;
}

.summary-bar {
  background: linear-gradient(135deg, #1976d2 0%, #2196f3 100%);
  padding: 16px;
  display: flex;
  justify-content: space-around;
  gap: 16px;
  color: white;
}

.summary-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.summary-label {
  font-size: 0.75rem;
  opacity: 0.9;
  margin-bottom: 4px;
}

.summary-value {
  font-size: 1.125rem;
  font-weight: 700;
}
</style>

<template>
  <v-card class="expiring-products-widget">
    <v-card-title class="d-flex align-center justify-space-between pa-4">
      <div class="d-flex align-center">
        <v-icon color="warning" class="me-2">mdi-clock-alert</v-icon>
        <span class="text-h6">Expiring Products</span>
      </div>
      <v-chip
        :color="getAlertColor()"
        size="small"
        variant="flat"
      >
        {{ expiringProducts.length }} items
      </v-chip>
    </v-card-title>

    <v-divider></v-divider>

    <v-card-text class="pa-0" style="max-height: 400px; overflow-y: auto;">
      <!-- Loading -->
      <div v-if="loading" class="py-8 text-center">
        <v-progress-circular
          indeterminate
          color="primary"
          size="48"
        ></v-progress-circular>
        <p class="text-grey mt-4">Loading products...</p>
      </div>

      <!-- Expiring Products List -->
      <div v-else-if="expiringProducts.length > 0">
        <div
          v-for="product in expiringProducts"
          :key="product.id"
          class="expiring-product-item"
          :class="`urgency-${getUrgencyLevel(product)}`"
        >
          <div class="product-image-wrapper">
            <img
              :src="product.primary_image?.thumbnail_url || '/images/placeholder-product.jpg'"
              :alt="product.name"
              class="product-image"
            />
            <v-chip
              :color="getFreshnessColor(product)"
              size="x-small"
              class="urgency-chip"
            >
              {{ getDaysLeft(product) }}
            </v-chip>
          </div>

          <div class="product-info">
            <div class="product-name">{{ product.name }}</div>
            <div class="product-details">
              <span class="detail-item">
                <v-icon size="12">mdi-fish</v-icon>
                {{ product.fish_type || 'Seafood' }}
              </span>
              <span class="detail-item">
                <v-icon size="12">mdi-package-variant</v-icon>
                {{ product.stock_quantity }} in stock
              </span>
            </div>
            <div class="expiration-info">
              <v-icon size="14" :color="getFreshnessColor(product)">mdi-calendar-clock</v-icon>
              <span>Expires: {{ formatDate(product.expiration_date) }}</span>
            </div>
          </div>

          <div class="product-actions">
            <v-chip
              :color="getGradeColor(product.freshness_grade)"
              size="small"
              variant="flat"
              class="mb-2"
            >
              Grade {{ product.freshness_grade }}
            </v-chip>
            <v-btn
              size="x-small"
              color="primary"
              variant="outlined"
              @click="viewProduct(product)"
            >
              <v-icon size="16">mdi-eye</v-icon>
              View
            </v-btn>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="empty-state py-8">
        <v-icon size="64" color="success">mdi-check-circle</v-icon>
        <h4 class="mt-4">All Products Fresh!</h4>
        <p class="text-grey text-caption">No products expiring in the next 7 days</p>
      </div>
    </v-card-text>

    <v-divider></v-divider>

    <v-card-actions class="pa-3">
      <v-btn
        variant="text"
        color="primary"
        size="small"
        @click="refreshData"
        :loading="loading"
      >
        <v-icon start>mdi-refresh</v-icon>
        Refresh
      </v-btn>
      <v-spacer></v-spacer>
      <v-btn
        variant="text"
        color="primary"
        size="small"
        @click="viewAllProducts"
      >
        View All
        <v-icon end>mdi-arrow-right</v-icon>
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const loading = ref(false)
const expiringProducts = ref([])

const fetchExpiringProducts = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/v1/products', {
      params: {
        expiring_soon: true,
        expiring_days: 7,
        include_inactive: true
      }
    })
    expiringProducts.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch expiring products:', error)
    expiringProducts.value = []
  } finally {
    loading.value = false
  }
}

const getDaysLeft = (product) => {
  const days = getDaysUntilExpiry(product)
  if (days === 0) return 'Today!'
  if (days === 1) return '1 day'
  if (days < 0) return 'Expired'
  return `${days} days`
}

const getDaysUntilExpiry = (product) => {
  if (!product.expiration_date) return null
  const expiry = new Date(product.expiration_date)
  const today = new Date()
  const diffTime = expiry - today
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return diffDays
}

const getUrgencyLevel = (product) => {
  const days = getDaysUntilExpiry(product)
  if (days <= 0) return 'critical'
  if (days <= 2) return 'high'
  if (days <= 5) return 'medium'
  return 'low'
}

const getFreshnessColor = (product) => {
  const days = getDaysUntilExpiry(product)
  if (days <= 0) return 'error'
  if (days <= 2) return 'error'
  if (days <= 5) return 'warning'
  return 'orange'
}

const getGradeColor = (grade) => {
  const colors = { A: 'success', B: 'warning', C: 'error' }
  return colors[grade] || 'grey'
}

const getAlertColor = () => {
  if (expiringProducts.value.length === 0) return 'success'
  if (expiringProducts.value.length > 5) return 'error'
  if (expiringProducts.value.length > 2) return 'warning'
  return 'orange'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const viewProduct = (product) => {
  router.push({ name: 'admin.products' })
}

const viewAllProducts = () => {
  router.push({ name: 'admin.products' })
}

const refreshData = () => {
  fetchExpiringProducts()
}

onMounted(() => {
  fetchExpiringProducts()
})
</script>

<style scoped>
.expiring-products-widget {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.expiring-product-item {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  gap: 12px;
  border-bottom: 1px solid #e0e0e0;
  transition: background-color 0.2s;
}

.expiring-product-item:hover {
  background-color: #f5f5f5;
}

.expiring-product-item:last-child {
  border-bottom: none;
}

.urgency-critical {
  background-color: #ffebee;
}

.urgency-high {
  background-color: #fff3e0;
}

.urgency-medium {
  background-color: #fff8e1;
}

.product-image-wrapper {
  position: relative;
  flex-shrink: 0;
}

.product-image {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: 8px;
  border: 2px solid #e0e0e0;
}

.urgency-chip {
  position: absolute;
  bottom: -4px;
  right: -4px;
  font-weight: 700;
  font-size: 0.65rem;
}

.product-info {
  flex: 1;
  min-width: 0;
}

.product-name {
  font-weight: 600;
  font-size: 0.875rem;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.product-details {
  display: flex;
  gap: 12px;
  font-size: 0.75rem;
  color: #666;
  margin-bottom: 4px;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 4px;
}

.expiration-info {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.75rem;
  font-weight: 600;
}

.product-actions {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}

.empty-state {
  text-align: center;
  color: #757575;
}
</style>

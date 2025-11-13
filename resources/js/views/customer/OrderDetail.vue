<template>
  <div class="order-detail-page pa-4">
    <v-container>
      <!-- Header -->
      <v-row class="mb-4">
        <v-col cols="12">
          <div class="d-flex align-center mb-2">
            <v-btn
              icon
              variant="text"
              @click="$router.back()"
              class="me-2"
            >
              <v-icon>mdi-arrow-left</v-icon>
            </v-btn>
            <div>
              <h1 class="text-h4 font-weight-bold">Order Details</h1>
              <v-breadcrumbs :items="breadcrumbs" class="pa-0"></v-breadcrumbs>
            </div>
          </div>
        </v-col>
      </v-row>

      <!-- Loading -->
      <v-progress-linear v-if="orderStore.loading" indeterminate color="primary"></v-progress-linear>

      <!-- Order Details -->
      <div v-else-if="order">
        <v-row>
          <!-- Order Information -->
          <v-col cols="12" md="8">
            <v-card class="mb-4">
              <v-card-title class="d-flex justify-space-between align-center">
                <span>Order #{{ order.order_number }}</span>
                <v-chip :color="getStatusColor(order.status)" size="large">
                  {{ getStatusText(order.status) }}
                </v-chip>
              </v-card-title>
              <v-divider></v-divider>

              <v-card-text>
                <v-row>
                  <v-col cols="12" md="6">
                    <div class="info-section">
                      <div class="info-label">Order Date</div>
                      <div class="info-value">{{ formatDate(order.created_at) }}</div>
                    </div>

                    <div class="info-section">
                      <div class="info-label">Delivery Address</div>
                      <div class="info-value">{{ order.delivery_address }}</div>
                    </div>

                    <div class="info-section">
                      <div class="info-label">Contact Number</div>
                      <div class="info-value">{{ order.contact_number }}</div>
                    </div>
                  </v-col>

                  <v-col cols="12" md="6">
                    <div class="info-section">
                      <div class="info-label">Total Amount</div>
                      <div class="info-value text-h6 font-weight-bold">₱{{ order.total_amount }}</div>
                    </div>

                    <div class="info-section">
                      <div class="info-label">Payment Method</div>
                      <div class="info-value">{{ order.payment_method.replace('_', ' ').toUpperCase() }}</div>
                    </div>

                    <div class="info-section">
                      <div class="info-label">Payment Status</div>
                      <v-chip :color="order.payment_status === 'paid' ? 'success' : 'warning'" size="small">
                        {{ order.payment_status.toUpperCase() }}
                      </v-chip>
                    </div>
                  </v-col>
                </v-row>

                <div v-if="order.notes" class="info-section mt-4">
                  <div class="info-label">Order Notes</div>
                  <div class="info-value">{{ order.notes }}</div>
                </div>
              </v-card-text>
            </v-card>

            <!-- Order Items -->
            <v-card>
              <v-card-title>Order Items ({{ order.items?.length || 0 }})</v-card-title>
              <v-divider></v-divider>

              <v-list>
                <v-list-item
                  v-for="item in order.items"
                  :key="item.id"
                  class="px-4"
                >
                  <template v-slot:prepend>
                    <v-avatar size="60" rounded>
                      <v-img :src="item.product?.primary_image?.path || '/images/placeholder-product.jpg'"></v-img>
                    </v-avatar>
                  </template>

                  <v-list-item-title>{{ item.product?.name }}</v-list-item-title>
                  <v-list-item-subtitle>
                    <div>Quantity: {{ item.quantity }}</div>
                    <div>Unit Price: ₱{{ item.price }}</div>
                    <div class="font-weight-bold">Subtotal: ₱{{ item.subtotal }}</div>
                  </v-list-item-subtitle>
                </v-list-item>
              </v-list>

              <v-divider></v-divider>
              <v-card-text class="text-right">
                <div class="text-h6 font-weight-bold">
                  Total: ₱{{ order.total_amount }}
                </div>
              </v-card-text>
            </v-card>
          </v-col>

          <!-- Order Progress -->
          <v-col cols="12" md="4">
            <v-card>
              <v-card-title>Order Progress</v-card-title>
              <v-divider></v-divider>

              <v-card-text>
                <v-stepper :model-value="currentStep" orientation="vertical">
                  <v-stepper-header>
                    <v-stepper-item
                      :complete="isStepComplete('pending')"
                      :color="getStepColor('pending')"
                      title="Order Placed"
                      subtitle="Your order has been received"
                      value="1"
                    >
                      <template v-slot:icon>
                        <v-icon>mdi-clipboard-check</v-icon>
                      </template>
                    </v-stepper-item>

                    <v-divider></v-divider>

                    <v-stepper-item
                      :complete="isStepComplete('processing')"
                      :color="getStepColor('processing')"
                      title="Processing"
                      subtitle="We're preparing your order"
                      value="2"
                    >
                      <template v-slot:icon>
                        <v-icon>mdi-package-variant</v-icon>
                      </template>
                    </v-stepper-item>

                    <v-divider></v-divider>

                    <v-stepper-item
                      :complete="isStepComplete('in_transit')"
                      :color="getStepColor('in_transit')"
                      title="In Transit"
                      subtitle="Your order is on the way"
                      value="3"
                    >
                      <template v-slot:icon>
                        <v-icon>mdi-truck-delivery</v-icon>
                      </template>
                    </v-stepper-item>

                    <v-divider></v-divider>

                    <v-stepper-item
                      :complete="isStepComplete('delivered')"
                      :color="getStepColor('delivered')"
                      title="Delivered"
                      subtitle="Order has been delivered"
                      value="4"
                    >
                      <template v-slot:icon>
                        <v-icon>mdi-check-circle</v-icon>
                      </template>
                    </v-stepper-item>
                  </v-stepper-header>
                </v-stepper>

                <v-alert
                  v-if="order.status === 'cancelled'"
                  type="error"
                  variant="tonal"
                  class="mt-4"
                  density="compact"
                >
                  This order has been cancelled
                </v-alert>
              </v-card-text>

              <v-card-actions v-if="order.status === 'pending' || order.status === 'processing'">
                <v-btn
                  color="error"
                  variant="outlined"
                  block
                  prepend-icon="mdi-cancel"
                  @click="confirmCancelOrder"
                >
                  Cancel Order
                </v-btn>
              </v-card-actions>
            </v-card>
          </v-col>
        </v-row>
      </div>

      <!-- Error State -->
      <v-row v-else-if="orderStore.error">
        <v-col cols="12" class="text-center py-16">
          <v-icon size="64" color="error">mdi-alert-circle</v-icon>
          <p class="text-h6 mt-4">Error Loading Order</p>
          <p class="text-grey mb-4">{{ orderStore.error }}</p>
          <v-btn color="primary" @click="loadOrder">
            Try Again
          </v-btn>
        </v-col>
      </v-row>

      <!-- Not Found -->
      <v-row v-else>
        <v-col cols="12" class="text-center py-16">
          <v-icon size="64" color="grey">mdi-package-variant-closed</v-icon>
          <p class="text-h6 mt-4">Order Not Found</p>
          <p class="text-grey mb-4">The order you're looking for doesn't exist or you don't have access to it.</p>
          <v-btn color="primary" to="/customer/orders">
            Back to Orders
          </v-btn>
        </v-col>
      </v-row>
    </v-container>

    <!-- Cancel Order Dialog -->
    <v-dialog v-model="cancelDialog" max-width="400">
      <v-card>
        <v-card-title>Cancel Order?</v-card-title>
        <v-card-text>
          Are you sure you want to cancel order #{{ order?.order_number }}?
          This action cannot be undone.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="cancelDialog = false">No, Keep It</v-btn>
          <v-btn color="error" variant="flat" :loading="cancelling" @click="cancelOrder">
            Yes, Cancel
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color">
      {{ snackbar.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useOrderStore } from '@/stores/orders'

const router = useRouter()
const route = useRoute()
const orderStore = useOrderStore()

const props = defineProps({
  id: {
    type: [String, Number],
    required: true
  }
})

const cancelDialog = ref(false)
const cancelling = ref(false)
const snackbar = ref({
  show: false,
  message: '',
  color: 'success'
})

const order = computed(() => orderStore.currentOrder)

const breadcrumbs = [
  { title: 'My Orders', to: '/customer/orders' },
  { title: `Order #${order.value?.order_number || props.id}`, disabled: true }
]

const statusOrder = ['pending', 'processing', 'in_transit', 'delivered']

const currentStep = computed(() => {
  if (!order.value) return 1
  const index = statusOrder.indexOf(order.value.status)
  return index + 1
})

const getStatusColor = (status) => {
  const colors = {
    pending: 'warning',
    processing: 'info',
    in_transit: 'primary',
    delivered: 'success',
    cancelled: 'error'
  }
  return colors[status] || 'grey'
}

const getStatusText = (status) => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const isStepComplete = (step) => {
  if (!order.value) return false
  const currentIndex = statusOrder.indexOf(order.value.status)
  const stepIndex = statusOrder.indexOf(step)
  return currentIndex >= stepIndex
}

const getStepColor = (step) => {
  if (!order.value) return 'grey-lighten-1'
  
  if (order.value.status === 'cancelled') return 'error'
  
  const currentIndex = statusOrder.indexOf(order.value.status)
  const stepIndex = statusOrder.indexOf(step)
  
  if (currentIndex >= stepIndex) {
    return 'success'
  }
  return 'grey-lighten-1'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const loadOrder = async () => {
  try {
    await orderStore.fetchOrder(props.id)
  } catch (error) {
    console.error('Failed to load order:', error)
    snackbar.value = {
      show: true,
      message: 'Failed to load order details',
      color: 'error'
    }
  }
}

const confirmCancelOrder = () => {
  cancelDialog.value = true
}

const cancelOrder = async () => {
  cancelling.value = true
  try {
    await orderStore.cancelOrder(order.value.id)
    cancelDialog.value = false
    snackbar.value = {
      show: true,
      message: 'Order cancelled successfully',
      color: 'success'
    }
    // Reload order to show updated status
    await loadOrder()
  } catch (error) {
    snackbar.value = {
      show: true,
      message: error.response?.data?.message || 'Failed to cancel order',
      color: 'error'
    }
  } finally {
    cancelling.value = false
  }
}

onMounted(loadOrder)
</script>

<style scoped>
.info-section {
  margin-bottom: 16px;
}

.info-label {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 4px;
}

.info-value {
  font-weight: 500;
  color: #1f2937;
}
</style>
<template>
  <div class="orders-page pa-4">
    <v-container>
      <v-row class="mb-4">
        <v-col cols="12">
          <h1 class="text-h4 font-weight-bold">My Orders</h1>
          <p class="text-subtitle-1 text-grey-darken-1">Track and manage your orders</p>
        </v-col>
      </v-row>

      <!-- Filter Tabs -->
      <v-row class="mb-4">
        <v-col cols="12">
          <v-chip-group v-model="selectedStatus" mandatory>
            <v-chip value="all" filter>All Orders</v-chip>
            <v-chip value="pending" filter>Pending</v-chip>
            <v-chip value="processing" filter>Processing</v-chip>
            <v-chip value="in_transit" filter>In Transit</v-chip>
            <v-chip value="delivered" filter>Delivered</v-chip>
          </v-chip-group>
        </v-col>
      </v-row>

      <!-- Loading -->
      <v-progress-linear v-if="orderStore.loading" indeterminate color="primary"></v-progress-linear>

      <!-- Orders List -->
      <v-row v-else-if="filteredOrders.length > 0">
        <v-col v-for="order in filteredOrders" :key="order.id" cols="12">
          <v-card hover @click="viewOrderDetails(order)">
            <v-card-title class="d-flex justify-space-between align-center">
              <div>
                <span class="text-h6">Order #{{ order.order_number }}</span>
                <v-chip :color="getStatusColor(order.status)" size="small" class="ml-2">
                  {{ getStatusText(order.status) }}
                </v-chip>
              </div>
              <span class="text-h6 font-weight-bold">₱{{ order.total_amount }}</span>
            </v-card-title>

            <v-divider></v-divider>

            <v-card-text>
              <v-row>
                <v-col cols="12" md="6">
                  <div class="text-caption text-grey">Order Date</div>
                  <div>{{ formatDate(order.created_at) }}</div>
                  
                  <div class="text-caption text-grey mt-2">Delivery Address</div>
                  <div>{{ order.delivery_address }}</div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="text-caption text-grey">Items</div>
                  <div v-for="item in order.items?.slice(0, 2)" :key="item.id" class="text-body-2">
                    {{ item.quantity }}x {{ item.product?.name }}
                  </div>
                  <div v-if="order.items?.length > 2" class="text-caption">
                    +{{ order.items.length - 2 }} more items
                  </div>
                </v-col>
              </v-row>

              <!-- Order Progress -->
              <v-divider class="my-3"></v-divider>
              <div class="order-progress">
                <v-stepper-header>
                  <v-stepper-item
                    :complete="isStepComplete(order, 'pending')"
                    :color="getStepColor(order, 'pending')"
                    title="Pending"
                    value="1"
                  ></v-stepper-item>

                  <v-divider></v-divider>

                  <v-stepper-item
                    :complete="isStepComplete(order, 'processing')"
                    :color="getStepColor(order, 'processing')"
                    title="Processing"
                    value="2"
                  ></v-stepper-item>

                  <v-divider></v-divider>

                  <v-stepper-item
                    :complete="isStepComplete(order, 'in_transit')"
                    :color="getStepColor(order, 'in_transit')"
                    title="In Transit"
                    value="3"
                  ></v-stepper-item>

                  <v-divider></v-divider>

                  <v-stepper-item
                    :complete="isStepComplete(order, 'delivered')"
                    :color="getStepColor(order, 'delivered')"
                    title="Delivered"
                    value="4"
                  ></v-stepper-item>
                </v-stepper-header>
              </div>
            </v-card-text>

            <v-card-actions>
              <v-btn
                variant="text"
                prepend-icon="mdi-eye"
                @click.stop="viewOrderDetails(order)"
              >
                View Details
              </v-btn>
              <v-spacer></v-spacer>
              <v-btn
                v-if="order.status === 'pending' || order.status === 'processing'"
                variant="text"
                color="error"
                prepend-icon="mdi-cancel"
                @click.stop="confirmCancelOrder(order)"
              >
                Cancel Order
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>

      <!-- Empty State -->
      <v-row v-else>
        <v-col cols="12" class="text-center py-16">
          <v-icon size="64" color="grey">mdi-package-variant</v-icon>
          <p class="text-h6 mt-4">No orders found</p>
          <p class="text-grey">Start shopping to place your first order!</p>
          <v-btn color="primary" class="mt-4" to="/customer/products">
            Browse Products
          </v-btn>
        </v-col>
      </v-row>
    </v-container>

    <!-- Cancel Order Dialog -->
    <v-dialog v-model="cancelDialog" max-width="400">
      <v-card>
        <v-card-title>Cancel Order?</v-card-title>
        <v-card-text>
          Are you sure you want to cancel order #{{ selectedOrder?.order_number }}?
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
import { useRouter } from 'vue-router'
import { useOrderStore } from '@/stores/orders'

const router = useRouter()
const orderStore = useOrderStore()

const selectedStatus = ref('all')
const cancelDialog = ref(false)
const selectedOrder = ref(null)
const cancelling = ref(false)

const snackbar = ref({
  show: false,
  message: '',
  color: 'success'
})

const filteredOrders = computed(() => {
  if (selectedStatus.value === 'all') {
    return orderStore.orders
  }
  return orderStore.orders.filter(o => o.status === selectedStatus.value)
})

const statusOrder = ['pending', 'processing', 'in_transit', 'delivered']

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

const isStepComplete = (order, step) => {
  const currentIndex = statusOrder.indexOf(order.status)
  const stepIndex = statusOrder.indexOf(step)
  return currentIndex >= stepIndex
}

const getStepColor = (order, step) => {
  const currentIndex = statusOrder.indexOf(order.status)
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
    day: 'numeric'
  })
}

const viewOrderDetails = (order) => {
  router.push({ name: 'customer.order-detail', params: { id: order.id } })
}

const confirmCancelOrder = (order) => {
  selectedOrder.value = order
  cancelDialog.value = true
}

const cancelOrder = async () => {
  cancelling.value = true
  try {
    await orderStore.cancelOrder(selectedOrder.value.id)
    cancelDialog.value = false
    snackbar.value = {
      show: true,
      message: 'Order cancelled successfully',
      color: 'success'
    }
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

onMounted(async () => {
  await orderStore.fetchOrders()
})
</script>

<style scoped>
.order-progress {
  margin-top: 16px;
}
</style>


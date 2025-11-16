<template>
  <div class="supplier-dashboard pa-4">
    <v-container fluid>
      <v-row class="mb-4">
        <v-col cols="12">
          <h1 class="text-h4 font-weight-bold">Supplier Dashboard</h1>
          <p class="text-subtitle-1 text-grey-darken-1">Overview of your products and orders</p>
        </v-col>
      </v-row>

      <!-- Statistics Cards -->
      <v-row class="mb-4">
        <v-col cols="12" md="3">
          <v-card color="primary" variant="tonal">
            <v-card-text>
              <div class="d-flex align-center">
                <div class="flex-grow-1">
                  <div class="text-h4 font-weight-bold">{{ supplierOrderStore.statistics.total_orders }}</div>
                  <div class="text-body-1">Total Orders</div>
                </div>
                <v-icon size="40" color="primary">mdi-shopping</v-icon>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="3">
          <v-card color="success" variant="tonal">
            <v-card-text>
              <div class="d-flex align-center">
                <div class="flex-grow-1">
                  <div class="text-h4 font-weight-bold">₱{{ formatCurrency(supplierOrderStore.statistics.total_revenue) }}</div>
                  <div class="text-body-1">Total Revenue</div>
                </div>
                <v-icon size="40" color="success">mdi-currency-php</v-icon>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="3">
          <v-card color="info" variant="tonal">
            <v-card-text>
              <div class="d-flex align-center">
                <div class="flex-grow-1">
                  <div class="text-h4 font-weight-bold">{{ supplierOrderStore.statistics.total_items_sold }}</div>
                  <div class="text-body-1">Items Sold</div>
                </div>
                <v-icon size="40" color="info">mdi-package-variant</v-icon>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="3">
          <v-card color="warning" variant="tonal">
            <v-card-text>
              <div class="d-flex align-center">
                <div class="flex-grow-1">
                  <div class="text-h4 font-weight-bold">{{ supplierOrderStore.statistics.pending_orders }}</div>
                  <div class="text-body-1">Pending Orders</div>
                </div>
                <v-icon size="40" color="warning">mdi-clock-outline</v-icon>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-row>
        <!-- Recent Orders -->
        <v-col cols="12" md="8">
          <v-card>
            <v-card-title class="d-flex justify-space-between align-center">
              <span>Recent Orders</span>
              <v-btn
                color="primary"
                variant="text"
                size="small"
                to="/supplier/orders"
              >
                View All
              </v-btn>
            </v-card-title>
            <v-divider></v-divider>

            <div v-if="supplierOrderStore.loading" class="pa-4">
              <v-skeleton-loader type="list-item-three-line" v-for="i in 3" :key="i"></v-skeleton-loader>
            </div>

            <v-list v-else-if="supplierOrderStore.recentOrders.length > 0">
              <v-list-item
                v-for="order in supplierOrderStore.recentOrders"
                :key="order.id"
                @click="viewOrder(order)"
                class="cursor-pointer"
              >
                <template v-slot:prepend>
                  <v-avatar :color="getStatusColor(order.status)" size="40">
                    <v-icon color="white">mdi-receipt</v-icon>
                  </v-avatar>
                </template>

                <v-list-item-title>Order #{{ order.order_number }}</v-list-item-title>
                <v-list-item-subtitle>
                  <div>{{ order.customer?.name }} • {{ formatDate(order.created_at) }}</div>
                  <div>{{ order.supplier_item_count }} items • ₱{{ formatCurrency(order.supplier_total) }}</div>
                </v-list-item-subtitle>

                <template v-slot:append>
                  <v-chip :color="getStatusColor(order.status)" size="small">
                    {{ getStatusText(order.status) }}
                  </v-chip>
                </template>
              </v-list-item>
            </v-list>

            <div v-else class="pa-8 text-center">
              <v-icon size="64" color="grey">mdi-package-variant-closed</v-icon>
              <p class="text-h6 mt-4">No Orders Yet</p>
              <p class="text-grey">Orders containing your products will appear here</p>
            </div>
          </v-card>
        </v-col>

        <!-- Order Status Breakdown -->
        <v-col cols="12" md="4">
          <v-card>
            <v-card-title>Order Status</v-card-title>
            <v-divider></v-divider>
            <v-card-text>
              <div class="status-item mb-3">
                <div class="d-flex justify-space-between align-center">
                  <div class="d-flex align-center">
                    <v-icon color="warning" class="me-2">mdi-clock-outline</v-icon>
                    <span>Pending</span>
                  </div>
                  <span class="font-weight-bold">{{ supplierOrderStore.statistics.pending_orders }}</span>
                </div>
              </div>

              <div class="status-item mb-3">
                <div class="d-flex justify-space-between align-center">
                  <div class="d-flex align-center">
                    <v-icon color="info" class="me-2">mdi-cog</v-icon>
                    <span>Processing</span>
                  </div>
                  <span class="font-weight-bold">{{ supplierOrderStore.statistics.processing_orders }}</span>
                </div>
              </div>

              <div class="status-item mb-3">
                <div class="d-flex justify-space-between align-center">
                  <div class="d-flex align-center">
                    <v-icon color="success" class="me-2">mdi-check-circle</v-icon>
                    <span>Completed</span>
                  </div>
                  <span class="font-weight-bold">{{ supplierOrderStore.statistics.completed_orders }}</span>
                </div>
              </div>

              <div class="status-item">
                <div class="d-flex justify-space-between align-center">
                  <div class="d-flex align-center">
                    <v-icon color="error" class="me-2">mdi-cancel</v-icon>
                    <span>Cancelled</span>
                  </div>
                  <span class="font-weight-bold">{{ supplierOrderStore.statistics.cancelled_orders }}</span>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useSupplierOrderStore } from '@/stores/supplierOrders'

const router = useRouter()
const supplierOrderStore = useSupplierOrderStore()

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

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatCurrency = (amount) => {
  return parseFloat(amount || 0).toFixed(2)
}

const viewOrder = (order) => {
  router.push(`/supplier/orders/${order.id}`)
}

onMounted(async () => {
  await Promise.all([
    supplierOrderStore.fetchStatistics(),
    supplierOrderStore.fetchRecentOrders()
  ])
})
</script>

<style scoped>
.supplier-dashboard {
  background-color: #f5f5f5;
  min-height: 100vh;
}

.status-item {
  padding: 8px 0;
}

.cursor-pointer {
  cursor: pointer;
}
</style>

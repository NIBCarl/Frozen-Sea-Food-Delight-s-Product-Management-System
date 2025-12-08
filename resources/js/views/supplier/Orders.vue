<template>
  <div class="supplier-orders pa-4">
    <v-container fluid>
      <v-row class="mb-4">
        <v-col cols="12">
          <h1 class="text-h4 font-weight-bold">My Product Orders</h1>
          <p class="text-subtitle-1 text-grey-darken-1">Track orders containing your products</p>
        </v-col>
      </v-row>

      <!-- Statistics Cards -->
      <v-row class="mb-4">
        <v-col cols="12" md="3">
          <v-card color="primary" variant="tonal">
            <v-card-text>
              <div class="text-h3 font-weight-bold">{{ supplierOrderStore.statistics.total_orders }}</div>
              <div class="text-body-1">Total Orders</div>
              <div class="text-caption">Orders with your products</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="3">
          <v-card color="warning" variant="tonal">
            <v-card-text>
              <div class="text-h3 font-weight-bold">{{ supplierOrderStore.statistics.pending_orders }}</div>
              <div class="text-body-1">Pending</div>
              <div class="text-caption">Awaiting processing</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="3">
          <v-card color="success" variant="tonal">
            <v-card-text>
              <div class="text-h3 font-weight-bold">{{ supplierOrderStore.statistics.completed_orders }}</div>
              <div class="text-body-1">Completed</div>
              <div class="text-caption">Successfully delivered</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="3">
          <v-card color="info" variant="tonal">
            <v-card-text>
              <div class="text-h3 font-weight-bold">₱{{ formatCurrency(supplierOrderStore.statistics.total_revenue) }}</div>
              <div class="text-body-1">Total Revenue</div>
              <div class="text-caption">From your products</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Filter Chips -->
      <v-row class="mb-4">
        <v-col cols="12">
          <v-chip-group v-model="selectedStatus" mandatory>
            <v-chip value="all" filter>All Orders</v-chip>
            <v-chip value="pending" filter color="warning">Pending</v-chip>
            <v-chip value="processing" filter color="info">Processing</v-chip>
            <v-chip value="in_transit" filter color="primary">In Transit</v-chip>
            <v-chip value="delivered" filter color="success">Delivered</v-chip>
            <v-chip value="cancelled" filter color="error">Cancelled</v-chip>
          </v-chip-group>
        </v-col>
      </v-row>

      <!-- Orders Table -->
      <v-card>
        <v-data-table
          :headers="headers"
          :items="filteredOrders"
          :loading="supplierOrderStore.loading"
          item-value="id"
        >
          <!-- Order Number -->
          <template v-slot:item.order_number="{ item }">
            <div>
              <div class="font-weight-bold">#{{ item.order_number }}</div>
              <div class="text-caption">{{ formatDate(item.created_at) }}</div>
            </div>
          </template>

          <!-- Customer -->
          <template v-slot:item.customer="{ item }">
            <div>
              <div class="font-weight-medium">{{ item.customer?.name }}</div>
              <div class="text-caption">{{ item.contact_number }}</div>
            </div>
          </template>

          <!-- Your Products -->
          <template v-slot:item.products="{ item }">
            <div>
              <div v-for="orderItem in item.items" :key="orderItem.id" class="mb-1">
                <div class="text-body-2">{{ orderItem.product?.name }}</div>
                <div class="text-caption">Qty: {{ orderItem.quantity }} × ₱{{ orderItem.price }}</div>
              </div>
            </div>
          </template>

          <!-- Your Revenue -->
          <template v-slot:item.supplier_total="{ item }">
            <div class="font-weight-bold">₱{{ formatCurrency(item.supplier_total) }}</div>
          </template>

          <!-- Status -->
          <template v-slot:item.status="{ item }">
            <v-chip :color="getStatusColor(item.status)" size="small">
              {{ getStatusText(item.status) }}
            </v-chip>
          </template>

          <!-- Payment Status -->
          <template v-slot:item.payment_status="{ item }">
            <v-chip :color="getPaymentStatusColor(item.payment_status)" size="small">
              {{ getPaymentStatusText(item.payment_status) }}
            </v-chip>
          </template>

          <!-- Delivery Driver -->
          <template v-slot:item.delivery_driver="{ item }">
            <div v-if="item.delivery?.delivery_personnel">
              <div class="font-weight-medium">{{ item.delivery.delivery_personnel.name }}</div>
              <v-chip :color="getDeliveryStatusColor(item.delivery.status)" size="x-small" class="mt-1">
                {{ formatDeliveryStatus(item.delivery.status) }}
              </v-chip>
            </div>
            <div v-else class="text-grey-darken-1">
              <v-icon size="small" class="mr-1">mdi-account-off</v-icon>
              Not Assigned
            </div>
          </template>

          <!-- Actions -->
          <template v-slot:item.actions="{ item }">
            <v-menu>
              <template v-slot:activator="{ props }">
                <v-btn icon="mdi-dots-vertical" size="small" variant="text" v-bind="props"></v-btn>
              </template>
              <v-list density="compact">
                <v-list-item prepend-icon="mdi-eye" @click="viewOrder(item)">
                  View Details
                </v-list-item>
                
                <v-list-item 
                  v-if="item.status === 'pending' && item.payment_status === 'paid'"
                  prepend-icon="mdi-check-circle"
                  @click="markAsReady(item)"
                >
                  Mark as Ready
                </v-list-item>
                
                <v-list-item 
                  v-if="item.status === 'pending'"
                  prepend-icon="mdi-alert-circle"
                  @click="reportIssue(item)"
                >
                  Report Issue
                </v-list-item>
                
                <v-list-item 
                  v-if="canContactCustomer(item)"
                  prepend-icon="mdi-phone"
                  @click="contactCustomer(item)"
                >
                  Contact Customer
                </v-list-item>
                
                <v-list-item 
                  prepend-icon="mdi-download"
                  @click="exportOrderDetails(item)"
                >
                  Export Details
                </v-list-item>
                
                <v-list-item 
                  v-if="item.status === 'pending'"
                  prepend-icon="mdi-clock-alert"
                  @click="requestDelay(item)"
                >
                  Request Delay
                </v-list-item>
              </v-list>
            </v-menu>
          </template>
        </v-data-table>
      </v-card>
    </v-container>

    <!-- Report Issue Dialog -->
    <v-dialog v-model="issueDialog" max-width="600">
      <v-card>
        <v-card-title>Report Issue - Order #{{ selectedOrder?.order_number }}</v-card-title>
        <v-divider></v-divider>
        <v-card-text>
          <v-form ref="issueForm" v-model="issueFormValid">
            <v-select
              v-model="issueData.issue_type"
              :items="issueTypes"
              label="Issue Type"
              variant="outlined"
              :rules="[v => !!v || 'Issue type is required']"
            ></v-select>

            <v-select
              v-model="issueData.severity"
              :items="severityLevels"
              label="Severity Level"
              variant="outlined"
              :rules="[v => !!v || 'Severity is required']"
            ></v-select>

            <v-textarea
              v-model="issueData.description"
              label="Issue Description"
              variant="outlined"
              rows="4"
              :rules="[v => !!v || 'Description is required', v => v.length <= 1000 || 'Description must be less than 1000 characters']"
              counter="1000"
            ></v-textarea>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="issueDialog = false">Cancel</v-btn>
          <v-btn 
            color="error" 
            :loading="reportingIssue"
            :disabled="!issueFormValid"
            @click="submitIssueReport"
          >
            Report Issue
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
import { useSupplierOrderStore } from '@/stores/supplierOrders'

const router = useRouter()
const supplierOrderStore = useSupplierOrderStore()

const selectedStatus = ref('all')
const issueDialog = ref(false)
const issueFormValid = ref(false)
const reportingIssue = ref(false)
const selectedOrder = ref(null)

const issueData = ref({
  issue_type: '',
  severity: '',
  description: ''
})

const issueTypes = [
  { title: 'Stock Shortage', value: 'stock_shortage' },
  { title: 'Quality Issue', value: 'quality_issue' },
  { title: 'Delivery Delay', value: 'delivery_delay' },
  { title: 'Other', value: 'other' }
]

const severityLevels = [
  { title: 'Low', value: 'low' },
  { title: 'Medium', value: 'medium' },
  { title: 'High', value: 'high' },
  { title: 'Critical', value: 'critical' }
]

const snackbar = ref({
  show: false,
  message: '',
  color: 'success'
})

const headers = [
  { title: 'Order', key: 'order_number' },
  { title: 'Customer', key: 'customer' },
  { title: 'Your Products', key: 'products', sortable: false },
  { title: 'Your Revenue', key: 'supplier_total' },
  { title: 'Status', key: 'status' },
  { title: 'Payment', key: 'payment_status' },
  { title: 'Delivery Driver', key: 'delivery_driver', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false }
]

const filteredOrders = computed(() => {
  if (selectedStatus.value === 'all') {
    return supplierOrderStore.orders
  }
  return supplierOrderStore.orders.filter(order => order.status === selectedStatus.value)
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

const getPaymentStatusColor = (status) => {
  switch (status) {
    case 'paid': return 'success'
    case 'pending': return 'warning'
    case 'verification_pending': return 'info'
    case 'verification_failed': return 'error'
    default: return 'grey'
  }
}

const getPaymentStatusText = (status) => {
  switch (status) {
    case 'paid': return 'PAID'
    case 'pending': return 'PENDING'
    case 'verification_pending': return 'VERIFYING'
    case 'verification_failed': return 'FAILED'
    default: return status?.toUpperCase() || 'UNKNOWN'
  }
}

const getDeliveryStatusColor = (status) => {
  const colors = {
    scheduled: 'info',
    out_for_delivery: 'warning',
    delivered: 'success',
    failed: 'error'
  }
  return colors[status] || 'grey'
}

const formatDeliveryStatus = (status) => {
  if (!status) return 'Pending'
  return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
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

const canContactCustomer = (order) => {
  return ['pending', 'processing'].includes(order.status)
}

const markAsReady = async (order) => {
  try {
    await supplierOrderStore.markOrderAsReady(order.id)
    snackbar.value = {
      show: true,
      message: `Order #${order.order_number} marked as ready for processing`,
      color: 'success'
    }
    // Refresh orders
    await fetchOrdersWithFilter()
  } catch (error) {
    snackbar.value = {
      show: true,
      message: 'Failed to mark order as ready',
      color: 'error'
    }
  }
}

const reportIssue = (order) => {
  selectedOrder.value = order
  issueData.value = {
    issue_type: '',
    severity: '',
    description: ''
  }
  issueDialog.value = true
}

const submitIssueReport = async () => {
  if (!issueFormValid.value) return
  
  reportingIssue.value = true
  try {
    await supplierOrderStore.reportOrderIssue(selectedOrder.value.id, issueData.value)
    
    snackbar.value = {
      show: true,
      message: `Issue reported for Order #${selectedOrder.value.order_number}`,
      color: 'success'
    }
    
    issueDialog.value = false
    await fetchOrdersWithFilter()
  } catch (error) {
    snackbar.value = {
      show: true,
      message: 'Failed to report issue',
      color: 'error'
    }
  } finally {
    reportingIssue.value = false
  }
}

const contactCustomer = (order) => {
  // Open contact customer dialog or redirect to messaging
  const phoneNumber = order.contact_number
  if (phoneNumber) {
    window.open(`tel:${phoneNumber}`, '_blank')
  } else {
    snackbar.value = {
      show: true,
      message: 'Customer contact information not available',
      color: 'warning'
    }
  }
}

const exportOrderDetails = (order) => {
  // Export order details as PDF or CSV
  const orderData = {
    orderNumber: order.order_number,
    customer: order.customer?.name,
    items: order.items.map(item => ({
      product: item.product?.name,
      quantity: item.quantity,
      price: item.price,
      subtotal: item.subtotal
    })),
    total: order.supplier_total,
    status: order.status,
    paymentStatus: order.payment_status,
    orderDate: formatDate(order.created_at)
  }
  
  // Create and download JSON file (in a real app, this would be PDF)
  const dataStr = JSON.stringify(orderData, null, 2)
  const dataBlob = new Blob([dataStr], { type: 'application/json' })
  const url = URL.createObjectURL(dataBlob)
  const link = document.createElement('a')
  link.href = url
  link.download = `order-${order.order_number}-details.json`
  link.click()
  URL.revokeObjectURL(url)
  
  snackbar.value = {
    show: true,
    message: `Order #${order.order_number} details exported`,
    color: 'success'
  }
}

const requestDelay = (order) => {
  // Open delay request dialog
  snackbar.value = {
    show: true,
    message: `Delay request for Order #${order.order_number} - Feature coming soon`,
    color: 'info'
  }
}

// Watch for status filter changes
const fetchOrdersWithFilter = async () => {
  try {
    const filters = selectedStatus.value !== 'all' ? { status: selectedStatus.value } : {}
    await supplierOrderStore.fetchOrders(filters)
  } catch (error) {
    snackbar.value = {
      show: true,
      message: 'Failed to fetch orders',
      color: 'error'
    }
  }
}

// Watch selectedStatus changes
const unwatchStatus = computed(() => selectedStatus.value)
const watchStatus = () => {
  fetchOrdersWithFilter()
}

onMounted(async () => {
  await Promise.all([
    supplierOrderStore.fetchOrders(),
    supplierOrderStore.fetchStatistics()
  ])
})
</script>

<style scoped>
.supplier-orders {
  background-color: #f5f5f5;
  min-height: 100vh;
}
</style>

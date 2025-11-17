<template>
  <div class="admin-orders pa-4">
    <v-container fluid>
      <v-row class="mb-4">
        <v-col cols="12" class="d-flex justify-space-between align-center">
          <div>
            <h1 class="text-h4 font-weight-bold">Order Management</h1>
            <p class="text-subtitle-1 text-grey-darken-1">Manage all customer orders</p>
          </div>
        </v-col>
      </v-row>

      <!-- Filters Row -->
      <v-row class="mb-4">
        <v-col cols="12" md="8">
          <v-chip-group v-model="selectedStatus" mandatory>
            <v-chip value="all" filter>All Orders</v-chip>
            <v-chip value="pending" filter color="warning">Pending</v-chip>
            <v-chip value="processing" filter color="info">Processing</v-chip>
            <v-chip value="in_transit" filter color="primary">In Transit</v-chip>
            <v-chip value="delivered" filter color="success">Delivered</v-chip>
          </v-chip-group>
        </v-col>
        
        <!-- Regional Zone Filter -->
        <v-col cols="12" md="4">
          <v-select
            v-model="selectedRegion"
            :items="regionFilters"
            label="Filter by Region"
            density="compact"
            variant="outlined"
            hide-details
            clearable
          >
            <template v-slot:prepend-inner>
              <v-icon size="20">mdi-map-marker-radius</v-icon>
            </template>
          </v-select>
        </v-col>
      </v-row>

      <!-- Data Table -->
      <v-card>
        <v-data-table
          :headers="headers"
          :items="filteredOrders"
          :loading="orderStore.loading"
          item-value="id"
          class="elevation-1"
        >
          <!-- Order Number -->
          <template v-slot:item.order_number="{ item }">
            <span class="font-weight-bold">#{{ item.order_number }}</span>
          </template>

          <!-- Customer -->
          <template v-slot:item.customer="{ item }">
            <div>
              <div class="font-weight-medium">{{ item.customer?.name }}</div>
              <div class="text-caption">{{ item.contact_number }}</div>
            </div>
          </template>

          <!-- Items -->
          <template v-slot:item.items="{ item }">
            <div v-for="orderItem in item.items?.slice(0, 2)" :key="orderItem.id" class="text-body-2">
              {{ orderItem.quantity }}x {{ orderItem.product?.name }}
            </div>
            <div v-if="item.items?.length > 2" class="text-caption text-grey">
              +{{ item.items.length - 2 }} more
            </div>
          </template>

          <!-- Shipping Zone -->
          <template v-slot:item.shipping_zone="{ item }">
            <div v-if="item.shipping_zone" class="d-flex flex-column">
              <div class="font-weight-medium text-body-2">
                {{ item.shipping_zone.name }}
              </div>
              <div class="text-caption text-grey d-flex align-center">
                <v-icon v-if="item.shipping_zone.requires_sea_transport" size="12" class="mr-1" color="info">
                  mdi-ferry
                </v-icon>
                {{ item.shipping_zone.province }}
              </div>
              <v-chip 
                v-if="item.shipping_cost > 0" 
                size="x-small" 
                color="primary" 
                variant="outlined"
                class="mt-1"
              >
                +₱{{ item.shipping_cost }}
              </v-chip>
            </div>
            <span v-else class="text-grey text-caption">No zone</span>
          </template>

          <!-- Payment -->
          <template v-slot:item.payment="{ item }">
            <div class="d-flex flex-column">
              <v-chip 
                :color="item.payment_method === 'gcash' ? 'primary' : 'secondary'" 
                size="x-small"
                class="mb-1"
              >
                {{ item.payment_method === 'gcash' ? 'GCash' : 'COD' }}
              </v-chip>
              <v-chip 
                :color="getPaymentStatusColor(item.payment_status)" 
                size="x-small"
              >
                {{ getPaymentStatusText(item.payment_status) }}
              </v-chip>
            </div>
          </template>

          <!-- Status -->
          <template v-slot:item.status="{ item }">
            <v-chip :color="getStatusColor(item.status)" size="small">
              {{ getStatusText(item.status) }}
            </v-chip>
          </template>

          <!-- Total Amount -->
          <template v-slot:item.total_amount="{ item }">
            <span class="font-weight-bold">₱{{ item.total_amount }}</span>
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
                  v-if="item.payment_method === 'gcash' && item.payment_status === 'verification_pending'"
                  prepend-icon="mdi-check-circle"
                  @click="verifyPayment(item, 'approve')"
                >
                  Approve Payment
                </v-list-item>
                <v-list-item
                  v-if="item.payment_method === 'gcash' && item.payment_status === 'verification_pending'"
                  prepend-icon="mdi-close-circle"
                  @click="verifyPayment(item, 'reject')"
                >
                  Reject Payment
                </v-list-item>
                <v-list-item
                  v-if="item.payment_method === 'gcash' && item.payment_receipt_path"
                  prepend-icon="mdi-receipt"
                  @click="viewReceipt(item)"
                >
                  View Receipt
                </v-list-item>
                <v-list-item
                  v-if="item.status === 'pending' && (item.payment_method === 'cash_on_delivery' || item.payment_status === 'paid')"
                  prepend-icon="mdi-check"
                  @click="updateOrderStatus(item, 'processing')"
                >
                  Mark as Processing
                </v-list-item>
                <v-list-item
                  v-if="item.status === 'processing'"
                  prepend-icon="mdi-truck"
                  @click="assignDelivery(item)"
                >
                  Assign Delivery
                </v-list-item>
                <v-list-item
                  v-if="item.status === 'in_transit'"
                  prepend-icon="mdi-check-circle"
                  @click="updateOrderStatus(item, 'delivered')"
                >
                  Mark as Delivered
                </v-list-item>
              </v-list>
            </v-menu>
          </template>
        </v-data-table>
      </v-card>
    </v-container>

    <!-- Assign Delivery Dialog -->
    <v-dialog v-model="deliveryDialog" max-width="600">
      <v-card>
        <v-card-title>Assign Delivery</v-card-title>
        <v-card-text>
          <v-form ref="deliveryForm" v-model="deliveryFormValid">
            <v-select
              v-model="deliveryData.delivery_personnel_id"
              :items="deliveryPersonnel"
              item-title="name"
              item-value="id"
              label="Delivery Personnel"
              variant="outlined"
            ></v-select>

            <v-text-field
              v-model="deliveryData.scheduled_date"
              label="Scheduled Date & Time"
              type="datetime-local"
              variant="outlined"
              :min="minDateTime"
            ></v-text-field>

            <v-textarea
              v-model="deliveryData.delivery_notes"
              label="Delivery Notes"
              variant="outlined"
              rows="2"
            ></v-textarea>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="deliveryDialog = false">Cancel</v-btn>
          <v-btn color="primary" :loading="assigning" @click="confirmAssignDelivery">
            Assign
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
import { useOrderStore } from '@/stores/orders'
import { useDeliveryStore } from '@/stores/deliveries'
import { useUserStore } from '@/stores/users'

const orderStore = useOrderStore()
const deliveryStore = useDeliveryStore()
const userStore = useUserStore()

const selectedStatus = ref('all')
const selectedRegion = ref(null)
const deliveryDialog = ref(false)
const deliveryForm = ref(null)
const deliveryFormValid = ref(false)

const regionFilters = [
  { title: 'All Regions', value: null },
  { title: 'Cebu (Local)', value: 'cebu' },
  { title: 'Surigao Region (Inter-island)', value: 'surigao' }
]
const assigning = ref(false)
const selectedOrder = ref(null)

const deliveryData = ref({
  delivery_personnel_id: null,
  scheduled_date: '',
  delivery_notes: ''
})

const snackbar = ref({
  show: false,
  message: '',
  color: 'success'
})

const headers = [
  { title: 'Order #', key: 'order_number' },
  { title: 'Customer', key: 'customer' },
  { title: 'Items', key: 'items' },
  { title: 'Shipping Zone', key: 'shipping_zone' },
  { title: 'Payment', key: 'payment' },
  { title: 'Status', key: 'status' },
  { title: 'Total', key: 'total_amount' },
  { title: 'Date', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false }
]

const filteredOrders = computed(() => {
  let orders = orderStore.orders

  // Filter by status
  if (selectedStatus.value !== 'all') {
    orders = orders.filter(o => o.status === selectedStatus.value)
  }

  // Filter by region
  if (selectedRegion.value) {
    orders = orders.filter(o => {
      const zoneName = o.shipping_zone?.province || ''
      if (selectedRegion.value === 'cebu') {
        return zoneName === 'Cebu'
      } else if (selectedRegion.value === 'surigao') {
        return zoneName.includes('Surigao') || zoneName.includes('Agusan') || zoneName.includes('Dinagat')
      }
      return true
    })
  }

  return orders
})

const deliveryPersonnel = computed(() => {
  return userStore.users.filter(u => u.roles?.some(r => r.name === 'delivery_personnel'))
})

const minDateTime = computed(() => {
  return new Date().toISOString().slice(0, 16)
})

const getStatusColor = (status) => {
  const colors = {
    pending: 'warning',
    processing: 'info',
    in_transit: 'primary',
    delivered: 'success',
    cancelled: 'error'
  }
  return colors[status]
}

const getStatusText = (status) => {
  return status.replace('_', ' ').toUpperCase()
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
    case 'verification_pending': return 'VERIFY'
    case 'verification_failed': return 'FAILED'
    default: return status?.toUpperCase() || 'UNKNOWN'
  }
}

const viewOrder = (order) => {
  // Navigate to order details
  console.log('View order:', order)
}

const updateOrderStatus = async (order, status) => {
  try {
    await orderStore.updateOrderStatus(order.id, status)
    snackbar.value = {
      show: true,
      message: 'Order status updated successfully',
      color: 'success'
    }
  } catch (error) {
    snackbar.value = {
      show: true,
      message: 'Failed to update order status',
      color: 'error'
    }
  }
}

const assignDelivery = (order) => {
  selectedOrder.value = order
  
  // Use order's preferred delivery date as default, or today if not set
  const defaultDate = order.preferred_delivery_date 
    ? new Date(order.preferred_delivery_date).toISOString().slice(0, 16)
    : new Date().toISOString().slice(0, 16)
  
  deliveryData.value = {
    delivery_personnel_id: null,
    scheduled_date: defaultDate,
    delivery_notes: ''
  }
  deliveryDialog.value = true
}

const confirmAssignDelivery = async () => {
  assigning.value = true
  try {
    await deliveryStore.createDelivery({
      order_id: selectedOrder.value.id,
      ...deliveryData.value
    })
    deliveryDialog.value = false
    snackbar.value = {
      show: true,
      message: 'Delivery assigned successfully',
      color: 'success'
    }
    await orderStore.fetchOrders()
  } catch (error) {
    snackbar.value = {
      show: true,
      message: 'Failed to assign delivery',
      color: 'error'
    }
  } finally {
    assigning.value = false
  }
}

const verifyPayment = async (order, action) => {
  try {
    const response = await fetch(`/api/v1/orders/${order.id}/verify-payment`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      },
      body: JSON.stringify({ action })
    })

    const data = await response.json()

    if (data.success) {
      snackbar.value = {
        show: true,
        message: `Payment ${action === 'approve' ? 'approved' : 'rejected'} successfully`,
        color: 'success'
      }
      await orderStore.fetchOrders()
    } else {
      throw new Error(data.message)
    }
  } catch (error) {
    snackbar.value = {
      show: true,
      message: `Failed to ${action} payment`,
      color: 'error'
    }
  }
}

const viewReceipt = (order) => {
  if (order.payment_receipt_path) {
    const receiptUrl = `/storage/${order.payment_receipt_path}`
    window.open(receiptUrl, '_blank')
  }
}

onMounted(async () => {
  await Promise.all([
    orderStore.fetchOrders(),
    userStore.fetchUsers()
  ])
})
</script>


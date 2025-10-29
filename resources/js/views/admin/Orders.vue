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

      <!-- Status Filter -->
      <v-row class="mb-4">
        <v-col cols="12">
          <v-chip-group v-model="selectedStatus" mandatory>
            <v-chip value="all" filter>All Orders</v-chip>
            <v-chip value="pending" filter color="warning">Pending</v-chip>
            <v-chip value="processing" filter color="info">Processing</v-chip>
            <v-chip value="in_transit" filter color="primary">In Transit</v-chip>
            <v-chip value="delivered" filter color="success">Delivered</v-chip>
          </v-chip-group>
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
                  v-if="item.status === 'pending'"
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
              label="Scheduled Date"
              type="date"
              variant="outlined"
              :min="minDate"
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
const deliveryDialog = ref(false)
const deliveryForm = ref(null)
const deliveryFormValid = ref(false)
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
  { title: 'Status', key: 'status' },
  { title: 'Total', key: 'total_amount' },
  { title: 'Date', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false }
]

const filteredOrders = computed(() => {
  if (selectedStatus.value === 'all') {
    return orderStore.orders
  }
  return orderStore.orders.filter(o => o.status === selectedStatus.value)
})

const deliveryPersonnel = computed(() => {
  return userStore.users.filter(u => u.roles?.some(r => r.name === 'delivery_personnel'))
})

const minDate = computed(() => {
  return new Date().toISOString().split('T')[0]
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
  deliveryData.value = {
    delivery_personnel_id: null,
    scheduled_date: new Date().toISOString().split('T')[0],
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

onMounted(async () => {
  await Promise.all([
    orderStore.fetchOrders(),
    userStore.fetchUsers()
  ])
})
</script>


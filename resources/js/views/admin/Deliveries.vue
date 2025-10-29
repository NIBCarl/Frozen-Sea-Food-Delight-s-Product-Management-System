<template>
  <div class="admin-deliveries pa-4">
    <v-container fluid>
      <v-row class="mb-4">
        <v-col cols="12">
          <h1 class="text-h4 font-weight-bold">Delivery Management</h1>
          <p class="text-subtitle-1 text-grey-darken-1">Coordinate and track all deliveries</p>
        </v-col>
      </v-row>

      <!-- Filter Chips -->
      <v-row class="mb-4">
        <v-col cols="12">
          <v-chip-group v-model="selectedStatus" mandatory>
            <v-chip value="all" filter>All</v-chip>
            <v-chip value="scheduled" filter>Scheduled</v-chip>
            <v-chip value="out_for_delivery" filter>Out for Delivery</v-chip>
            <v-chip value="delivered" filter color="success">Delivered</v-chip>
            <v-chip value="failed" filter color="error">Failed</v-chip>
          </v-chip-group>
        </v-col>
      </v-row>

      <!-- Deliveries Table -->
      <v-card>
        <v-data-table
          :headers="headers"
          :items="filteredDeliveries"
          :loading="deliveryStore.loading"
          item-value="id"
        >
          <!-- Order -->
          <template v-slot:item.order="{ item }">
            <div>
              <div class="font-weight-bold">#{{ item.order?.order_number }}</div>
              <div class="text-caption">{{ item.order?.customer?.name }}</div>
            </div>
          </template>

          <!-- Personnel -->
          <template v-slot:item.personnel="{ item }">
            {{ item.deliveryPersonnel?.name || 'Unassigned' }}
          </template>

          <!-- Scheduled Date -->
          <template v-slot:item.scheduled_date="{ item }">
            {{ formatDate(item.scheduled_date) }}
          </template>

          <!-- Status -->
          <template v-slot:item.status="{ item }">
            <v-chip :color="getStatusColor(item.status)" size="small">
              {{ getStatusText(item.status) }}
            </v-chip>
          </template>

          <!-- Actions -->
          <template v-slot:item.actions="{ item }">
            <v-btn
              v-if="item.status === 'scheduled'"
              size="small"
              variant="tonal"
              color="info"
              @click="markOutForDelivery(item)"
            >
              Start Delivery
            </v-btn>
            <v-btn
              v-if="item.status === 'out_for_delivery'"
              size="small"
              variant="tonal"
              color="success"
              @click="markAsDelivered(item)"
            >
              Mark Delivered
            </v-btn>
          </template>
        </v-data-table>
      </v-card>
    </v-container>

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color">
      {{ snackbar.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useDeliveryStore } from '@/stores/deliveries'

const deliveryStore = useDeliveryStore()

const selectedStatus = ref('all')
const snackbar = ref({
  show: false,
  message: '',
  color: 'success'
})

const headers = [
  { title: 'Order', key: 'order' },
  { title: 'Personnel', key: 'personnel' },
  { title: 'Scheduled Date', key: 'scheduled_date' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions', sortable: false }
]

const filteredDeliveries = computed(() => {
  if (selectedStatus.value === 'all') {
    return deliveryStore.deliveries
  }
  return deliveryStore.deliveries.filter(d => d.status === selectedStatus.value)
})

const getStatusColor = (status) => {
  const colors = {
    scheduled: 'grey',
    out_for_delivery: 'info',
    delivered: 'success',
    failed: 'error'
  }
  return colors[status]
}

const getStatusText = (status) => {
  return status.replace('_', ' ').toUpperCase()
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const markOutForDelivery = async (delivery) => {
  try {
    await deliveryStore.updateDeliveryStatus(delivery.id, { status: 'out_for_delivery' })
    snackbar.value = {
      show: true,
      message: 'Delivery marked as out for delivery',
      color: 'success'
    }
  } catch (error) {
    snackbar.value = {
      show: true,
      message: 'Failed to update status',
      color: 'error'
    }
  }
}

const markAsDelivered = async (delivery) => {
  try {
    await deliveryStore.updateDeliveryStatus(delivery.id, { status: 'delivered' })
    snackbar.value = {
      show: true,
      message: 'Delivery marked as completed',
      color: 'success'
    }
  } catch (error) {
    snackbar.value = {
      show: true,
      message: 'Failed to update status',
      color: 'error'
    }
  }
}

onMounted(async () => {
  await deliveryStore.fetchDeliveries()
})
</script>


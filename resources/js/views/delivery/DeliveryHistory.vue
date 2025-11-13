<template>
  <div class="delivery-history pa-4">
    <v-container fluid>
      <v-row class="mb-4">
        <v-col cols="12">
          <h1 class="text-h4 font-weight-bold">Delivery History</h1>
          <p class="text-subtitle-1 text-grey-darken-1">All completed and failed deliveries</p>
        </v-col>
      </v-row>

      <!-- Loading -->
      <v-progress-linear v-if="deliveryStore.loading" indeterminate></v-progress-linear>

      <!-- History List -->
      <v-row v-else-if="deliveryStore.historyDeliveries.length > 0">
        <v-col v-for="delivery in deliveryStore.historyDeliveries" :key="delivery.id" cols="12" md="6">
          <v-card hover>
            <v-card-title class="d-flex justify-space-between align-center">
              <span class="text-h6">Order #{{ delivery.order?.order_number }}</span>
              <v-chip :color="getStatusColor(delivery.status)" size="small">
                {{ getStatusText(delivery.status) }}
              </v-chip>
            </v-card-title>
            <v-divider></v-divider>
            <v-card-text>
              <div class="mb-3">
                <div class="text-caption text-grey">Delivered On</div>
                <div class="text-body-2">{{ formatDate(delivery.actual_delivery_datetime || delivery.scheduled_date) }}</div>
              </div>
              <div class="mb-3">
                <div class="text-caption text-grey">Customer</div>
                <div class="font-weight-medium">{{ delivery.order?.customer?.name }}</div>
                <div class="text-body-2">{{ delivery.order?.contact_number }}</div>
              </div>
              <div class="mb-3">
                <div class="text-caption text-grey">Delivery Address</div>
                <div class="text-body-2">{{ delivery.order?.delivery_address }}</div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Empty -->
      <v-row v-else>
        <v-col cols="12" class="text-center py-16">
          <v-icon size="64" color="grey">mdi-history</v-icon>
          <p class="text-h6 mt-4">No completed deliveries yet</p>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useDeliveryStore } from '@/stores/deliveries'

const deliveryStore = useDeliveryStore()

const getStatusColor = (status) => {
  return status === 'delivered' ? 'success' : 'error'
}

const getStatusText = (status) => status.replace('_', ' ').toUpperCase()

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

onMounted(async () => {
  await deliveryStore.fetchHistoryDeliveries()
})
</script>

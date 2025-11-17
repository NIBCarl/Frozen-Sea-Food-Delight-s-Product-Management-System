<template>
  <div class="delivery-page pa-4">
    <v-container fluid>
      <v-row class="mb-4">
        <v-col cols="12">
          <h1 class="text-h4 font-weight-bold">Active Deliveries</h1>
          <p class="text-subtitle-1 text-grey-darken-1">{{ formatDate(new Date()) }} - Showing today and upcoming deliveries</p>
        </v-col>
      </v-row>

      <!-- Summary Cards -->
      <v-row class="mb-4">
        <v-col cols="12" md="3">
          <v-card color="primary" variant="tonal">
            <v-card-text>
              <div class="text-h3 font-weight-bold">{{ scheduledCount }}</div>
              <div class="text-body-1">Scheduled</div>
              <div class="text-caption">Total pending</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="3">
          <v-card color="info" variant="tonal">
            <v-card-text>
              <div class="text-h3 font-weight-bold">{{ outForDeliveryCount }}</div>
              <div class="text-body-1">Out for Delivery</div>
              <div class="text-caption">Currently active</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="3">
          <v-card color="success" variant="tonal">
            <v-card-text>
              <div class="text-h3 font-weight-bold">{{ completedCount }}</div>
              <div class="text-body-1">Completed</div>
              <div class="text-caption">All time total</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="3">
          <v-card color="error" variant="tonal">
            <v-card-text>
              <div class="text-h3 font-weight-bold">{{ failedCount }}</div>
              <div class="text-body-1">Failed</div>
              <div class="text-caption">All time total</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Loading -->
      <v-progress-linear v-if="deliveryStore.loading" indeterminate></v-progress-linear>

      <!-- Deliveries List -->
      <v-row v-else-if="deliveryStore.todayDeliveries.length > 0">
        <v-col v-for="delivery in deliveryStore.todayDeliveries" :key="delivery.id" cols="12" md="6">
          <v-card hover>
            <v-card-title class="d-flex justify-space-between align-center">
              <span class="text-h6">Order #{{ delivery.order?.order_number }}</span>
              <v-chip :color="getStatusColor(delivery.status)" size="small">
                {{ getStatusText(delivery.status) }}
              </v-chip>
            </v-card-title>

            <v-divider></v-divider>

            <v-card-text>
              <!-- Customer Info -->
              <div class="mb-3">
                <div class="text-caption text-grey">Customer</div>
                <div class="font-weight-medium">{{ delivery.order?.customer?.name }}</div>
                <div class="text-body-2">{{ delivery.order?.contact_number }}</div>
              </div>

              <!-- Delivery Address -->
              <div class="mb-3">
                <div class="text-caption text-grey">Delivery Address</div>
                <div class="text-body-2">{{ delivery.order?.delivery_address }}</div>
              </div>

              <!-- Order Items -->
              <div class="mb-3">
                <div class="text-caption text-grey">Items</div>
                <div v-for="item in delivery.order?.items" :key="item.id" class="text-body-2">
                  {{ item.quantity }}x {{ item.product?.name }}
                </div>
              </div>

              <!-- Scheduled Time -->
              <div class="mb-3">
                <div class="text-caption text-grey">Scheduled Time</div>
                <div class="text-body-2 font-weight-medium">{{ formatDeliveryTime(delivery.scheduled_date) }}</div>
              </div>

              <!-- Amount -->
              <div class="mb-3">
                <div class="text-caption text-grey">Amount to Collect (COD)</div>
                <div class="text-h6 font-weight-bold">₱{{ delivery.order?.total_amount }}</div>
              </div>

              <!-- Notes -->
              <div v-if="delivery.order?.notes">
                <div class="text-caption text-grey">Customer Notes</div>
                <v-alert type="info" variant="tonal" density="compact">
                  {{ delivery.order?.notes }}
                </v-alert>
              </div>
            </v-card-text>

            <v-divider></v-divider>

            <v-card-actions class="pa-4">
              <v-btn
                v-if="delivery.status === 'scheduled'"
                color="primary"
                prepend-icon="mdi-truck"
                @click="updateStatus(delivery, 'out_for_delivery')"
              >
                Start Delivery
              </v-btn>

              <v-btn
                v-if="delivery.status === 'out_for_delivery'"
                color="success"
                prepend-icon="mdi-check-circle"
                @click="openCompleteDialog(delivery)"
              >
                Mark as Delivered
              </v-btn>

              <v-btn
                v-if="delivery.status === 'out_for_delivery'"
                color="error"
                variant="outlined"
                prepend-icon="mdi-close-circle"
                @click="openFailedDialog(delivery)"
              >
                Mark as Failed
              </v-btn>

              <v-spacer></v-spacer>

              <v-btn
                variant="text"
                icon="mdi-phone"
                :href="`tel:${delivery.order?.contact_number}`"
              ></v-btn>

              <v-btn
                variant="text"
                icon="mdi-map-marker"
                @click="openMap(delivery.order?.delivery_address)"
              ></v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>

      <!-- Empty State -->
      <v-row v-else>
        <v-col cols="12" class="text-center py-16">
          <v-icon size="64" color="grey">mdi-truck-check</v-icon>
          <p class="text-h6 mt-4">No active deliveries</p>
          <p class="text-caption text-grey">All deliveries are completed or no orders pending</p>
        </v-col>
      </v-row>
    </v-container>

    <!-- Complete Delivery Dialog -->
    <v-dialog v-model="completeDialog" max-width="400">
      <v-card>
        <v-card-title>Complete Delivery</v-card-title>
        <v-card-text>
          <v-textarea
            v-model="deliveryNotes"
            label="Delivery Notes (Optional)"
            variant="outlined"
            rows="3"
          ></v-textarea>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="completeDialog = false">Cancel</v-btn>
          <v-btn color="success" :loading="updating" @click="confirmComplete">
            Confirm Delivery
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Failed Delivery Dialog -->
    <v-dialog v-model="failedDialog" max-width="400">
      <v-card>
        <v-card-title>Mark as Failed</v-card-title>
        <v-card-text>
          <v-textarea
            v-model="failureReason"
            label="Reason for Failure *"
            variant="outlined"
            rows="3"
            required
          ></v-textarea>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="failedDialog = false">Cancel</v-btn>
          <v-btn
            color="error"
            :loading="updating"
            :disabled="!failureReason"
            @click="confirmFailed"
          >
            Confirm
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
import { ref, onMounted, computed } from 'vue'
import { useDeliveryStore } from '@/stores/deliveries'

const deliveryStore = useDeliveryStore()

const completeDialog = ref(false)
const failedDialog = ref(false)
const selectedDelivery = ref(null)
const deliveryNotes = ref('')
const failureReason = ref('')
const updating = ref(false)

const snackbar = ref({
  show: false,
  message: '',
  color: 'success'
})

// Summary counts from statistics API
const scheduledCount = computed(() => deliveryStore.todayStatistics.scheduled)
const outForDeliveryCount = computed(() => deliveryStore.todayStatistics.out_for_delivery)
const completedCount = computed(() => deliveryStore.todayStatistics.delivered)
const failedCount = computed(() => deliveryStore.todayStatistics.failed)

const getStatusColor = (status) => {
  const colors = {
    scheduled: 'grey',
    out_for_delivery: 'info',
    delivered: 'success',
    failed: 'error'
  }
  return colors[status] || 'grey'
}

const getStatusText = (status) => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatDeliveryTime = (date) => {
  return new Date(date).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  })
}

const updateStatus = async (delivery, status) => {
  try {
    await deliveryStore.updateDeliveryStatus(delivery.id, { status })
    // Refresh statistics after status update
    await deliveryStore.fetchTodayStatistics()
    snackbar.value = {
      show: true,
      message: 'Delivery status updated',
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

const openCompleteDialog = (delivery) => {
  selectedDelivery.value = delivery
  deliveryNotes.value = ''
  completeDialog.value = true
}

const openFailedDialog = (delivery) => {
  selectedDelivery.value = delivery
  failureReason.value = ''
  failedDialog.value = true
}

const confirmComplete = async () => {
  updating.value = true
  try {
    await deliveryStore.updateDeliveryStatus(selectedDelivery.value.id, {
      status: 'delivered',
      delivery_notes: deliveryNotes.value
    })
    // Refresh statistics after completion
    await deliveryStore.fetchTodayStatistics()
    completeDialog.value = false
    snackbar.value = {
      show: true,
      message: 'Delivery marked as completed!',
      color: 'success'
    }
  } catch (error) {
    snackbar.value = {
      show: true,
      message: 'Failed to complete delivery',
      color: 'error'
    }
  } finally {
    updating.value = false
  }
}

const confirmFailed = async () => {
  updating.value = true
  try {
    await deliveryStore.updateDeliveryStatus(selectedDelivery.value.id, {
      status: 'failed',
      failure_reason: failureReason.value
    })
    // Refresh statistics after marking as failed
    await deliveryStore.fetchTodayStatistics()
    failedDialog.value = false
    snackbar.value = {
      show: true,
      message: 'Delivery marked as failed',
      color: 'warning'
    }
  } catch (error) {
    snackbar.value = {
      show: true,
      message: 'Failed to update status',
      color: 'error'
    }
  } finally {
    updating.value = false
  }
}

const openMap = (address) => {
  const url = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`
  window.open(url, '_blank')
}

onMounted(async () => {
  await Promise.all([
    deliveryStore.fetchTodayDeliveries(),
    deliveryStore.fetchTodayStatistics()
  ])
})
</script>


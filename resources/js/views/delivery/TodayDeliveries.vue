<template>
  <div class="delivery-page pa-4">
    <v-container fluid>
      <v-row class="mb-4">
        <v-col cols="12">
          <h1 class="text-h4 font-weight-bold">Active Deliveries</h1>
          <p class="text-subtitle-1 text-grey-darken-1">{{ formatDate(new Date()) }} - Showing all active and upcoming deliveries</p>
        </v-col>
      </v-row>

      <!-- Summary Cards -->
      <v-row class="mb-6">
        <v-col cols="12" md="3">
          <v-card color="primary" variant="tonal" class="rounded-lg">
            <v-card-text>
              <div class="text-h3 font-weight-bold">{{ scheduledCount }}</div>
              <div class="text-body-1">Scheduled</div>
              <div class="text-caption">Total pending</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="3">
          <v-card color="info" variant="tonal" class="rounded-lg">
            <v-card-text>
              <div class="text-h3 font-weight-bold">{{ outForDeliveryCount }}</div>
              <div class="text-body-1">Out for Delivery</div>
              <div class="text-caption">Currently active</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="3">
          <v-card color="success" variant="tonal" class="rounded-lg">
            <v-card-text>
              <div class="text-h3 font-weight-bold">{{ completedCount }}</div>
              <div class="text-body-1">Completed</div>
              <div class="text-caption">All time total</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="3">
          <v-card color="error" variant="tonal" class="rounded-lg">
            <v-card-text>
              <div class="text-h3 font-weight-bold">{{ failedCount }}</div>
              <div class="text-body-1">Failed</div>
              <div class="text-caption">All time total</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Loading -->
      <v-progress-linear v-if="deliveryStore.loading" indeterminate color="primary" class="mb-4"></v-progress-linear>

      <!-- Empty State -->
      <v-row v-else-if="deliveryStore.todayDeliveries.length === 0">
        <v-col cols="12" class="text-center py-16">
          <v-icon size="64" color="grey-lighten-1">mdi-truck-check-outline</v-icon>
          <p class="text-h6 mt-4 text-grey-darken-1">No active deliveries</p>
          <p class="text-caption text-grey">You're all caught up!</p>
        </v-col>
      </v-row>

      <!-- Grouped Deliveries List -->
      <template v-else>
        <!-- Overdue Tasks -->
        <div v-if="sortedDeliveries.overdue.length > 0" class="mb-8">
          <div class="d-flex align-center mb-4">
            <v-icon color="error" class="mr-2">mdi-alert-circle</v-icon>
            <h2 class="text-h6 font-weight-bold text-error">Overdue Deliveries</h2>
            <v-chip size="small" color="error" class="ml-3" variant="flat">{{ sortedDeliveries.overdue.length }}</v-chip>
          </div>
          <v-row>
            <v-col v-for="delivery in sortedDeliveries.overdue" :key="delivery.id" cols="12" md="6">
              <delivery-card :delivery="delivery" type="overdue" @update-status="updateStatus" @complete="openCompleteDialog" @failed="openFailedDialog" @map="openMap"></delivery-card>
            </v-col>
          </v-row>
        </div>

        <!-- Today's Tasks -->
        <div v-if="sortedDeliveries.today.length > 0" class="mb-8">
           <div class="d-flex align-center mb-4">
            <v-icon color="primary" class="mr-2">mdi-calendar-today</v-icon>
            <h2 class="text-h6 font-weight-bold text-primary">Today's Schedule</h2>
            <v-chip size="small" color="primary" class="ml-3" variant="flat">{{ sortedDeliveries.today.length }}</v-chip>
          </div>
          <v-row>
             <v-col v-for="delivery in sortedDeliveries.today" :key="delivery.id" cols="12" md="6">
              <delivery-card :delivery="delivery" type="today" @update-status="updateStatus" @complete="openCompleteDialog" @failed="openFailedDialog" @map="openMap"></delivery-card>
            </v-col>
          </v-row>
        </div>

        <!-- Upcoming Tasks -->
        <div v-if="sortedDeliveries.upcoming.length > 0" class="mb-8">
           <div class="d-flex align-center mb-4">
            <v-icon color="grey-darken-1" class="mr-2">mdi-calendar-clock</v-icon>
            <h2 class="text-h6 font-weight-bold text-grey-darken-2">Upcoming</h2>
            <v-chip size="small" color="grey" class="ml-3" variant="flat">{{ sortedDeliveries.upcoming.length }}</v-chip>
          </div>
          <v-row>
             <v-col v-for="delivery in sortedDeliveries.upcoming" :key="delivery.id" cols="12" md="6">
              <delivery-card :delivery="delivery" type="upcoming" @update-status="updateStatus" @complete="openCompleteDialog" @failed="openFailedDialog" @map="openMap"></delivery-card>
            </v-col>
          </v-row>
        </div>
      </template>
    </v-container>

    <!-- Complete Delivery Dialog -->
    <v-dialog v-model="completeDialog" max-width="450">
      <v-card class="rounded-lg">
        <v-card-title class="bg-success text-white py-3">
          <v-icon start icon="mdi-check-circle" class="mr-2"></v-icon>
          Complete Delivery
        </v-card-title>
        <v-card-text class="pt-4">
          <v-textarea
            v-model="deliveryNotes"
            label="Delivery Notes (Optional)"
            variant="outlined"
            rows="3"
            placeholder="Any comments about the delivery..."
            density="comfortable"
          ></v-textarea>
        </v-card-text>
        <v-card-actions class="px-4 pb-4">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="completeDialog = false">Cancel</v-btn>
          <v-btn color="success" variant="elevated" :loading="updating" @click="confirmComplete">
            Confirm Delivery
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Failed Delivery Dialog -->
    <v-dialog v-model="failedDialog" max-width="450">
      <v-card class="rounded-lg">
        <v-card-title class="bg-error text-white py-3">
          <v-icon start icon="mdi-alert-circle" class="mr-2"></v-icon>
          Mark as Failed
        </v-card-title>
        <v-card-text class="pt-4">
          <v-textarea
            v-model="failureReason"
            label="Reason for Failure *"
            variant="outlined"
            rows="3"
            required
            placeholder="Why was the delivery failed?"
            density="comfortable"
          ></v-textarea>
        </v-card-text>
        <v-card-actions class="px-4 pb-4">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="failedDialog = false">Cancel</v-btn>
          <v-btn
            color="error"
            variant="elevated"
            :loading="updating"
            :disabled="!failureReason"
            @click="confirmFailed"
          >
            Confirm Failure
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" location="top right">
      {{ snackbar.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, defineComponent } from 'vue'
import { useDeliveryStore } from '@/stores/deliveries'
import DeliveryCard from './components/DeliveryCard.vue'

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

// Summary counts
const scheduledCount = computed(() => deliveryStore.todayStatistics.scheduled)
const outForDeliveryCount = computed(() => deliveryStore.todayStatistics.out_for_delivery)
const completedCount = computed(() => deliveryStore.todayStatistics.delivered)
const failedCount = computed(() => deliveryStore.todayStatistics.failed)

// Grouping Logic
const sortedDeliveries = computed(() => {
    const now = new Date();
    const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();
    const todayEnd = todayStart + 86400000;
    
    const overdue = [];
    const today = [];
    const upcoming = [];

    deliveryStore.todayDeliveries.forEach(d => {
        // Skip completed/failed from main list if necessary, but API seems to send 'scheduled','out_for_delivery','in_transit'
        if (['delivered', 'failed'].includes(d.status)) return;

        const dDate = new Date(d.scheduled_date).getTime();
        
        if (dDate < todayStart) {
            overdue.push(d);
        } else if (dDate >= todayStart && dDate < todayEnd) {
            today.push(d);
        } else {
            upcoming.push(d);
        }
    });

    return { overdue, today, upcoming };
})

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
  })
}

// Keeping wrapper methods for main component
const updateStatus = async (delivery, status) => {
  try {
    await deliveryStore.updateDeliveryStatus(delivery.id, { status })
    await deliveryStore.fetchTodayStatistics()
    snackbar.value = { show: true, message: 'Status updated', color: 'success' }
  } catch (error) {
    snackbar.value = { show: true, message: 'Failed to update', color: 'error' }
  }
}

const openCompleteDialog = (delivery) => {
  selectedDelivery.value = delivery; deliveryNotes.value = ''; completeDialog.value = true;
}

const openFailedDialog = (delivery) => {
  selectedDelivery.value = delivery; failureReason.value = ''; failedDialog.value = true;
}

const confirmComplete = async () => {
    updating.value = true
    try {
        await deliveryStore.updateDeliveryStatus(selectedDelivery.value.id, {
            status: 'delivered', delivery_notes: deliveryNotes.value
        })
        await deliveryStore.fetchTodayStatistics()
        completeDialog.value = false
        snackbar.value = { show: true, message: 'Marked as Delivered', color: 'success' }
    } catch (e) {
         snackbar.value = { show: true, message: 'Error updating status', color: 'error' }
    } finally {
        updating.value = false
    }
}

const confirmFailed = async () => {
    updating.value = true
    try {
         await deliveryStore.updateDeliveryStatus(selectedDelivery.value.id, {
            status: 'failed', failure_reason: failureReason.value
        })
        await deliveryStore.fetchTodayStatistics()
        failedDialog.value = false
        snackbar.value = { show: true, message: 'Marked as Failed', color: 'warning' }
    } catch(e) {
         snackbar.value = { show: true, message: 'Error updating status', color: 'error' }
    } finally {
        updating.value = false
    }
}

const openMap = (address) => {
  window.open(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`, '_blank')
}

onMounted(async () => {
  await Promise.all([
    deliveryStore.fetchTodayDeliveries(),
    deliveryStore.fetchTodayStatistics()
  ])
})
</script>

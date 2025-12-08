<template>
  <div class="admin-deliveries pa-4">
    <v-container fluid>
      <v-row class="mb-4">
        <v-col cols="12">
          <h1 class="text-h4 font-weight-bold">Delivery Management</h1>
          <p class="text-subtitle-1 text-grey-darken-1">Coordinate and track all deliveries</p>
        </v-col>
      </v-row>

      <!-- Statistics Cards -->
      <v-row class="mb-4">
        <v-col cols="12" sm="6" md="3">
          <v-card color="warning" variant="tonal">
            <v-card-text class="d-flex align-center">
              <v-icon size="40" class="mr-3">mdi-account-clock</v-icon>
              <div>
                <div class="text-h4 font-weight-bold">{{ unassignedCount }}</div>
                <div class="text-body-2">Needs Assignment</div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card color="info" variant="tonal">
            <v-card-text class="d-flex align-center">
              <v-icon size="40" class="mr-3">mdi-clock-outline</v-icon>
              <div>
                <div class="text-h4 font-weight-bold">{{ scheduledCount }}</div>
                <div class="text-body-2">Scheduled</div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card color="primary" variant="tonal">
            <v-card-text class="d-flex align-center">
              <v-icon size="40" class="mr-3">mdi-truck-delivery</v-icon>
              <div>
                <div class="text-h4 font-weight-bold">{{ outForDeliveryCount }}</div>
                <div class="text-body-2">Out for Delivery</div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card color="success" variant="tonal">
            <v-card-text class="d-flex align-center">
              <v-icon size="40" class="mr-3">mdi-check-circle</v-icon>
              <div>
                <div class="text-h4 font-weight-bold">{{ deliveredCount }}</div>
                <div class="text-body-2">Delivered</div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Filter Chips -->
      <v-row class="mb-4">
        <v-col cols="12">
          <v-chip-group v-model="selectedStatus" mandatory>
            <v-chip value="all" filter>All</v-chip>
            <v-chip value="unassigned" filter color="warning">
              Unassigned
              <v-badge v-if="unassignedCount > 0" :content="unassignedCount" color="error" inline class="ml-1"></v-badge>
            </v-chip>
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

          <!-- Delivery Address -->
          <template v-slot:item.address="{ item }">
            <div class="text-body-2" style="max-width: 200px;">
              {{ item.order?.delivery_address || '-' }}
            </div>
          </template>

          <!-- Personnel -->
          <template v-slot:item.personnel="{ item }">
            <div v-if="item.delivery_personnel">
              <div class="font-weight-medium">{{ item.delivery_personnel.name }}</div>
              <div class="text-caption text-grey">{{ item.delivery_personnel.contact_number }}</div>
            </div>
            <v-btn
              v-else
              size="small"
              variant="tonal"
              color="warning"
              prepend-icon="mdi-account-plus"
              @click="openAssignDialog(item)"
            >
              Assign Driver
            </v-btn>
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
            <v-menu>
              <template v-slot:activator="{ props }">
                <v-btn icon="mdi-dots-vertical" size="small" variant="text" v-bind="props"></v-btn>
              </template>
              <v-list density="compact">
                <v-list-item
                  v-if="!item.delivery_personnel"
                  prepend-icon="mdi-account-plus"
                  @click="openAssignDialog(item)"
                >
                  Assign Driver
                </v-list-item>
                <v-list-item
                  v-if="item.delivery_personnel"
                  prepend-icon="mdi-account-switch"
                  @click="openAssignDialog(item)"
                >
                  Change Driver
                </v-list-item>
                <v-list-item
                  v-if="item.status === 'scheduled' && item.delivery_personnel"
                  prepend-icon="mdi-truck-fast"
                  @click="markOutForDelivery(item)"
                >
                  Start Delivery
                </v-list-item>
                <v-list-item
                  v-if="item.status === 'out_for_delivery'"
                  prepend-icon="mdi-check-circle"
                  @click="markAsDelivered(item)"
                >
                  Mark Delivered
                </v-list-item>
                <v-list-item
                  v-if="item.status === 'out_for_delivery'"
                  prepend-icon="mdi-alert-circle"
                  @click="openFailedDialog(item)"
                >
                  Mark Failed
                </v-list-item>
                <v-list-item
                  prepend-icon="mdi-note-edit"
                  @click="openNotesDialog(item)"
                >
                  Add Notes
                </v-list-item>
              </v-list>
            </v-menu>
          </template>
        </v-data-table>
      </v-card>
    </v-container>

    <!-- Assign Driver Dialog -->
    <v-dialog v-model="assignDialog" max-width="500">
      <v-card>
        <v-card-title>
          {{ selectedDelivery?.delivery_personnel ? 'Change' : 'Assign' }} Delivery Driver
        </v-card-title>
        <v-card-subtitle>
          Order #{{ selectedDelivery?.order?.order_number }}
        </v-card-subtitle>
        <v-divider></v-divider>
        <v-card-text>
          <v-select
            v-model="assignData.delivery_personnel_id"
            :items="deliveryPersonnel"
            item-title="name"
            item-value="id"
            label="Select Driver"
            variant="outlined"
            :rules="[v => !!v || 'Please select a driver']"
          >
            <template v-slot:item="{ item, props }">
              <v-list-item v-bind="props">
                <template v-slot:subtitle>
                  {{ item.raw.contact_number || 'No contact' }}
                </template>
              </v-list-item>
            </template>
          </v-select>

          <v-text-field
            v-model="assignData.scheduled_date"
            label="Scheduled Date & Time"
            type="datetime-local"
            variant="outlined"
            :min="minDateTime"
          ></v-text-field>

          <v-textarea
            v-model="assignData.delivery_notes"
            label="Delivery Notes (Optional)"
            variant="outlined"
            rows="2"
            placeholder="Special instructions for the driver..."
          ></v-textarea>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="assignDialog = false">Cancel</v-btn>
          <v-btn 
            color="primary" 
            :loading="assigning"
            :disabled="!assignData.delivery_personnel_id"
            @click="confirmAssignDriver"
          >
            {{ selectedDelivery?.delivery_personnel ? 'Update' : 'Assign' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Failed Delivery Dialog -->
    <v-dialog v-model="failedDialog" max-width="500">
      <v-card>
        <v-card-title>Mark Delivery as Failed</v-card-title>
        <v-card-subtitle>
          Order #{{ selectedDelivery?.order?.order_number }}
        </v-card-subtitle>
        <v-divider></v-divider>
        <v-card-text>
          <v-textarea
            v-model="failureReason"
            label="Reason for Failure"
            variant="outlined"
            rows="3"
            placeholder="e.g., Customer not available, Wrong address, etc."
            :rules="[v => !!v || 'Please provide a reason']"
          ></v-textarea>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="failedDialog = false">Cancel</v-btn>
          <v-btn 
            color="error" 
            :loading="updating"
            :disabled="!failureReason"
            @click="confirmMarkFailed"
          >
            Mark as Failed
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Notes Dialog -->
    <v-dialog v-model="notesDialog" max-width="500">
      <v-card>
        <v-card-title>Delivery Notes</v-card-title>
        <v-card-subtitle>
          Order #{{ selectedDelivery?.order?.order_number }}
        </v-card-subtitle>
        <v-divider></v-divider>
        <v-card-text>
          <v-textarea
            v-model="deliveryNotes"
            label="Notes"
            variant="outlined"
            rows="4"
            placeholder="Add delivery notes..."
          ></v-textarea>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="notesDialog = false">Cancel</v-btn>
          <v-btn 
            color="primary" 
            :loading="updating"
            @click="saveNotes"
          >
            Save Notes
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
import { useDeliveryStore } from '@/stores/deliveries'
import { useUserStore } from '@/stores/users'

const deliveryStore = useDeliveryStore()
const userStore = useUserStore()

const selectedStatus = ref('all')
const assignDialog = ref(false)
const failedDialog = ref(false)
const notesDialog = ref(false)
const selectedDelivery = ref(null)
const assigning = ref(false)
const updating = ref(false)
const failureReason = ref('')
const deliveryNotes = ref('')

const assignData = ref({
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
  { title: 'Order', key: 'order' },
  { title: 'Delivery Address', key: 'address', sortable: false },
  { title: 'Driver', key: 'personnel', sortable: false },
  { title: 'Scheduled Date', key: 'scheduled_date' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions', sortable: false }
]

// Statistics computed properties
const unassignedCount = computed(() => 
  deliveryStore.deliveries.filter(d => !d.delivery_personnel && d.status !== 'delivered' && d.status !== 'failed').length
)
const scheduledCount = computed(() => 
  deliveryStore.deliveries.filter(d => d.status === 'scheduled' && d.delivery_personnel).length
)
const outForDeliveryCount = computed(() => 
  deliveryStore.deliveries.filter(d => d.status === 'out_for_delivery').length
)
const deliveredCount = computed(() => 
  deliveryStore.deliveries.filter(d => d.status === 'delivered').length
)

const filteredDeliveries = computed(() => {
  if (selectedStatus.value === 'all') {
    return deliveryStore.deliveries
  }
  if (selectedStatus.value === 'unassigned') {
    return deliveryStore.deliveries.filter(d => !d.delivery_personnel && d.status !== 'delivered' && d.status !== 'failed')
  }
  return deliveryStore.deliveries.filter(d => d.status === selectedStatus.value)
})

const deliveryPersonnel = computed(() => {
  return userStore.users.filter(u => u.roles?.some(r => r.name === 'delivery_personnel'))
})

const minDateTime = computed(() => {
  return new Date().toISOString().slice(0, 16)
})

const getStatusColor = (status) => {
  const colors = {
    scheduled: 'info',
    out_for_delivery: 'primary',
    delivered: 'success',
    failed: 'error'
  }
  return colors[status] || 'grey'
}

const getStatusText = (status) => {
  return status.replace(/_/g, ' ').toUpperCase()
}

const formatDate = (date) => {
  return new Date(date).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  })
}

const openAssignDialog = (delivery) => {
  selectedDelivery.value = delivery
  assignData.value = {
    delivery_personnel_id: delivery.delivery_personnel?.id || null,
    scheduled_date: delivery.scheduled_date 
      ? new Date(delivery.scheduled_date).toISOString().slice(0, 16) 
      : new Date().toISOString().slice(0, 16),
    delivery_notes: delivery.delivery_notes || ''
  }
  assignDialog.value = true
}

const confirmAssignDriver = async () => {
  if (!assignData.value.delivery_personnel_id) return
  
  assigning.value = true
  try {
    await deliveryStore.createDelivery({
      order_id: selectedDelivery.value.order_id,
      ...assignData.value
    })
    assignDialog.value = false
    snackbar.value = {
      show: true,
      message: 'Driver assigned successfully',
      color: 'success'
    }
    await deliveryStore.fetchDeliveries()
  } catch (error) {
    snackbar.value = {
      show: true,
      message: error.response?.data?.message || 'Failed to assign driver',
      color: 'error'
    }
  } finally {
    assigning.value = false
  }
}

const markOutForDelivery = async (delivery) => {
  if (!delivery.delivery_personnel) {
    snackbar.value = {
      show: true,
      message: 'Please assign a driver first',
      color: 'warning'
    }
    return
  }
  
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

const openFailedDialog = (delivery) => {
  selectedDelivery.value = delivery
  failureReason.value = ''
  failedDialog.value = true
}

const confirmMarkFailed = async () => {
  if (!failureReason.value) return
  
  updating.value = true
  try {
    await deliveryStore.updateDeliveryStatus(selectedDelivery.value.id, { 
      status: 'failed',
      failure_reason: failureReason.value
    })
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

const openNotesDialog = (delivery) => {
  selectedDelivery.value = delivery
  deliveryNotes.value = delivery.delivery_notes || ''
  notesDialog.value = true
}

const saveNotes = async () => {
  updating.value = true
  try {
    // Include current status since API requires it
    await deliveryStore.updateDeliveryStatus(selectedDelivery.value.id, { 
      status: selectedDelivery.value.status,
      delivery_notes: deliveryNotes.value
    })
    notesDialog.value = false
    snackbar.value = {
      show: true,
      message: 'Notes saved successfully',
      color: 'success'
    }
  } catch (error) {
    snackbar.value = {
      show: true,
      message: 'Failed to save notes',
      color: 'error'
    }
  } finally {
    updating.value = false
  }
}

onMounted(async () => {
  await Promise.all([
    deliveryStore.fetchDeliveries(),
    userStore.fetchUsers()
  ])
})
</script>


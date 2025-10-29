<template>
  <div class="shipments-page pa-4">
    <v-container fluid>
      <v-row class="mb-4">
        <v-col cols="12" class="d-flex justify-space-between align-center">
          <div>
            <h1 class="text-h4 font-weight-bold">My Shipments</h1>
            <p class="text-subtitle-1 text-grey-darken-1">Manage shipments to Surigao City</p>
          </div>
          <v-btn color="primary" size="large" prepend-icon="mdi-plus" @click="showNewShipmentDialog">
            Log New Shipment
          </v-btn>
        </v-col>
      </v-row>

      <!-- Status Filters -->
      <v-row class="mb-4">
        <v-col cols="12">
          <v-chip-group v-model="selectedStatus" mandatory>
            <v-chip value="all" filter>All</v-chip>
            <v-chip value="pending" filter>Pending</v-chip>
            <v-chip value="in_transit" filter>In Transit</v-chip>
            <v-chip value="arrived" filter>Arrived</v-chip>
            <v-chip value="confirmed" filter>Confirmed</v-chip>
          </v-chip-group>
        </v-col>
      </v-row>

      <!-- Loading -->
      <v-progress-linear v-if="shipmentStore.loading" indeterminate></v-progress-linear>

      <!-- Shipments List -->
      <v-row v-else-if="filteredShipments.length > 0">
        <v-col v-for="shipment in filteredShipments" :key="shipment.id" cols="12" md="6" lg="4">
          <v-card hover>
            <v-card-title class="d-flex justify-space-between">
              <span>{{ shipment.shipment_number }}</span>
              <v-chip :color="getStatusColor(shipment.status)" size="small">
                {{ getStatusText(shipment.status) }}
              </v-chip>
            </v-card-title>

            <v-divider></v-divider>

            <v-card-text>
              <div class="mb-2">
                <div class="text-caption text-grey">Expected Arrival</div>
                <div class="font-weight-medium">{{ formatDate(shipment.expected_arrival_date) }}</div>
              </div>

              <div v-if="shipment.actual_arrival_date" class="mb-2">
                <div class="text-caption text-grey">Actual Arrival</div>
                <div class="font-weight-medium">{{ formatDate(shipment.actual_arrival_date) }}</div>
              </div>

              <div class="mb-2">
                <div class="text-caption text-grey">Items</div>
                <div v-for="item in shipment.items" :key="item.id" class="text-body-2">
                  {{ item.quantity }}x {{ item.product?.name }}
                </div>
              </div>

              <div v-if="shipment.notes" class="mb-2">
                <div class="text-caption text-grey">Notes</div>
                <div class="text-body-2">{{ shipment.notes }}</div>
              </div>
            </v-card-text>

            <v-card-actions>
              <v-btn variant="text" size="small" prepend-icon="mdi-eye">
                View Details
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>

      <!-- Empty State -->
      <v-row v-else>
        <v-col cols="12" class="text-center py-16">
          <v-icon size="64" color="grey">mdi-ship-wheel</v-icon>
          <p class="text-h6 mt-4">No shipments found</p>
          <v-btn color="primary" class="mt-4" @click="showNewShipmentDialog">
            Log Your First Shipment
          </v-btn>
        </v-col>
      </v-row>
    </v-container>

    <!-- New Shipment Dialog -->
    <v-dialog v-model="newShipmentDialog" max-width="700" persistent>
      <v-card>
        <v-card-title class="text-h5">Log New Shipment</v-card-title>
        <v-divider></v-divider>

        <v-card-text class="pa-6">
          <v-form ref="form" v-model="formValid">
            <v-text-field
              v-model="newShipment.expected_arrival_date"
              label="Expected Arrival Date *"
              type="date"
              variant="outlined"
              :min="minDate"
              :rules="[rules.required]"
            ></v-text-field>

            <v-textarea
              v-model="newShipment.notes"
              label="Shipment Notes"
              variant="outlined"
              rows="2"
              placeholder="Any additional information about this shipment"
            ></v-textarea>

            <v-divider class="my-4"></v-divider>
            <h3 class="text-h6 mb-3">Shipment Items</h3>

            <div v-for="(item, index) in newShipment.items" :key="index" class="mb-3">
              <v-row>
                <v-col cols="7">
                  <v-autocomplete
                    v-model="item.product_id"
                    :items="products"
                    item-title="name"
                    item-value="id"
                    label="Product *"
                    variant="outlined"
                    density="compact"
                    :rules="[rules.required]"
                  ></v-autocomplete>
                </v-col>
                <v-col cols="4">
                  <v-text-field
                    v-model.number="item.quantity"
                    label="Quantity *"
                    type="number"
                    min="1"
                    variant="outlined"
                    density="compact"
                    :rules="[rules.required, rules.positive]"
                  ></v-text-field>
                </v-col>
                <v-col cols="1" class="d-flex align-center">
                  <v-btn
                    v-if="newShipment.items.length > 1"
                    icon="mdi-delete"
                    size="small"
                    variant="text"
                    color="error"
                    @click="removeItem(index)"
                  ></v-btn>
                </v-col>
              </v-row>
            </div>

            <v-btn
              variant="outlined"
              prepend-icon="mdi-plus"
              @click="addItem"
            >
              Add Item
            </v-btn>
          </v-form>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
          <v-btn
            color="primary"
            :loading="submitting"
            :disabled="!formValid"
            @click="submitShipment"
          >
            Log Shipment
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
import { useShipmentStore } from '@/stores/shipments'
import { useProductStore } from '@/stores/products'

const shipmentStore = useShipmentStore()
const productStore = useProductStore()

const selectedStatus = ref('all')
const newShipmentDialog = ref(false)
const form = ref(null)
const formValid = ref(false)
const submitting = ref(false)

const newShipment = ref({
  expected_arrival_date: '',
  notes: '',
  items: [{ product_id: null, quantity: 1 }]
})

const snackbar = ref({
  show: false,
  message: '',
  color: 'success'
})

const rules = {
  required: v => !!v || 'Required',
  positive: v => v > 0 || 'Must be greater than 0'
}

const products = computed(() => productStore.products)

const filteredShipments = computed(() => {
  if (selectedStatus.value === 'all') {
    return shipmentStore.shipments
  }
  return shipmentStore.shipments.filter(s => s.status === selectedStatus.value)
})

const minDate = computed(() => {
  const date = new Date()
  date.setDate(date.getDate() + 1)
  return date.toISOString().split('T')[0]
})

const getStatusColor = (status) => {
  const colors = {
    pending: 'grey',
    in_transit: 'info',
    arrived: 'warning',
    confirmed: 'success'
  }
  return colors[status] || 'grey'
}

const getStatusText = (status) => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const showNewShipmentDialog = () => {
  newShipmentDialog.value = true
}

const closeDialog = () => {
  newShipmentDialog.value = false
  resetForm()
}

const resetForm = () => {
  newShipment.value = {
    expected_arrival_date: '',
    notes: '',
    items: [{ product_id: null, quantity: 1 }]
  }
  form.value?.reset()
}

const addItem = () => {
  newShipment.value.items.push({ product_id: null, quantity: 1 })
}

const removeItem = (index) => {
  newShipment.value.items.splice(index, 1)
}

const submitShipment = async () => {
  const { valid } = await form.value.validate()
  if (!valid) return

  submitting.value = true
  try {
    await shipmentStore.createShipment(newShipment.value)
    snackbar.value = {
      show: true,
      message: 'Shipment logged successfully!',
      color: 'success'
    }
    closeDialog()
  } catch (error) {
    snackbar.value = {
      show: true,
      message: error.response?.data?.message || 'Failed to log shipment',
      color: 'error'
    }
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  await Promise.all([
    shipmentStore.fetchShipments(),
    productStore.fetchProducts()
  ])
})
</script>


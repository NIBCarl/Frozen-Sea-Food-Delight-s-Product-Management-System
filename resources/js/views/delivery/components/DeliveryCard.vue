<template>
  <v-card elevation="2" class="rounded-lg h-100 d-flex flex-column" :style="`border-left: 6px solid rgb(var(--v-theme-${getBorderColor}))`">
    <div class="pa-4 flex-grow-1">
      <!-- Header -->
      <div class="d-flex justify-space-between align-start mb-3">
        <div>
          <div class="text-caption text-grey">Order #</div>
          <div class="text-subtitle-1 font-weight-bold">{{ delivery.order?.order_number }}</div>
        </div>
        <v-chip :color="getStatusChip(delivery.status).color" :class="`text-${getStatusChip(delivery.status).textColor}`" size="small" label font-weight-medium>
           {{ getStatusChip(delivery.status).text }}
        </v-chip>
      </div>

      <v-divider class="mb-3"></v-divider>

      <!-- Address -->
      <div class="d-flex align-start mb-4">
           <v-avatar color="grey-lighten-4" rounded size="40" class="mr-3 mt-1">
              <v-icon color="primary" size="24">mdi-map-marker-radius</v-icon>
           </v-avatar>
           <div>
              <div class="text-caption text-grey font-weight-medium">Delivery Address</div>
              <div class="text-body-2" style="line-height: 1.3;">{{ delivery.order?.delivery_address }}</div>

           </div>
      </div>

      <!-- Customer -->
      <div class="d-flex align-center mb-3">
           <v-icon size="20" color="grey" class="mr-3">mdi-account</v-icon>
           <div>
              <div class="text-body-2 font-weight-medium">{{ delivery.order?.customer?.name }}</div>
              <div class="text-caption text-grey">{{ delivery.order?.contact_number }}</div>
           </div>
           <v-spacer></v-spacer>
           <v-btn icon="mdi-phone" variant="tonal" density="comfortable" size="small" color="success" :href="`tel:${delivery.order?.contact_number}`"></v-btn>
      </div>

      <!-- Amount -->
       <div class="d-flex align-center mb-1 pa-3 bg-grey-lighten-4 rounded">
           <div class="text-body-2 text-grey-darken-1 mr-2">Collect (COD):</div>
           <div class="text-h6 font-weight-bold text-success">{{ formatPrice(delivery.order?.total_amount) }}</div>
      </div>

      <!-- Scheduled -->
       <div class="d-flex justify-end mt-2">
           <div class="text-caption text-grey">Scheduled: {{ formatDeliveryTime(delivery.scheduled_date) }}</div>
      </div>
    </div>

    <!-- Actions -->
    <v-divider></v-divider>
    <div class="pa-3 bg-grey-lighten-5">
       <v-btn v-if="delivery.status === 'scheduled'" block color="primary" variant="flat" size="large" prepend-icon="mdi-truck-fast" @click="$emit('update-status', delivery, 'out_for_delivery')">
          START DELIVERY
       </v-btn>
       
       <div v-if="delivery.status === 'out_for_delivery' || delivery.status === 'in_transit'" class="d-flex gap-2">
          <v-btn color="success" variant="flat" class="flex-grow-1" prepend-icon="mdi-check-circle" @click="$emit('complete', delivery)">
              DELIVERED
          </v-btn>
           <v-btn color="error" variant="text" icon="mdi-close-circle" @click="$emit('failed', delivery)"></v-btn>
       </div>
    </div>
  </v-card>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps(['delivery', 'type'])
const emit = defineEmits(['update-status', 'complete', 'failed', 'map'])

const getBorderColor = computed(() => {
    if (props.type === 'overdue') return 'error';
    if (props.type === 'today') return 'primary';
    return 'grey-lighten-1';
});

const formatDeliveryTime = (date) => {
  return new Date(date).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  })
}

const getStatusChip = (status) => {
    const map = {
        scheduled: { color: 'grey-lighten-2', text: 'Scheduled', textColor: 'grey-darken-3' },
        out_for_delivery: { color: 'info', text: 'Out for Delivery', textColor: 'white' },
        in_transit: { color: 'primary', text: 'In Transit', textColor: 'white' },
    };
    return map[status] || { color: 'grey', text: status, textColor: 'white' };
}

const formatPrice = (amount) => {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}
</script>

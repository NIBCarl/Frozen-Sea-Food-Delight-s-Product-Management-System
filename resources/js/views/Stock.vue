<template>
  <v-container fluid>
    <h1 class="text-h4 mb-4">Stock Management</h1>
    
    <v-card>
      <v-card-title class="d-flex align-center pe-2">
        <v-icon icon="mdi-swap-horizontal-bold"></v-icon>
        &nbsp;Stock Movements

        <v-spacer></v-spacer>

        <v-btn class="ms-4" color="primary" @click="openNewMovementDialog">
          New Movement
        </v-btn>
      </v-card-title>

      <v-divider></v-divider>

      <v-data-table-server
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="stockStore.movements"
        :items-length="stockStore.pagination.total"
        :loading="stockStore.loading"
        item-value="id"
        @update:options="loadItems"
      >
        <template v-slot:item.type="{ item }">
          <v-chip :color="getTypeColor(item.type)" small>
            {{ item.type.toUpperCase() }}
          </v-chip>
        </template>

        <template v-slot:item.quantity="{ item }">
          <span :class="item.type === 'out' ? 'text-red' : 'text-green'">
            {{ item.type === 'out' ? '-' : '+' }}{{ item.quantity }}
          </span>
        </template>

        <template v-slot:item.created_at="{ item }">
          <span>{{ new Date(item.created_at).toLocaleString() }}</span>
        </template>

      </v-data-table-server>
    </v-card>

    <!-- Stock Movement Form Dialog -->
    <v-dialog v-model="dialog" max-width="600px" persistent>
      <StockMovementForm 
        @close="closeDialog"
        @save="onMovementSave"
      />
    </v-dialog>

  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useStockStore } from '@/stores/stock';
import StockMovementForm from '@/components/stock/StockMovementForm.vue';

const stockStore = useStockStore();

const dialog = ref(false);
const itemsPerPage = ref(15);

const headers = [
  { title: 'Date', key: 'created_at' },
  { title: 'Product', key: 'product.name', align: 'start' },
  { title: 'Type', key: 'type' },
  { title: 'Quantity', key: 'quantity', align: 'center' },
  { title: 'Reference', key: 'reference' },
  { title: 'User', key: 'creator.name' },
];

const loadItems = async ({ page, itemsPerPage, sortBy }) => {
  await stockStore.fetchMovements({ 
    page, 
    per_page: itemsPerPage, 
    sort_by: sortBy && sortBy.length ? sortBy[0].key : null,
    sort_order: sortBy && sortBy.length ? sortBy[0].order : null,
  });
};

onMounted(() => {
  if (!stockStore.movements.length) {
      loadItems({ page: 1, itemsPerPage: itemsPerPage.value });
  }
});

const openNewMovementDialog = () => {
  dialog.value = true;
};

const closeDialog = () => {
  dialog.value = false;
};

const onMovementSave = () => {
  closeDialog();
  loadItems({ page: 1, itemsPerPage: itemsPerPage.value }); // Refresh the list
};

const getTypeColor = (type) => {
  switch (type) {
    case 'in': return 'green';
    case 'out': return 'red';
    case 'adjustment': return 'blue';
    default: return 'grey';
  }
};

</script>

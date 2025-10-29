<template>
  <v-container fluid>
    <h1 class="text-h4 mb-4">Reports & Exports</h1>

    <!-- Report Filters -->
    <v-card class="mb-6">
      <v-card-text>
        <v-row align="center">
          <v-col cols="12" md="3">
            <v-select
              v-model="selectedReport"
              :items="reportTypes"
              label="Select Report"
              hide-details
            ></v-select>
          </v-col>

          <!-- Dynamic Filters -->
          <template v-if="selectedReport === 'inventory'">
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.category_id"
                :items="categoryStore.allCategories"
                item-title="name"
                item-value="id"
                label="Category"
                clearable
                hide-details
              ></v-select>
            </v-col>
            <v-col cols="12" md="2">
              <v-checkbox v-model="filters.low_stock" label="Low Stock Only" hide-details></v-checkbox>
            </v-col>
          </template>

          <template v-if="selectedReport === 'products'">
             <v-col cols="12" md="3">
                <v-text-field type="date" v-model="filters.start_date" label="Start Date" hide-details></v-text-field>
            </v-col>
            <v-col cols="12" md="3">
                <v-text-field type="date" v-model="filters.end_date" label="End Date" hide-details></v-text-field>
            </v-col>
          </template>

          <v-col cols="12" md="auto" class="text-right">
            <v-btn color="primary" @click="generateReport" :loading="reportStore.loading">Generate</v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Report Results -->
    <v-card v-if="reportStore.reportData.length > 0 || reportStore.loading">
      <v-card-title class="d-flex align-center">
        Report Results
        <v-spacer></v-spacer>
        <v-menu>
          <template v-slot:activator="{ props }">
            <v-btn color="secondary" v-bind="props">Export As</v-btn>
          </template>
          <v-list>
            <v-list-item @click="exportReport('pdf')">
              <v-list-item-title>PDF</v-list-item-title>
            </v-list-item>
            <v-list-item @click="exportReport('excel')">
              <v-list-item-title>Excel (XLSX)</v-list-item-title>
            </v-list-item>
            <v-list-item @click="exportReport('csv')">
              <v-list-item-title>CSV</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>
      </v-card-title>
      <v-divider></v-divider>

      <!-- Summary -->
      <v-card-text>
        <v-row>
          <v-col v-for="(value, key) in reportStore.summary" :key="key" cols="6" sm="3">
            <v-card outlined>
              <v-card-text class="text-center">
                <div class="text-h6">{{ value }}</div>
                <div class="text-caption">{{ formatSummaryKey(key) }}</div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-card-text>

      <v-data-table
        :headers="currentHeaders"
        :items="reportStore.reportData"
        :loading="reportStore.loading"
        class="elevation-1"
      ></v-data-table>
    </v-card>

    <v-alert v-else-if="!reportStore.loading" type="info" class="mt-6">
      Please select your desired report and filters, then click "Generate".
    </v-alert>

  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useReportStore } from '@/stores/reports';
import { useCategoryStore } from '@/stores/categories';

const reportStore = useReportStore();
const categoryStore = useCategoryStore();

const selectedReport = ref('inventory');
const reportTypes = ['inventory', 'products'];
const filters = ref({});

const inventoryHeaders = [
  { title: 'Product Name', key: 'name' },
  { title: 'Category', key: 'category.name' },
  { title: 'Stock Quantity', key: 'stock_quantity' },
  { title: 'Price', key: 'price' },
  { title: 'Status', key: 'status' },
];

const productHeaders = [
  { title: 'Product Name', key: 'name' },
  { title: 'Category', key: 'category.name' },
  { title: 'Created By', key: 'creator.name' },
  { title: 'Created At', key: 'created_at' },
  { title: 'Status', key: 'status' },
];

const currentHeaders = computed(() => {
  return selectedReport.value === 'inventory' ? inventoryHeaders : productHeaders;
});

onMounted(() => {
  categoryStore.fetchAllCategories();
});

const generateReport = () => {
  reportStore.generateReport(selectedReport.value, filters.value);
};

const exportReport = (format) => {
  reportStore.exportReport(format, selectedReport.value, filters.value);
};

const formatSummaryKey = (key) => {
  return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

</script>

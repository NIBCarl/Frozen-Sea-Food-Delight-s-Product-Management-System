<template>
  <v-container fluid>
    <h1 class="text-h4 mb-4">Product Management</h1>
    
    <v-card>
      <!-- Desktop Header -->
      <v-card-title class="d-none d-md-flex align-center pe-2">
        <v-icon icon="mdi-package-variant"></v-icon>
        &nbsp;Products

        <v-spacer></v-spacer>

        <v-text-field
          v-model="search"
          density="compact"
          label="Search"
          prepend-inner-icon="mdi-magnify"
          variant="solo-filled"
          flat
          hide-details
          single-line
          style="max-width: 300px;"
        ></v-text-field>

        <v-btn class="ms-4" color="primary" @click="openNewProductDialog">
          New Product
        </v-btn>
      </v-card-title>

      <!-- Mobile Header -->
      <div class="d-md-none">
        <v-card-title class="d-flex align-center justify-space-between pb-2">
          <div class="d-flex align-center">
            <v-icon icon="mdi-package-variant"></v-icon>
            &nbsp;Products
          </div>
          <v-btn color="primary" size="small" @click="openNewProductDialog">
            <v-icon>mdi-plus</v-icon>
          </v-btn>
        </v-card-title>
        
        <v-card-text class="pt-0 pb-2">
          <v-text-field
            v-model="search"
            density="compact"
            label="Search products..."
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
            hide-details
          ></v-text-field>
        </v-card-text>
      </div>

      <v-divider></v-divider>

      <v-data-table-server
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="productStore.products"
        :items-length="productStore.pagination.total"
        :loading="productStore.loading"
        :search="search"
        item-value="id"
        :mobile="null"
        mobile-breakpoint="sm"
        @update:options="loadItems"
      >
        <template v-slot:item.primary_image="{ item }">
          <v-avatar size="40" class="ma-2">
            <v-img :src="item.primary_image?.thumbnail_url || 'https://via.placeholder.com/80x80?text=No+Image'" alt="Product Image"></v-img>
          </v-avatar>
        </template>

        <template v-slot:item.price="{ item }">
          <span>${{ parseFloat(item.price).toFixed(2) }}</span>
        </template>

        <template v-slot:item.status="{ item }">
          <v-chip :color="getStatusColor(item.status)" small>{{ item.status }}</v-chip>
        </template>

        <template v-slot:item.actions="{ item }">
          <v-btn-group density="compact">
            <v-btn size="small" icon="mdi-pencil" @click="editItem(item)" color="primary"></v-btn>
            <v-btn size="small" icon="mdi-delete" @click="deleteItem(item)" color="error"></v-btn>
          </v-btn-group>
        </template>

        <!-- Mobile-friendly item template -->
        <template v-slot:item="{ item }" v-if="$vuetify.display.mobile">
          <tr>
            <td colspan="100%">
              <v-card class="ma-2" elevation="2">
                <v-card-text>
                  <div class="d-flex align-center mb-2">
                    <v-avatar size="40" class="me-3">
                      <v-img :src="item.primary_image?.thumbnail_url || 'https://via.placeholder.com/80x80?text=No+Image'" alt="Product Image"></v-img>
                    </v-avatar>
                    <div class="flex-grow-1">
                      <div class="font-weight-bold">{{ item.name }}</div>
                      <div class="text-caption text-grey">{{ item.product_id }}</div>
                    </div>
                    <v-chip :color="getStatusColor(item.status)" size="small">{{ item.status }}</v-chip>
                  </div>
                  
                  <v-divider class="my-2"></v-divider>
                  
                  <div class="d-flex justify-space-between align-center mb-1">
                    <span class="text-body-2">Category:</span>
                    <span>{{ item.category?.name }}</span>
                  </div>
                  <div class="d-flex justify-space-between align-center mb-1">
                    <span class="text-body-2">Supplier:</span>
                    <span>{{ item.creator?.name }}</span>
                  </div>
                  <div class="d-flex justify-space-between align-center mb-1">
                    <span class="text-body-2">Price:</span>
                    <span class="font-weight-bold">${{ parseFloat(item.price).toFixed(2) }}</span>
                  </div>
                  <div class="d-flex justify-space-between align-center mb-3">
                    <span class="text-body-2">Stock:</span>
                    <span>{{ item.stock_quantity }}</span>
                  </div>
                  
                  <div class="d-flex justify-end">
                    <v-btn-group density="compact" size="small">
                      <v-btn icon="mdi-pencil" @click="editItem(item)" color="primary"></v-btn>
                      <v-btn icon="mdi-delete" @click="deleteItem(item)" color="error"></v-btn>
                    </v-btn-group>
                  </div>
                </v-card-text>
              </v-card>
            </td>
          </tr>
        </template>
      </v-data-table-server>
    </v-card>

    <!-- Product Form Dialog -->
    <v-dialog v-model="dialog" max-width="800px" persistent>
      <ProductForm 
        :product-id="editedItem.id"
        @close="closeDialog"
        @save="onProductSave"
      />
    </v-dialog>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="dialogDelete" max-width="500px">
      <v-card>
        <v-card-title class="text-h5">Are you sure you want to delete this item?</v-card-title>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="blue-darken-1" variant="text" @click="closeDelete">Cancel</v-btn>
          <v-btn color="blue-darken-1" variant="text" @click="deleteItemConfirm">OK</v-btn>
          <v-spacer></v-spacer>
        </v-card-actions>
      </v-card>
    </v-dialog>

  </v-container>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useProductStore } from '@/stores/products';
import ProductForm from '@/components/products/ProductForm.vue';

const productStore = useProductStore();

const dialog = ref(false);
const dialogDelete = ref(false);
const search = ref('');
const itemsPerPage = ref(15);

const headers = [
  { title: 'Image', key: 'primary_image', sortable: false },
  { title: 'Product ID', key: 'product_id', align: 'start' },
  { title: 'Name', key: 'name' },
  { title: 'Category', key: 'category.name' },
  { title: 'Supplier', key: 'creator.name' },
  { title: 'Price', key: 'price' },
  { title: 'Stock', key: 'stock_quantity' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const editedItem = ref({});
const itemToDelete = ref(null);

watch(search, () => {
  loadItems({ page: 1, itemsPerPage: itemsPerPage.value, search: search.value });
});

const loadItems = async ({ page, itemsPerPage, sortBy, search }) => {
  await productStore.fetchProducts({ 
    include_inactive: true,
    page, 
    per_page: itemsPerPage, 
    sort_by: sortBy && sortBy.length ? sortBy[0].key : null,
    sort_order: sortBy && sortBy.length ? sortBy[0].order : null,
    search: search,
  });
};

const openNewProductDialog = () => {
  editedItem.value = {};
  dialog.value = true;
};

const editItem = (item) => {
  editedItem.value = { ...item };
  dialog.value = true;
};

const deleteItem = (item) => {
  itemToDelete.value = item;
  dialogDelete.value = true;
};

const deleteItemConfirm = async () => {
  if (itemToDelete.value) {
    await productStore.deleteProduct(itemToDelete.value.id);
  }
  closeDelete();
};

const closeDelete = () => {
  dialogDelete.value = false;
  itemToDelete.value = null;
};

const closeDialog = () => {
  dialog.value = false;
  editedItem.value = {};
};

const onProductSave = () => {
  closeDialog();
  loadItems({ page: 1, itemsPerPage: itemsPerPage.value, search: search.value });
};

const getStatusColor = (status) => {
  switch (status) {
    case 'active': return 'green';
    case 'inactive': return 'orange';
    case 'discontinued': return 'red';
    default: return 'grey';
  }
};

</script>

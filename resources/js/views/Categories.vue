<template>
  <v-container fluid>
    <h1 class="text-h4 mb-4">Category Management</h1>
    
    <v-card>
      <!-- Desktop Header -->
      <v-card-title class="d-none d-md-flex align-center pe-2">
        <v-icon icon="mdi-tag-multiple"></v-icon>
        &nbsp;Categories

        <v-spacer></v-spacer>

        <v-btn class="ms-4" color="primary" @click="openNewCategoryDialog">
          New Category
        </v-btn>
      </v-card-title>

      <!-- Mobile Header -->
      <v-card-title class="d-md-none d-flex align-center justify-space-between">
        <div class="d-flex align-center">
          <v-icon icon="mdi-tag-multiple"></v-icon>
          &nbsp;Categories
        </div>
        <v-btn color="primary" size="small" @click="openNewCategoryDialog">
          <v-icon>mdi-plus</v-icon>
        </v-btn>
      </v-card-title>

      <v-divider></v-divider>

      <v-data-table-server
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="categoryStore.categories"
        :items-length="categoryStore.categories.length" 
        :loading="categoryStore.loading"
        item-value="id"
        :mobile="null"
        mobile-breakpoint="sm"
        @update:options="loadItems"
      >
        <template v-slot:item.image="{ item }">
          <v-avatar size="40" class="ma-2">
            <v-img :src="item.image_url || '/storage/categories/placeholder.png'" alt="Category Image"></v-img>
          </v-avatar>
        </template>

        <template v-slot:item.parent="{ item }">
          <span>{{ item.parent?.name || 'N/A' }}</span>
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
                      <v-img :src="item.image_url || '/storage/categories/placeholder.png'" alt="Category Image"></v-img>
                    </v-avatar>
                    <div class="flex-grow-1">
                      <div class="font-weight-bold">{{ item.name }}</div>
                      <div class="text-caption text-grey">{{ item.parent?.name || 'No Parent' }}</div>
                    </div>
                    <v-chip :color="getStatusColor(item.status)" size="small">{{ item.status }}</v-chip>
                  </div>
                  
                  <v-divider class="my-2"></v-divider>
                  
                  <div class="d-flex justify-space-between align-center mb-3">
                    <span class="text-body-2">Sort Order:</span>
                    <span>{{ item.sort_order }}</span>
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

    <!-- Category Form Dialog -->
    <v-dialog v-model="dialog" max-width="600px" persistent>
      <CategoryForm 
        :category-id="editedItem.id"
        @close="closeDialog"
        @save="onCategorySave"
      />
    </v-dialog>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="dialogDelete" max-width="500px">
      <v-card>
        <v-card-title class="text-h5">Are you sure you want to delete this category?</v-card-title>
        <v-card-text>This action cannot be undone. Any products in this category will need to be reassigned.</v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="blue-darken-1" variant="text" @click="closeDelete">Cancel</v-btn>
          <v-btn color="red-darken-1" variant="text" @click="deleteItemConfirm" :loading="deleting">Delete</v-btn>
          <v-spacer></v-spacer>
        </v-card-actions>
      </v-card>
    </v-dialog>

  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useCategoryStore } from '@/stores/categories';
import CategoryForm from '@/components/categories/CategoryForm.vue';

const categoryStore = useCategoryStore();

const dialog = ref(false);
const dialogDelete = ref(false);
const deleting = ref(false);
const itemsPerPage = ref(15);

const headers = [
  { title: 'Image', key: 'image', sortable: false },
  { title: 'Name', key: 'name', align: 'start' },
  { title: 'Parent Category', key: 'parent' },
  { title: 'Status', key: 'status' },
  { title: 'Sort Order', key: 'sort_order' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const editedItem = ref({});
const itemToDelete = ref(null);

const loadItems = async () => {
  await categoryStore.fetchCategories({ all: true }); // Fetch all for simplicity, can be paginated if needed
};

onMounted(loadItems);

const openNewCategoryDialog = () => {
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
    deleting.value = true;
    try {
      await categoryStore.deleteCategory(itemToDelete.value.id);
    } catch (error) {
      console.error("Failed to delete category:", error);
      // You can add a user-facing error notification here
    }
    deleting.value = false;
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

const onCategorySave = () => {
  closeDialog();
  loadItems(); // Refresh the list
};

const getStatusColor = (status) => {
  return status === 'active' ? 'green' : 'red';
};

</script>

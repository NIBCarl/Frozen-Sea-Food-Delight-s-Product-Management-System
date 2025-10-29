<template>
  <v-container fluid>
    <h1 class="text-h4 mb-4">User Management</h1>
    
    <v-card>
      <v-card-title class="d-flex align-center pe-2">
        <v-icon icon="mdi-account-group"></v-icon>
        &nbsp;Users

        <v-spacer></v-spacer>

        <v-btn class="ms-4" color="primary" @click="openNewUserDialog">
          New User
        </v-btn>
      </v-card-title>

      <v-divider></v-divider>

      <v-data-table-server
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="userStore.users"
        :items-length="userStore.pagination.total"
        :loading="userStore.loading"
        item-value="id"
        @update:options="loadItems"
      >
        <template v-slot:item.roles="{ item }">
          <v-chip v-for="role in item.roles" :key="role.id" class="ma-1" small>
            {{ role.name }}
          </v-chip>
        </template>

        <template v-slot:item.status="{ item }">
          <v-chip :color="getStatusColor(item.status)" small>{{ item.status }}</v-chip>
        </template>

        <template v-slot:item.actions="{ item }">
          <v-icon small class="me-2" @click="editItem(item)">mdi-pencil</v-icon>
          <v-icon small @click="deleteItem(item)">mdi-delete</v-icon>
        </template>
      </v-data-table-server>
    </v-card>

    <!-- User Form Dialog -->
    <v-dialog v-model="dialog" max-width="600px" persistent>
      <UserForm 
        :user-id="editedItem.id"
        @close="closeDialog"
        @save="onUserSave"
      />
    </v-dialog>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="dialogDelete" max-width="500px">
      <v-card>
        <v-card-title class="text-h5">Are you sure you want to delete this user?</v-card-title>
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
import { useUserStore } from '@/stores/users';
import UserForm from '@/components/users/UserForm.vue';

const userStore = useUserStore();

const dialog = ref(false);
const dialogDelete = ref(false);
const deleting = ref(false);
const itemsPerPage = ref(10);

const headers = [
  { title: 'Name', key: 'name', align: 'start' },
  { title: 'Email', key: 'email' },
  { title: 'Roles', key: 'roles', sortable: false },
  { title: 'Status', key: 'status' },
  { title: 'Joined', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const editedItem = ref({});
const itemToDelete = ref(null);

const loadItems = async ({ page, itemsPerPage }) => {
  await userStore.fetchUsers({ page, per_page: itemsPerPage });
};

onMounted(() => {
  loadItems({ page: 1, itemsPerPage: itemsPerPage.value });
  userStore.fetchRoles();
});

const openNewUserDialog = () => {
  editedItem.value = {};
  dialog.value = true;
};

const editItem = (item) => {
  editedItem.value = { ...item, roles: item.roles.map(r => r.name) }; // Pass role names to form
  dialog.value = true;
};

const deleteItem = (item) => {
  itemToDelete.value = item;
  dialogDelete.value = true;
};

const deleteItemConfirm = async () => {
  if (itemToDelete.value) {
    deleting.value = true;
    await userStore.deleteUser(itemToDelete.value.id);
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

const onUserSave = () => {
  closeDialog();
  // The store action will refetch the list, so no need to do it here
};

const getStatusColor = (status) => {
  switch (status) {
    case 'active': return 'green';
    case 'inactive': return 'orange';
    case 'suspended': return 'red';
    default: return 'grey';
  }
};

</script>

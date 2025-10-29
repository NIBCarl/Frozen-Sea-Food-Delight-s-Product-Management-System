<template>
  <v-card>
    <v-card-title>
      <span class="text-h5">{{ formTitle }}</span>
    </v-card-title>

    <v-card-text>
      <v-container>
        <v-form ref="form">
          <v-row>
            <v-col cols="12" sm="6">
              <v-text-field v-model="formData.name" label="Full Name" :rules="[rules.required]"></v-text-field>
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field v-model="formData.username" label="Username" :rules="[rules.required]"></v-text-field>
            </v-col>
            <v-col cols="12">
              <v-text-field v-model="formData.email" label="Email" :rules="[rules.required, rules.email]"></v-text-field>
            </v-col>
            <v-col cols="12">
              <v-select
                v-model="formData.roles"
                :items="userStore.roles"
                item-title="name"
                item-value="name"
                label="Roles"
                multiple
                chips
                :rules="[rules.requiredArray]"
              ></v-select>
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field 
                v-model="formData.password" 
                label="Password" 
                type="password"
                :rules="isNewUser ? [rules.required] : []"
                :hint="isNewUser ? '' : 'Leave blank to keep current password'"
                persistent-hint
              ></v-text-field>
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field 
                v-model="formData.password_confirmation" 
                label="Confirm Password" 
                type="password"
                :rules="[passwordConfirmationRule]"
              ></v-text-field>
            </v-col>
          </v-row>
        </v-form>
        <v-alert v-if="userStore.error" type="error" density="compact" class="mt-4">
          <ul v-for="(error, key) in userStore.error" :key="key">
            <li v-for="message in error" :key="message">{{ message }}</li>
          </ul>
        </v-alert>
      </v-container>
    </v-card-text>

    <v-card-actions>
      <v-spacer></v-spacer>
      <v-btn color="blue-darken-1" variant="text" @click="close">Cancel</v-btn>
      <v-btn color="blue-darken-1" variant="text" @click="save" :loading="userStore.loading">Save</v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { useUserStore } from '@/stores/users';

const props = defineProps({
  userId: {
    type: Number,
    default: null,
  },
});

const emit = defineEmits(['close', 'save']);

const userStore = useUserStore();

const form = ref(null);
const formData = ref({
  name: '',
  username: '',
  email: '',
  roles: [],
  password: '',
  password_confirmation: '',
});

const rules = {
  required: value => !!value || 'Required.',
  requiredArray: value => value.length > 0 || 'At least one role is required.',
  email: value => /.+@.+\..+/.test(value) || 'E-mail must be valid.',
};

const passwordConfirmationRule = computed(() => {
  return formData.value.password === formData.value.password_confirmation || 'Passwords do not match.';
});

const isNewUser = computed(() => !props.userId);
const formTitle = computed(() => (isNewUser.value ? 'New User' : 'Edit User'));

watch(() => props.userId, (newId) => {
  if (newId) {
    loadUser(newId);
  } else {
    resetForm();
  }
}, { immediate: true });

function loadUser(id) {
  const user = userStore.users.find(u => u.id === id);
  if (user) {
    formData.value = { 
      ...user, 
      roles: user.roles.map(r => r.name), // Use role names for v-select
      password: '',
      password_confirmation: '',
    };
  }
}

function resetForm() {
  formData.value = {
    name: '',
    username: '',
    email: '',
    roles: [],
    password: '',
    password_confirmation: '',
  };
  userStore.error = null;
}

async function save() {
  const { valid } = await form.value.validate();
  if (!valid) return;

  try {
    if (isNewUser.value) {
      await userStore.createUser(formData.value);
    } else {
      // Don't send empty password fields
      const dataToSend = { ...formData.value };
      if (!dataToSend.password) {
        delete dataToSend.password;
        delete dataToSend.password_confirmation;
      }
      await userStore.updateUser(props.userId, dataToSend);
    }
    
    if (!userStore.error) {
      emit('save');
    }
  } catch (error) {
    // Error is handled in the store, and displayed in the template
  }
}

function close() {
  resetForm();
  emit('close');
}

onMounted(() => {
  if (userStore.roles.length === 0) {
    userStore.fetchRoles();
  }
});
</script>
